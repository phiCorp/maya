<?php

namespace Maya\Console;

class CommandBuilder
{
    private static array $commands = [];
    private string $command;
    private array $arguments = [
        'required' => [],
        'optional' => [],
    ];
    private ?string $description = null;
    private ?array $alias = [];
    private $callback;

    private function __construct(string $commandSignature, callable $callback)
    {
        $parts = explode(' ', $commandSignature);
        $this->command = array_shift($parts);
        $this->callback = $callback;
        $this->arguments = $this->parseSignature($parts);
        $this->register();
    }

    public static function create(string $commandSignature, callable $callback): self
    {
        return new self($commandSignature, $callback);
    }

    public function alias(array $alias): self
    {
        $this->alias = $alias;
        $this->register();
        return $this;
    }

    public function before(callable $callback): self
    {
        Nova::addHook('before', $this->command, $callback);
        return $this;
    }
    
    public function after(callable $callback): self
    {
        Nova::addHook('after', $this->command, $callback);
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        $this->register();
        return $this;
    }

    protected function parseSignature(array $parts): array
    {
        $arguments = [
            'required' => [],
            'optional' => [],
        ];

        foreach ($parts as $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                if (strpos($part, '?') === strlen($part) - 2) {
                    $arguments['optional'][] = trim($part, '{}?');
                } else {
                    $arguments['required'][] = trim($part, '{}');
                }
            }
        }

        return $arguments;
    }

    public function action(string|callable $callback): self
    {
        $this->callback = $callback;
        $this->register();
        return $this;
    }

    private function register(): void
    {
        Nova::registerCommand($this->command, $this->callback, $this->arguments, $this->description, $this->alias);
    }

    public static function getCommands(): array
    {
        return self::$commands;
    }
}
