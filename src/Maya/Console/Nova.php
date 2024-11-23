<?php

namespace Maya\Console;

use Maya\Console\ArgHandler;

class Nova
{
    protected static $beforeHooks = [];
    protected static $afterHooks = [];
    protected static $commands = [];
    protected static $aliases = [];
    protected static $logFile = 'Storage/Logs/Maya/Nova.log';
    protected static $maxLogs = 100;
    protected static $notifications = [];

    public static function systemCommands()
    {
        require 'systemCommands.php';
    }

    public static function init()
    {
        self::initLogging();
    }

    protected static function initLogging()
    {
        if (!file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, "");
        }
    }

    public static function command(string $commandSignature, string|callable $action = null): null|CommandBuilder
    {
        if (is_string($commandSignature) && class_exists($commandSignature)) {
            $commandClass = new $commandSignature;
            CommandBuilder::create($commandClass->signature, function (ArgHandler $request) use ($commandClass) {
                $commandClass->handle($request, new Iris);
            })->description($commandClass->description ?? '')->alias($commandClass->alias ?? []);
            return null;
        }
        return CommandBuilder::create($commandSignature, $action ?? function () {});
    }

    public static function handle($argv)
    {
        self::init();

        if (count($argv) < 2) {
            self::showHelp();
            return;
        }

        $command = $argv[1] ?? null;
        $inputArgs = array_slice($argv, 2);

        if (isset(self::$aliases[$command])) {
            $command = self::$aliases[$command];
        }

        if ($command && isset(self::$commands[$command])) {
            $commandInfo = self::$commands[$command];
            $parsedArgs = self::parseArgs($commandInfo['arguments'], $inputArgs);

            if ($parsedArgs === false) {
                Iris::simple()->error("Invalid arguments provided.");
                return;
            }

            self::executeHooks('before', $command, $parsedArgs);
            $commandInfo['callback'](new ArgHandler($parsedArgs), new Iris);
            self::executeHooks('after', $command, $parsedArgs);

            self::log("Executed command: $command with args: " . json_encode($parsedArgs));
            self::notifyUser($command, $parsedArgs);
        } else {
            self::suggestSimilarCommands($command);
        }
    }

    protected static function parseSignature(array $parts)
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

    protected static function parseArgs(array $expectedArgs, array $inputArgs)
    {
        $parsed = [
            'args' => [],
            'options' => [],
        ];

        if (count($inputArgs) < count($expectedArgs['required'])) {
            $interactiveArgs = self::interactiveMode($expectedArgs);

            foreach ($expectedArgs['required'] as $argName) {
                $parsed['args'][$argName] = $interactiveArgs[$argName];
            }

            foreach ($expectedArgs['optional'] as $argName) {
                $parsed['args'][$argName] = $interactiveArgs[$argName];
            }
        } else {
            foreach ($expectedArgs['required'] as $index => $argName) {
                $parsed['args'][$argName] = $inputArgs[$index];
            }

            $optionalStartIndex = count($expectedArgs['required']);
            foreach ($expectedArgs['optional'] as $index => $argName) {
                $parsed['args'][$argName] = $inputArgs[$optionalStartIndex + $index] ?? null;
            }
        }

        foreach (array_slice($inputArgs, count($expectedArgs['required']) + count($expectedArgs['optional'])) as $arg) {
            if (strpos($arg, '--') === 0) {
                $option = explode('=', substr($arg, 2), 2);
                $parsed['options'][$option[0]] = $option[1] ?? true;
            }
        }

        return $parsed;
    }

    protected static function interactiveMode($expectedArgs)
    {
        $parsed = [];

        foreach ($expectedArgs['required'] as $argName) {
            do {
                echo "Enter value for $argName: ";
                $input = trim(fgets(STDIN));
                if (empty($input)) {
                    Iris::error("Value for $argName is required.");
                }
            } while (empty($input));
            $parsed[$argName] = $input;
        }

        foreach ($expectedArgs['optional'] as $argName) {
            echo "Enter value for $argName (optional): ";
            $input = trim(fgets(STDIN));
            $parsed[$argName] = $input !== '' ? $input : null;
        }

        return $parsed;
    }

    public static function addHook(string $type, string $command, callable $callback)
    {
        if ($type === 'before') {
            self::$beforeHooks[$command][] = $callback;
        } elseif ($type === 'after') {
            self::$afterHooks[$command][] = $callback;
        }
    }

    protected static function executeHooks(string $type, string $command, array $parsedArgs)
    {
        $hooks = $type === 'before' ? self::$beforeHooks[$command] ?? [] : self::$afterHooks[$command] ?? [];

        foreach ($hooks as $hook) {
            call_user_func($hook, $parsedArgs);
        }
    }

    protected static function notifyUser($command, $args)
    {
        self::$notifications[] = "Executed '$command' with arguments: " . json_encode($args);
    }

    public static function showNotifications()
    {
        foreach (self::$notifications as $notification) {
            Iris::simple()->info($notification);
        }
    }

    protected static function log($message)
    {
        $time = date('Y-m-d H:i:s');
        $logEntry = "[$time] $message\n";

        $logs = file(self::$logFile);

        if (count($logs) >= self::$maxLogs) {
            $logs = array_slice($logs, -self::$maxLogs + 1);
        }

        $logs[] = $logEntry;

        file_put_contents(self::$logFile, implode("", $logs));
    }

    public static function showHelp()
    {
        Iris::simple()->info("Available commands:");
        $commands = [];
        foreach (self::$commands as $name => $details) {
            $aliases = !empty($details['alias']) ? ' (aliases: ' . implode(', ', $details['alias']) . ')' : '';
            $commands[] = "  " . Iris::CYAN . "$name" . Iris::RESET . ": {$details['description']}{$aliases}";
        }
        echo Iris::unorderedList($commands);
    }

    protected static function suggestSimilarCommands($inputCommand)
    {
        Iris::error("Command '$inputCommand' not found.");
        Iris::br();
        $similarCommands = [];

        foreach (array_keys(self::$commands) as $command) {
            if (levenshtein($inputCommand, $command) <= 3) {
                $similarCommands[] = $command;
            }
        }

        if (!empty($similarCommands)) {
            Iris::simple()->info("Did you mean?");
            foreach ($similarCommands as $suggestion) {
                Iris::simple()->info("  " . Iris::CYAN . $suggestion . Iris::RESET);
            }
        }
        Iris::br();
    }

    public static function executionTime()
    {
        $endTime = microtime(true);
        $executionTime = $endTime - DAVINCI_START;
        return number_format($executionTime, 4) . 's';
    }

    public static function registerCommand(string $commandName, callable $callback, array $arguments, ?string $description = '', array $alias = [])
    {
        self::$commands[$commandName] = [
            'callback' => $callback,
            'arguments' => $arguments,
            'description' => $description,
            'alias' => $alias,
        ];

        foreach ($alias as $a) {
            self::$aliases[$a] = $commandName;
        }
    }

    public static function logPerformance()
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $executionTime = self::executionTime();
        self::log("Performance: Memory used: " . number_format($memoryUsage, 2) . " MB, Execution time: $executionTime seconds");
    }

    public static function getPerformance()
    {
        return [(memory_get_usage(true) / 1024 / 1024) . "MB", self::executionTime()];
    }

    public static function clearLogs()
    {
        file_put_contents(self::$logFile, "");
    }

    public static function showLogs($type = null)
    {
        $logs = file(self::$logFile);
        foreach ($logs as $log) {
            if ($type === null || strpos($log, strtoupper($type)) !== false) {
                echo $log;
            }
        }
    }

    public static function getCommands()
    {
        return self::$commands;
    }
}
