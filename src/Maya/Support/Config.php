<?php

namespace Maya\Support;

class Config
{
    private array $items = [];

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        $this->items = require configPath();
    }

    public function set(string $key, $value): void
    {
        arr()->set($this->items, $key, $value);
    }

    public function get(array|string $key = null, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }
        return arr()->get($this->items, $key, $default);
    }

    private function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }
            $config[$key] = arr()->get($this->items, $key, $default);
        }

        return $config;
    }

    public function remove(string $key): void
    {
        arr()->forget($this->items, $key);
    }

    public function hasKey(string $key): bool
    {
        return arr()->has($this->items, $key);
    }
}
