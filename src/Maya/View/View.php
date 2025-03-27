<?php

namespace Maya\View;

class View
{
    public static function make(string $dir, array $data = [], int $httpStatus = 200, array $httpHeaders = []): void
    {
        http_response_code($httpStatus);
        foreach ($httpHeaders as $name => $values) {
            header($name . ': ' . $values);
        }

        $compiledPath = self::getCompiledPath($dir);
        $viewPath = self::getViewPath($dir);
        $cacheEnabled = config('VIEW.CACHE', true);

        if (!$cacheEnabled || !file_exists($compiledPath) || filemtime($compiledPath) < filemtime($viewPath)) {
            $viewBuilder = new ViewBuilder();
            $viewBuilder->run($dir);

            if (!is_dir(dirname($compiledPath))) {
                mkdir(dirname($compiledPath), 0755, true);
            }

            file_put_contents($compiledPath, html($viewBuilder->content));
        }
        extract(Composer::getVars());
        extract($data);
        require_once($compiledPath);
    }


    public static function exists(string $dir): bool
    {

        $viewFile = config('VIEW.VIEW_DIRECTORY') . DIRECTORY_SEPARATOR . $dir . config('VIEW.VIEW_FILE_EXTENSION');
        return file_exists($viewFile);
    }

    private static function getViewPath($viewName): string
    {
        $dir = trim($viewName, " ./");
        $dir = str_replace(".", "/", $dir);
        return config('VIEW.VIEW_DIRECTORY') . DIRECTORY_SEPARATOR . $dir . config('VIEW.VIEW_FILE_EXTENSION');
    }

    private static function getCompiledPath($viewName): string
    {
        $cacheDir = config('VIEW.VIEW_COMPILED_PATH');
        return $cacheDir . DIRECTORY_SEPARATOR . md5($viewName) . '.php';
    }
}