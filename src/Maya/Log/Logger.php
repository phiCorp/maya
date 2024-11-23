<?php

namespace Maya\Log;

use Exception;

class Logger
{
    protected static $config;
    protected static $currentChannel;

    protected static function loadConfig()
    {
        if (self::$config === null) {
            self::$config = config('LOGGER');
            self::$currentChannel = self::$config['DEFAULT'] ?? 'SINGLE';
        }
    }

    public static function channel($channel)
    {
        self::loadConfig();

        if (isset(self::$config['CHANNELS'][$channel])) {
            self::$currentChannel = $channel;
        } else {
            throw new Exception("Channel $channel does not exist.");
        }

        return new static;
    }

    public static function multiChannel(array $channels)
    {
        self::loadConfig();
        foreach ($channels as $channel) {
            if (!isset(self::$config['CHANNELS'][$channel])) {
                throw new Exception("Channel $channel does not exist.");
            }
        }
        self::$currentChannel = $channels;
        return new static;
    }

    protected static function writeLog($level, $message, array $context = [], array $extra = [])
    {
        self::loadConfig();

        $channels = is_array(self::$currentChannel) ? self::$currentChannel : [self::$currentChannel];
        foreach ($channels as $channel) {
            $channelConfig = self::$config['CHANNELS'][$channel];
            $allowedLevels = self::getAllowedLevels($channelConfig['LEVEL'] ?? 'debug');

            if (!in_array($level, $allowedLevels)) {
                continue;
            }

            $filePath = self::getFilePath($channelConfig, $channel);
            $logMessage = self::formatMessage($level, $message, $context, $extra);

            file_put_contents($filePath, $logMessage, FILE_APPEND | LOCK_EX);
            self::rotateFiles($filePath, $channelConfig['MAX_FILES'] ?? 7);
        }
        self::$currentChannel = self::$config['DEFAULT'] ?? 'SINGLE';
    }

    protected static function getAllowedLevels($configLevel)
    {
        $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        $index = array_search($configLevel, $levels);
        return $index !== false ? array_slice($levels, $index) : $levels;
    }

    protected static function getFilePath($channelConfig, $channel)
    {
        $basePath = $channelConfig['PATH'];

        if ($channel === 'DAILY') {
            if (!is_dir($basePath)) {
                mkdir($basePath, 0777, true);
                chmod($basePath, 0777);
            }
            return $basePath . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
        } else {
            $directory = dirname($basePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
                chmod($directory, 0777);
            }
            if (!file_exists($basePath)) {
                file_put_contents($basePath, '');
                chmod($basePath, 0666);
            }
            return $basePath;
        }
    }

    protected static function formatMessage($level, $message, array $context = [], array $extra = [])
    {
        $format = self::$config['FORMAT'] ?? '[{timestamp}] {level}: {message} {context} {extra} [RequestID: {request_id}]';

        $contextData = !empty($context) ? 'Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $extraData = !empty($extra) ? 'Extra: ' . json_encode($extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

        return strtr($format, [
            '{timestamp}' => date('Y-m-d H:i:s'),
            '{level}' => strtoupper($level),
            '{message}' => $message,
            '{context}' => $contextData,
            '{extra}' => $extraData,
            '{request_id}' => uniqid()
        ]) . PHP_EOL;
    }



    protected static function rotateFiles($filePath, $maxFiles)
    {
        if (self::$currentChannel === 'DAILY') {
            $files = glob(dirname($filePath) . '/*.log');
            $oldFiles = array_slice($files, 0, count($files) - $maxFiles);
            foreach ($oldFiles as $file) {
                $gzFile = $file . '.gz';
                if (file_exists($gzFile)) unlink($gzFile);
                $fp = fopen($file, 'rb');
                $gz = gzopen($gzFile, 'wb9');
                while (!feof($fp)) gzwrite($gz, fread($fp, 4096));
                fclose($fp);
                gzclose($gz);
                unlink($file);
            }
        }
    }

    public static function log($level, $message, array $context = [], array $extra = [])
    {
        self::writeLog($level, $message, $context, $extra);
    }

    public static function emergency($message, array $context = [], array $extra = [])
    {
        self::writeLog('emergency', $message, $context, $extra);
    }

    public static function alert($message, array $context = [], array $extra = [])
    {
        self::writeLog('alert', $message, $context, $extra);
    }

    public static function critical($message, array $context = [], array $extra = [])
    {
        self::writeLog('critical', $message, $context, $extra);
    }

    public static function error($message, array $context = [], array $extra = [])
    {
        self::writeLog('error', $message, $context, $extra);
    }

    public static function warning($message, array $context = [], array $extra = [])
    {
        self::writeLog('warning', $message, $context, $extra);
    }

    public static function notice($message, array $context = [], array $extra = [])
    {
        self::writeLog('notice', $message, $context, $extra);
    }

    public static function info($message, array $context = [], array $extra = [])
    {
        self::writeLog('info', $message, $context, $extra);
    }

    public static function debug($message, array $context = [], array $extra = [])
    {
        self::writeLog('debug', $message, $context, $extra);
    }
}
