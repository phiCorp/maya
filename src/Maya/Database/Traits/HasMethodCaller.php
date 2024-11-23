<?php

namespace Maya\Database\Traits;

trait HasMethodCaller
{

    private $allMethods = [
        'increment',
        'decrement',
        'create',
        'update',
        'delete',
        'find',
        'all',
        'save',
        'where',
        'orWhere',
        'whereIn',
        'whereNull',
        'whereNotNull',
        'limit',
        'orderBy',
        'oldest',
        'latest',
        'first',
        'last',
        'get',
        'orWhereNull',
        'orWhereNotNull',
        'orWhereIn',
        'whereBetween',
        'orWhereBetween',
        'whereRaw',
        'orWhereRaw'
    ];
    private $allowedMethods = [
        'increment',
        'decrement',
        'create',
        'update',
        'delete',
        'find',
        'all',
        'save',
        'where',
        'orWhere',
        'whereIn',
        'whereNull',
        'whereNotNull',
        'limit',
        'orderBy',
        'oldest',
        'latest',
        'first',
        'last',
        'get',
        'orWhereNull',
        'orWhereNotNull',
        'orWhereIn',
        'whereBetween',
        'orWhereBetween',
        'whereRaw',
        'orWhereRaw'
    ];

    public function __call($method, $args)
    {
        return $this->methodCaller($this, $method, $args);
    }

    public static function __callStatic($method, $args)
    {
        $className = get_called_class();
        $instance = new $className;
        return $instance->methodCaller($instance, $method, $args);
    }

    private function methodCaller($object, $method, $args)
    {
        $methodName = $method . 'Method';
        if (in_array($method, $this->allowedMethods)) {
            return call_user_func_array(array($object, $methodName), $args);
        }
    }
    protected function setAllowedMethods($array): void
    {
        $this->allowedMethods = $array;
    }
}
