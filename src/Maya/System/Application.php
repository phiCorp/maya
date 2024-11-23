<?php

namespace Maya\System;

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
use Throwable;

class Application extends Container
{
    /** @var string The Davinci framework version. */
    const VERSION = '1.0.0';

    protected $basePath;
    protected $configPath;
    protected $appPath;
    protected $storagePath;
    protected $assetsPath;
    protected $mayaPath;
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
        foreach (glob(basePath('/Routes/*.php')) as $path) {
            if ($path == basePath('/Routes/console.php')) {
                continue;
            }
            require_once($path);
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
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError($error): void
    {
        $this->errorHandler->handle($error);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null) {
            $this->errorHandler->handle($error);
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
}
