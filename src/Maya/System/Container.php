<?php

namespace Maya\System;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Class Container
 * 
 * dependency injection container.
 */
class Container
{
    /** @var array Holds the registered bindings */
    private $bindings = [];

    /** @var array Holds the registered aliases */
    private $aliases = [];

    /** @var static The current globally available container (if any).*/
    protected static $instance;

    /** @var array Holds the instances of resolved bindings */
    private $instances = [];



    /**
     * Bind an abstract class or interface to a concrete implementation.
     *
     * @param string $abstract The abstract class or interface
     * @param mixed $concrete The concrete implementation or null to use the abstract as concrete
     * @param bool $shared Whether the binding should be shared as a singleton
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Bind an abstract class or interface only if it has not been bound before.
     *
     * @param string $abstract The abstract class or interface
     * @param mixed $concrete The concrete implementation or null to use the abstract as concrete
     * @param bool $shared Whether the binding should be shared as a singleton
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (!isset($this->bindings[$abstract])) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Create an alias for a binding.
     *
     * @param string $abstract The abstract class or interface
     * @param string $alias The alias to be created
     */
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract The abstract class or interface
     * @param mixed $concrete The concrete implementation or null to use the abstract as concrete
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding only if it has not been bound before.
     *
     * @param string $abstract The abstract class or interface
     * @param mixed $concrete The concrete implementation or null to use the abstract as concrete
     */
    public function singletonIf($abstract, $concrete = null)
    {
        $this->bindIf($abstract, $concrete, true);
    }

    /**
     * Flush all bindings and aliases from the container.
     */
    public function flush()
    {
        $this->bindings = [];
        $this->aliases = [];
        $this->instances = [];
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->singleton($abstract, function () use ($instance) {
            return $instance;
        });
    }


    /**
     * Call a callback with resolved dependencies.
     *
     * @param mixed $callback The callback to be called
     * @param array $parameters Additional parameters to be passed to the callback
     * @param string|null $defaultMethod The default method to be called if $callback is a string
     * @return mixed The result of the callback
     */
    public function call($callback, $parameters = [], $defaultMethod = null)
    {
        if (is_string($callback) && strpos($callback, '@') !== false) {
            list($class, $method) = explode('@', $callback);
            $callback = [$this->make($class), $method];
        }
        if (is_array($callback) && is_string($callback[0])) {
            $callback[0] = $this->make($callback[0]);
        }
        return $this->resolveCallback($callback, $parameters, $defaultMethod);
    }

    /**
     * Resolve an abstract class or interface from the container.
     *
     * @param string $abstract The abstract class or interface
     * @param array $parameters Additional parameters to be passed to the concrete implementation
     * @return mixed The resolved instance
     * @throws Exception if the binding is not found
     */
    public function make($abstract, $parameters = [])
    {
        // Check if the abstract is an alias and replace it with the actual binding
        $abstract = $this->getAlias($abstract);

        if (isset($this->bindings[$abstract])) {
            if ($this->bindings[$abstract]['shared'] && isset($this->instances[$abstract])) {
                return $this->instances[$abstract];
            }

            $concrete = $this->bindings[$abstract]['concrete'];

            // Check if $concrete is a Closure
            if ($concrete instanceof Closure) {
                return $concrete(...$parameters);
            }

            $instance = $this->build($concrete, $parameters);

            if ($this->bindings[$abstract]['shared']) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        throw new Exception("Binding for $abstract not found.");
    }

    /**
     * Get the alias for an abstract class or interface.
     *
     * @param string $abstract The abstract class or interface
     * @return string The actual binding or alias
     */
    protected function getAlias($abstract)
    {
        return isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;
    }

    /**
     * Build the instance of the concrete implementation with dependencies.
     *
     * @param string $concrete The concrete implementation class name
     * @param array $parameters Additional parameters to be passed to the concrete implementation
     * @return mixed The built instance
     * @throws Exception if the class is not instantiable
     */
    protected function build($concrete, $parameters)
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class $concrete is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $this->getDependencies($constructor, $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Get the resolved dependencies for a constructor or method.
     *
     * @param ReflectionMethod $method The reflection of the constructor or method
     * @param array $parameters Additional parameters to be passed to the constructor or method
     * @return array The resolved dependencies
     * @throws Exception if a dependency cannot be resolved
     */
    protected function getDependencies($constructor, $parameters)
    {
        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            if (array_key_exists($parameter->name, $parameters)) {
                $dependencies[] = $parameters[$parameter->name];
            } elseif ($parameter->getType() && !$parameter->getType()->isBuiltin()) {
                $dependencies[] = $this->make($parameter->getType()->getName());
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Unable to resolve dependency: " . $parameter->name);
                }
            }
        }

        return $dependencies;
    }

    /**
     * Resolve a callback with its dependencies.
     *
     * @param mixed $callback The callback to be resolved
     * @param array $parameters Additional parameters to be passed to the callback
     * @param string|null $defaultMethod The default method to be called if $callback is a string
     * @return mixed The result of the resolved callback
     * @throws Exception if the method to be called cannot be determined
     */
    protected function resolveCallback($callback, $parameters, $defaultMethod = null)
    {
        if ($callback instanceof Closure) {
            return $this->resolveClosure($callback, $parameters);
        }

        $reflector = $this->getMethodReflector($callback, $defaultMethod);

        $dependencies = $this->getDependencies($reflector, $parameters);

        return $reflector->invokeArgs(is_array($callback) ? $callback[0] : null, $dependencies);
    }

    /**
     * Get the reflection of the method to be called.
     *
     * @param mixed $callback The callback whose method is to be reflected
     * @param string|null $defaultMethod The default method to be called if $callback is a string
     * @return ReflectionMethod The reflection of the method
     * @throws Exception if the method to be called cannot be determined
     */
    protected function getMethodReflector($callback, $defaultMethod)
    {
        if (is_array($callback) && isset($callback[1])) {
            $method = $callback[1];
        } elseif (is_string($callback) && $defaultMethod && method_exists($callback, $defaultMethod)) {
            $method = $defaultMethod;
        } else {
            throw new Exception('Unable to determine the method to be called. Callback: ' . print_r($callback, true));
        }

        return new ReflectionMethod($callback[0], $method);
    }

    /**
     * Resolve a Closure with its dependencies.
     *
     * @param Closure $closure The Closure to be resolved
     * @param array $parameters Additional parameters to be passed to the Closure
     * @return mixed The result of the resolved Closure
     */
    protected function resolveClosure(Closure $closure, $parameters)
    {
        $reflector = new ReflectionFunction($closure);

        $dependencies = $this->getDependencies($reflector, $parameters);

        return $reflector->invokeArgs($dependencies);
    }


    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param Container|null $container The container instance to be set as shared
     * @return static
     */
    public static function setInstance($container = null)
    {
        return static::$instance = $container;
    }
}
