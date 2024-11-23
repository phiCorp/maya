<?php

namespace Maya\Router\Web;

use Maya\Router\Router;

class Route
{
    private static function createRouter(string|null $method = 'NULL', string|null $uri = NULL, callable|array|string|null $action = NULL): Router
    {
        return Router::create()->addRoute($method, $uri, $action);
    }

    public static function create(string $method, string $uri, callable|array|string $action): Router
    {
        return static::createRouter($method, $uri, $action);
    }

    public static function get(string $uri, callable|array|string $action): Router
    {
        return static::createRouter('GET', $uri, $action);
    }

    public static function post(string $uri, callable|array|string $action): Router
    {
        return static::createRouter('POST', $uri, $action);
    }

    public static function put(string $uri, callable|array|string $action): Router
    {
        return static::createRouter('PUT', $uri, $action);
    }

    public static function delete(string $uri, callable|array|string $action): Router
    {
        return static::createRouter('DELETE', $uri, $action);
    }

    public static function name(string $name): Router
    {
        return static::createRouter()->name($name);
    }

    public static function prefix(string $prefix): Router
    {
        return static::createRouter()->prefix($prefix);
    }

    public static function middleware(string|array $middleware): Router
    {
        return static::createRouter()->middleware($middleware);
    }

    public static function where(string|array $parameter, string|null $pattern = NULL): Router
    {
        return static::createRouter()->where($parameter, $pattern);
    }

    public static function whereAlpha(string $parameter): Router
    {
        return static::createRouter()->where($parameter, '^[A-Za-z]+$');
    }

    public static function whereNumber(string $parameter): Router
    {
        return static::createRouter()->where($parameter, '^[0-9]+$');
    }

    public static function whereAlphaNumeric(string $parameter): Router
    {
        return static::createRouter()->where($parameter, '^[A-Za-z0-9]+$');
    }

    public static function group(array $attributes, callable $callback): void
    {
        Router::setStack($attributes);
        $callback();
        Router::popStack();
    }

    public static function getRoutes(): array
    {
        return Router::getRoutes();
    }
}
