<?php

namespace Maya\Console;

class ArgHandler
{
    protected $options = [];
    protected $params = [];
    public function __construct($args)
    {
        $this->params = $args['args'];
        $this->options = $args['options'];
    }

    public function __get($name)
    {
        if (!is_null($this->params[$name]) && strlen(trim($this->params[$name])) > 0) {
            return $this->params[$name];
        }
        return null;
    }

    public function options()
    {
        return $this->options;
    }

    public function option($name)
    {
        return $this->options[$name] ?? null;
    }

    public function hasOption($name)
    {
        return isset($this->options[$name]) ? true : false;
    }
}
