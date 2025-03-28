<?php

namespace Maya\System;

use App\Middlewares\CorsMiddleware;
use App\Middlewares\CsrfMiddleware;
use Dotenv\Dotenv;
use Exception;
use Maya\Console\Nova;
use Maya\Database\Connection\Connection;
use Maya\Disk\Directory;
use Maya\Disk\File;
use Maya\Http\Redirect;
use Maya\Http\Request;
use Maya\Http\Response;
use Maya\Router\Routing;
use Maya\Router\Web\Route;
use Maya\Support\Arr;
use Maya\Support\Auth;
use Maya\Support\Config;
use Maya\Support\Cookie;
use Maya\Support\Error;
use Maya\Support\Flash;
use Maya\Support\Session;
use Maya\Support\Token;
use Maya\Support\URL;
use Maya\View\View;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RuntimeException;
use Throwable;

class Application extends Container
{
    /** @var string The Davinci framework version. */
    const VERSION = '1.3.3';

    protected $basePath;
    protected $configPath;
    protected $appPath;
    protected $storagePath;
    protected $assetsPath;
    protected $mayaPath;
    protected $publicPath;
    private ErrorHandler $errorHandler;

    /**
     * Create a new Maya application instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }
        $this->registerBaseBindings();
        $this->registerServices();
        $this->initialSettings();
        $this->loadProviders();
        if (config('APP.CHECK_UNUSED_VARIABLES', false)) {
            $this->allUnusedVariables();
        }
        if (PHP_SAPI !== 'cli') {
            $this->routing();
        }
    }

    public function version()
    {
        return static::VERSION;
    }

    public function joinPaths($basePath, $path = '')
    {
        return $basePath . ($path != '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
        $this->bindPathsInContainer();
        return $this;
    }
    public function basePath($path = '')
    {
        return $this->joinPaths($this->basePath, $path);
    }
    protected function bindPathsInContainer()
    {
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.assets', $this->assetsPath());
        $this->instance('path.maya', $this->mayaPath());
        $this->instance('path.public', $this->mayaPath());
    }
    public function configPath()
    {
        return $this->configPath ?: $this->basePath('config.php');
    }
    public function useConfigPath($path)
    {
        $this->configPath = $path;
        $this->instance('path.config', $path);
        return $this;
    }
    public function storagePath($path = '')
    {
        return $this->joinPaths($this->storagePath ?: $this->basePath('Storage'), $path);
    }
    public function useStoragePath($path)
    {
        $this->storagePath = $path;
        $this->instance('path.storage', $path);
        return $this;
    }
    public function publicPath($path = '')
    {
        return $this->joinPaths($this->publicPath ?: $this->basePath('public'), $path);
    }
    public function usePublicPath($path)
    {
        $this->publicPath = $path;
        $this->instance('path.public', $path);
        return $this;
    }
    public function assetsPath($path = '')
    {
        return $this->joinPaths($this->assetsPath ?: $this->basePath('Assets'), $path);
    }
    public function useAssetsPath($path)
    {
        $this->assetsPath = $path;
        $this->instance('path.assets', $path);
        return $this;
    }
    public function mayaPath($path = '')
    {
        return $this->joinPaths($this->mayaPath ?: dirname(__DIR__), $path);
    }
    public function useMayaPath($path)
    {
        $this->mayaPath = $path;
        $this->instance('path.maya', $path);
        return $this;
    }
    public function appPath($path = '')
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('App'), $path);
    }
    public function useAppPath($path)
    {
        $this->appPath = $path;
        $this->instance('path.app', $path);
        return $this;
    }
    protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
    }

    private function initialSettings()
    {
        ini_set('error_log', $this->storagePath('Logs/Maya/logfile.log'));
        $this->errorHandler = $this->make('errorHandler');
        $this->setupErrorHandling();
        $this->checkPHPVersion();
        $dotenv = Dotenv::createImmutable($this->basePath());
        $dotenv->load();
        $this->loadHelpers();
        $this->setTimeZone();
        $this->display_error(config('APP.DEBUG', false));
        set_time_limit(0);
        ini_set('memory_limit', '-1');
    }

    private function setTimeZone()
    {
        if (config('APP.TIMEZONE')) {
            date_default_timezone_set(config('APP.TIMEZONE'));
        }
    }
    public function terminate()
    {
        Connection::closeConnection();
        if (PHP_SAPI === 'cli') {
            return;
        }
        exit;
    }

    private function loadHelpers()
    {
        $this->loadSystemHelpers();
        $this->loadClientHelpers();
    }

    private function loadProviders(): void
    {
        foreach (config('APP.PROVIDERS') as $provider) {
            (new $provider)->boot();
        }
    }

    private function loadSystemHelpers(): void
    {
        foreach (glob(dirname(__DIR__) . '/Support/helpers/*.php') as $helperFile) {
            require_once($helperFile);
        }
    }

    private function loadClientHelpers(): void
    {
        foreach (glob($this->appPath('/Helpers/*.php')) as $helperFile) {
            require_once($helperFile);
        }
    }

    private function registerRoutes(): void
    {
        if (file_exists(basePath('/Routes/api.php'))) {
            Route::group(['prefix' => '/api'], function () {
                require_once(basePath('/Routes/api.php'));
            });
        }
        if (file_exists(basePath('/Routes/web.php'))) {
            Route::group(['middleware' => [CorsMiddleware::class, CsrfMiddleware::class]], function () {
                require_once(basePath('/Routes/web.php'));
            });
        }
    }
    private function registerConsoleRoute(): void
    {
        if (file_exists(basePath('/Routes/console.php'))) {
            require_once(basePath('/Routes/console.php'));
        }
    }

    private function routing(): void
    {
        $this->registerRoutes();
        (new Routing)->run();
    }

    private function checkPHPVersion(): void
    {
        if (version_compare(phpversion(), '8', '<')) {
            throw new Exception("PHP version must be 8 or higher to use this application");
        }
    }

    private function display_error($status): void
    {
        if ($status) {
            ini_set("display_errors", "1");
            ini_set("display_startup_errors", "1");
            error_reporting(E_ALL);
        } else {
            ini_set("display_errors", "0");
            ini_set("display_startup_errors", "0");
            error_reporting(0);
        }
    }


    private function setupErrorHandling(): void
    {
        set_error_handler([$this, 'handlePhpError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handlePhpError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if (!(error_reporting() & $errno)) {
            return;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public function handleException(Throwable $exception): void
    {
        $this->errorHandler->handle($exception);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->errorHandler->handle(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }

    public function runConsoleCommands($argv)
    {
        $this->registerConsoleRoute();
        Nova::handle($argv);
    }

    private function registerServices()
    {
        $services = [
            'request' => [Request::class, 'singletonIf'],
            'arr' => [Arr::class, 'singletonIf'],
            'errorHandler' => [ErrorHandler::class, 'bindIf'],
            'auth' => [Auth::class, 'singletonIf'],
            'debugger' => [Debugger::class, 'singletonIf'],
            'cookie' => [Cookie::class, 'singletonIf'],
            'url' => [URL::class, 'singletonIf'],
            'config' => [Config::class, 'singletonIf'],
            'token' => [Token::class, 'singletonIf'],
            'session' => [Session::class, 'singletonIf'],
            'redirect' => [Redirect::class, 'singletonIf'],
            'flash' => [Flash::class, 'singletonIf'],
            'error' => [Error::class, 'singletonIf'],
            'view' => [View::class, 'bind'],
            'response' => [Response::class, 'bind'],
            'file' => [File::class, 'bind'],
            'directory' => [Directory::class, 'bind'],
        ];

        foreach ($services as $alias => [$class, $method]) {
            $this->$method($class, $class);
            $this->alias($class, $alias);
        }
    }

    private function allUnusedVariables()
    {
        $directory = new RecursiveDirectoryIterator($this->appPath());
        $iterator = new RecursiveIteratorIterator($directory);
        $phpFiles = new RegexIterator($iterator, '/\.php$/i');

        $parser = (new ParserFactory())->createForHostVersion();

        $skipVars = [
            '$this',
            '$table',
            '$fillable',
            '$guarded',
            '$casts',
            '$dates',
            '$hidden',
            '$_SERVER',
            '$_GET',
            '$_POST',
            '$_FILES',
            '$_COOKIE',
            '$_SESSION',
            '$_REQUEST',
            '$_ENV'
        ];

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file->getPathname());

            try {
                $ast = $parser->parse($content);
            } catch (Exception $e) {
                continue;
            }

            $declared = [];
            $used = [];

            $visitor = new class($declared, $used, $skipVars) extends NodeVisitorAbstract {
                private $declared;
                private $used;
                private $skipVars;

                public function __construct(&$declared, &$used, $skipVars)
                {
                    $this->declared = &$declared;
                    $this->used = &$used;
                    $this->skipVars = $skipVars;
                }

                public function enterNode(Node $node)
                {
                    if ($node instanceof Node\Expr\Variable && is_string($node->name)) {
                        $varName = '$' . $node->name;

                        if (in_array($varName, $this->skipVars)) {
                            return;
                        }

                        if (!isset($this->declared[$varName])) {
                            $this->declared[$varName] = $node->getStartLine();
                        } else {
                            $this->used[$varName] = true;
                        }
                    }
                }
            };

            $traverser = new NodeTraverser();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            foreach ($declared as $varName => $vLine) {
                if (!isset($used[$varName])) {
                    throw new RuntimeException(
                        "Unused variable {$varName} in {$file->getPathname()}:{$vLine}"
                    );
                }
            }
        }
    }
}
