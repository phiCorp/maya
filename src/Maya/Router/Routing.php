<?php

namespace Maya\Router;

use Exception;
use Maya\Http\Middleware\MiddlewareCore;
use Maya\Http\Redirect;
use Maya\Http\Request;
use Maya\Router\Web\Route;

class Routing
{
    private array $current_route;
    private string $method_field;
    private array $routes;
    private $request;
    private array $values = [];

    public function __construct()
    {
        $this->request = request();
        $this->current_route = $this->current_route();
        $this->method_field = $this->methodField();
        $this->routes = Route::getRoutes();
    }

    public function run(): void
    {
        $match = $this->match();
        if (empty($match)) {
            $this->error404();
        }

        $middlewareCore = new MiddlewareCore();
        foreach ($match['middlewares'] as $middleware) {
            $middlewareCore->add(new $middleware);
        }
        $middlewareRunner = function (Request $request) use ($match) {
            if ($match['class'] === null) {
                $this->callMethod($match['method']);
            } else {
                $this->callControllerMethod($match['class'], $match['method']);
            }
        };
        $middlewareCore->handle(new Request, $middlewareRunner);
    }

    private function match(): array
    {
        $reservedRoutes = $this->routes[$this->method_field];
        foreach ($reservedRoutes as $reservedRoute) {
            if ($this->compare($reservedRoute['uri'], $reservedRoute['wheres'])) {
                return [
                    "class" => $reservedRoute['action']['controller'],
                    "method" => $reservedRoute['action']['method'],
                    "middlewares" => $reservedRoute['middleware'] ?? []
                ];
            } else {
                $this->values = [];
            }
        }
        return [];
    }

    private function compare($reservedRouteUrl, $wheres): bool
    {
        if (trim($reservedRouteUrl, " /") === '') {
            return trim($this->current_route[0], " /") === '';
        }
        $reservedRouteUrlArray = explode("/", $reservedRouteUrl);
        if (count($this->current_route) > count($reservedRouteUrlArray)) {
            return false;
        }
        foreach ($reservedRouteUrlArray as $key => $reservedRouteUrlElement) {
            $currentRouteElement = $this->current_route[$key] ?? null;

            if (str_starts_with($reservedRouteUrlElement, "{") && str_ends_with($reservedRouteUrlElement, "}")) {
                $parameterName = trim($reservedRouteUrlElement, "{}?");
                $isOptional = str_ends_with($reservedRouteUrlElement, "?}");
                if (!$isOptional && ($currentRouteElement === null || empty($currentRouteElement))) {
                    return false;
                }

                if ($currentRouteElement !== null) {
                    if (isset($wheres[$parameterName])) {
                        $regexPattern = $wheres[$parameterName];
                        if (!preg_match("/$regexPattern/", $currentRouteElement)) {
                            return false;
                        }
                    }
                    $this->values[$parameterName] = $currentRouteElement;
                }
            } elseif ($reservedRouteUrlElement != $currentRouteElement) {
                return false;
            }
        }
        return true;
    }

    private function callMethod($method)
    {
        $this->handleResponse(app()->call($method, $this->values));
    }

    private function callControllerMethod($class, $method)
    {
        $classPath = trim(str_replace("\\", "/", $class), '/');
        if (!file_exists(basePath("$classPath.php"))) {
            throw new Exception("controller {$classPath} not found");
        }
        $object = new $class();
        if (method_exists($object, $method)) {
            $this->handleResponse(app()->call([$object, $method], $this->values));
        } else {
            throw new Exception("method {$method} not found");
        }
    }

    private function error404(): void
    {
        abort(404, 'Page Not Found.');
        app()->terminate();
    }
    private function current_route()
    {
        return explode("/", trim(explode("?", $this->request->server('REQUEST_URI') ?? '')[0], ' /'));
    }
    private function methodField(): string
    {
        return $this->request->method();
    }

    public function getCurrentRouteName(): string
    {
        $currentRouteUrl = implode('/', $this->current_route);
        $currentRouteMethod = $this->method_field;
        foreach ($this->routes[$currentRouteMethod] as $reservedRoute) {
            if ($this->compare($reservedRoute['uri'], $reservedRoute['wheres']) && $this->compare($currentRouteUrl, [])) {
                return $reservedRoute['name'];
            }
        }

        return '';
    }

    public function getCurrentRouteParameters(): array
    {
        $currentRouteUrl = implode('/', $this->current_route);
        $currentRouteMethod = $this->method_field;

        foreach ($this->routes[$currentRouteMethod] as $reservedRoute) {
            if ($this->compare($reservedRoute['uri'], $reservedRoute['wheres']) && $this->compare($currentRouteUrl, [])) {
                $reservedRouteUrlArray = explode('/', $reservedRoute['uri']);
                $parameters = [];

                foreach ($reservedRouteUrlArray as $key => $reservedRouteUrlElement) {
                    if (str_starts_with($reservedRouteUrlElement, "{") && str_ends_with($reservedRouteUrlElement, "}")) {
                        $parameterName = trim($reservedRouteUrlElement, "{}");
                        $parameters[$parameterName] = $this->values[$key];
                    }
                }

                return $parameters;
            }
        }

        return [];
    }

    private function handleResponse($result)
    {
        if ($result instanceof Redirect) {
            $result->exec();
        }
        if (is_array($result) || is_object($result)) {
            response()->json($result, 200, options: JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        if (is_string($result) || is_numeric($result)) {
            echo $result;
        }
    }
}
