<?php

namespace Maya\Console;

class SimpleMessage
{
    public static function success($message)
    {
        echo Iris::GREEN . "[SUCCESS] " . $message . Iris::RESET . "\n";
    }

    public static function error($message)
    {
        echo Iris::RED . "[ERROR] " . $message . Iris::RESET . "\n";
    }

    public static function warning($message)
    {
        echo Iris::YELLOW . "[WARNING] " . $message . Iris::RESET . "\n";
    }

    public static function info($message)
    {
        echo Iris::CYAN . "[INFO] " . $message . Iris::RESET . "\n";
    }
}
