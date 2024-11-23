<?php

namespace Maya\View;

class Composer
{
    private static $instance;
    private $vars = [];
    private $viewArray = [];
    private $registeredViewArray = [];

    private function __construct()
    {
    }

    private function registerView(string|array $names, $callback): void
    {
        if (is_string($names)) {
            $names = [$names];
        }
        foreach ($names as $name) {
            $this->registeredViewArray[$name] = $callback;
        }
    }

    private function setViewArray($viewArray): void
    {
        $this->viewArray = $viewArray;
    }

    private function getViewVars(): array
    {
        foreach ($this->viewArray as $viewName) {
            if (isset($this->registeredViewArray[str_replace('/', '.', $viewName)])) {
                $callback = $this->registeredViewArray[str_replace('/', '.', $viewName)];
                $viewVars = $callback();
                foreach ($viewVars as $key => $value) {
                    $this->vars[$key] = $value;
                }
            }
        }
        if (isset($this->registeredViewArray['*'])) {
            $callback = $this->registeredViewArray['*'];
            $viewVars = $callback();
            foreach ($viewVars as $key => $value) {
                $this->vars[$key] = $value;
            }
        }
        return $this->vars;
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = self::getInstance();
        switch ($name) {
            case "view":
                return call_user_func_array(array($instance, "registerView"), $arguments);
            case "setViews":
                return call_user_func_array(array($instance, "setViewArray"), $arguments);
            case "getVars":
                return call_user_func_array(array($instance, "getViewVars"), $arguments);
        }
    }

    private static function getInstance(): Composer
    {
        if (empty(self::$instance))
            self::$instance = new self;
        return self::$instance;
    }
}
