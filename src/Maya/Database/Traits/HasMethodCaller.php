<?php

namespace Maya\Database\Traits;

use BadMethodCallException;

trait HasMethodCaller
{

    private $allowedMethods = [
        'increment',
        'decrement',
        'create',
        'update',
        'delete',
        'all',
        'find',
        'findFrom',
        'where',
        'orWhere',
        'whereNull',
        'orWhereNull',
        'whereNotNull',
        'orWhereNotNull',
        'whereIn',
        'orWhereIn',
        'whereBetween',
        'orWhereBetween',
        'whereRaw',
        'orWhereRaw',
        'orderBy',
        'oldest',
        'latest',
        'limit',
        'get',
        'count',
        'first',
        'last',
        'save',
        'restore',
        'restoreAll',
        'restoreFrom',
        'forceDelete',
        'forceDeleteAll',
        'forceDeleteFrom',
        'withTrashed',
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
        if (in_array($method, $this->allowedMethods)) {
            return call_user_func_array(array($object, $method . 'Method'), $args);
        } else {
            throw new BadMethodCallException("The method '{$method}' is not allowed or does not exist.");
        }
    }

    protected function setAllowedMethods($array): void
    {
        $this->allowedMethods = $array;
    }
}
