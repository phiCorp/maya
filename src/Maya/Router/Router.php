<?php

namespace Maya\Router;

class Router
{
    private function __construct() {}

    private static array $groupStack = [];
    private static array $routes = [];
    private ?string $name = null;
    private ?string $method = null;
    private ?string $uri = null;
    private $action = null;
    private array $wheres = [];
    private array $middleware = [];
    private array $prefix = [];

    public static function setStack(array $attributes): void
    {
        static::$groupStack[] = $attributes;
    }

    public static function popStack(): void
    {
        array_pop(static::$groupStack);
    }

    public static function create(): static
    {
        $route = new static;
        static::$routes[] = $route;
        if (!empty(static::$groupStack)) {
            $route->applyGroup(static::$groupStack);
        }
        return $route;
    }

    public function applyGroup(array $groupStack): void
    {
        foreach ($groupStack as $group) {
            if (isset($group['prefix'])) {
                $this->prefix($group['prefix']);
            }
            if (isset($group['where']) && is_array($group['where'])) {
                $this->where($group['where']);
            }
            if (isset($group['middleware'])) {
                $this->middleware($group['middleware']);
            }
            if (isset($group['name'])) {
                $this->name = trim($this->name ?? '') . trim($group['name'] ?? '');
            }
        }
    }

    public function addRoute($method, $uri, $action): static
    {
        $method = strtoupper($method);
        $this->method = $method;
        $this->uri = $uri ? trim($uri, ' /') : null;
        $this->action = $action;
        return $this;
    }

    public function get(string $uri, callable|array|string|null $action = NULL): static
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, callable|array|string|null $action = NULL): static
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, callable|array|string|null $action = NULL): static
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, callable|array|string|null $action = NULL): static
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function name(string $name): static
    {
        $this->name .= trim($name);
        return $this;
    }

    public function where(string|array $parameter, string|null $pattern = NULL): static
    {
        if (!is_array($parameter)) {
            $this->wheres[$parameter] = $pattern;
        } else {
            $this->wheres = array_filter(array_unique(array_merge($this->wheres ?? [], $parameter)));
        }
        return $this;
    }

    public function whereAlpha(string $parameter): static
    {
        return $this->where($parameter, '^[A-Za-z]+$');
    }

    public function whereNumber(string $parameter): static
    {
        return $this->where($parameter, '^[0-9]+$');
    }

    public function whereAlphaNumeric(string $parameter): static
    {
        return $this->where($parameter, '^[A-Za-z0-9]+$');
    }

    public function middleware(string|array $middleware): static
    {
        if (!is_array($middleware)) {
            $middleware = [trim($middleware)];
        }
        foreach ($middleware as $value) {
            $this->addToArray(trim($value), $this->middleware, true);
        }
        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->addToArray(trim($prefix, '/ '), $this->prefix);
        return $this;
    }

    private function addToArray($value, array &$array, $unique = false): void
    {
        if ($unique) {
            if (!in_array($value, $array)) {
                $array[] = $value;
            }
        } else {
            $array[] = $value;
        }
    }

    public static function getRoutes(): array
    {
        $routes = [
            'GET' => [],
            'POST' => [],
            'DELETE' => [],
            'PUT' => [],
        ];
        $instance = new static;
        foreach (static::$routes as $route) {
            $routes[$route->method][] = $instance->compileRoutes($route);
        }
        return $routes;
    }

    private function compileRoutes($route): array
    {
        return [
            'uri' => $this->compileUri($route),
            'action' => $this->compileAction($route),
            'name' => $route->name,
            'wheres' => count($route->wheres) > 0 ? $route->wheres : null,
            'middleware' => count($route->middleware) > 0 ? $route->middleware : null
        ];
    }

    private function compileAction($route): ?array
    {
        if (is_callable($route->action)) {
            return ['controller' => null, 'method' => $route->action];
        }
        if (is_string($route->action)) {
            $actionArray = explode('@', $route->action);
            $actionArray[0] = $actionArray[0];
            return ['controller' => $actionArray[0], 'method' => $actionArray[1] ?? 'index'];
        }
        if (is_array($route->action) && $route->action) {
            return ['controller' => $route->action[0], 'method' => $route->action[1] ?? 'index'];
        }
        return null;
    }

    private function compileUri($route)
    {
        return isset($route->prefix) ? trim(implode('/', $route->prefix) . '/' . $route->uri, ' /') : $route->uri;
    }
}
