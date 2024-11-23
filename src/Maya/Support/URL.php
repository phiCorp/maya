<?php

namespace Maya\Support;

use Exception;
use Maya\Http\Request;
use Maya\Router\Routing;
use Maya\Router\Web\Route;
use Moment\Moment;

class URL
{
    public static function instance()
    {
        return new static();
    }
    public static function current()
    {
        return trim(explode('?', static::full())[0], '/');
    }
    public static function currentDomain()
    {
        return trim(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://' .
            $_SERVER['HTTP_HOST'], '/');
    }
    public static function full()
    {
        return trim(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://' .
            $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'], '/');
    }
    public static function path()
    {
        return $_SERVER['REQUEST_URI'];
    }
    public static function route(string $name, array $params = [], $absolute = true)
    {
        $routes = Route::getRoutes();
        $allRoutes = array_merge($routes['GET'], $routes['POST'], $routes['PUT'], $routes['DELETE']);
        $route = null;
        foreach ($allRoutes as $r) {
            if ($r['name'] == $name && $r['name'] !== null) {
                $route = $r['uri'];
                break;
            }
        }
        if ($route === null) {
            throw new Exception('route not found');
        }
        $routeParamsMatch = [];
        preg_match_all("/{[^}.]*}/", $route, $routeParamsMatch);
        if (count($routeParamsMatch[0]) > count($params)) {
            throw new Exception('Route parameters are not sufficient');
        }
        foreach ($routeParamsMatch[0] as $routeMatch) {
            $paramValue = array_shift($params);
            $route = str_replace($routeMatch, $paramValue, $route);
        }
        if ($absolute) {
            $url = self::currentDomain() . "/" . trim($route, " /");
        } else {
            $url = '/' . trim($route, " /");
        }
        $queryParameters = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $queryParameters[] = urlencode($key) . ((is_string($value) || is_numeric($value)) && !empty($value) ? '=' . urlencode($value) : '');
            } else {
                $queryParameters[] = urlencode($value);
            }
        }
        if (!empty($queryParameters)) {
            $url .= '?' . implode('&', $queryParameters);
        }
        return $url;
    }
    public static function to($url)
    {
        return currentDomain() . ("/" . trim($url, "/ "));
    }

    public static function hasCorrectSignedRoute(Request $request)
    {
        $route = new Routing;
        $routeName = $route->getCurrentRouteName();
        $parameters = $request->allQuery();
        $expiration = isset($parameters['expiration']) ? $parameters['expiration'] : '';
        $signature = $parameters['signature'];
        unset($parameters['expiration']);
        unset($parameters['signature']);
        $parameters = array_merge($route->getCurrentRouteParameters(), $parameters);
        if ($expiration && $expiration >= now()->timestamp()) {
            $expectedSignature = hash_hmac('sha256', route($routeName, $parameters) . $expiration, config('APP.KEY'));
        } else {
            $expectedSignature = hash_hmac('sha256', route($routeName, $parameters), config('APP.KEY'));
        }
        return hash_equals($signature, $expectedSignature);
    }

    public static function signedRoute($routeName, $parameters = [], Moment|null $expiration = null)
    {
        $url = route($routeName, $parameters);
        if ($expiration !== null) {
            $expiration = (string)$expiration->timestamp();
            $parameters['expiration'] = $expiration;
            $url .= $expiration;
        }
        $signature = hash_hmac('sha256', $url, config('APP.KEY'));
        $parameters['signature'] = $signature;
        return route($routeName, $parameters);
    }
}
