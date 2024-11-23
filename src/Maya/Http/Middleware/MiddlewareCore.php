<?php

namespace Maya\Http\Middleware;

use Maya\Http\Middleware\Contract\MiddlewareInterface;
use Maya\Http\Request;

class MiddlewareCore
{
    private array $stack = [];

    public function __construct(array $middlewares = [])
    {
        $this->setStack($middlewares);
    }

    public function setStack(array $middlewares): void
    {
        $this->stack = [];
        $this->addMany($middlewares);
    }

    public function add(MiddlewareInterface $middleware): void
    {
        $this->stack[] = $middleware;
    }

    public function addMany(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }

    public function remove(MiddlewareInterface $middleware): void
    {
        $index = array_search($middleware, $this->stack, true);
        if ($index !== false) {
            unset($this->stack[$index]);
            $this->stack = array_values($this->stack);
        }
    }

    public function has(MiddlewareInterface $middleware): bool
    {
        return in_array($middleware, $this->stack, true);
    }

    public function reset(): void
    {
        $this->stack = [];
    }

    public function handle(Request $request, callable $finalHandler)
    {
        $index = 0;
        $runner = function (Request $request) use (&$runner, $finalHandler, &$index) {
            if ($index >= count($this->stack)) {
                return $finalHandler($request);
            }
            $middleware = $this->stack[$index];
            $index++;
            return $middleware->handle($request, $runner);
        };
        return $runner($request);
    }

    public function count(): int
    {
        return count($this->stack);
    }
}
