<?php

namespace Maya\Cache;

use Exception;
use Maya\Cache\Contracts\CacheInterface;

class Cache implements CacheInterface
{
    private static $cacheDir;
    private static $defaultExpiration;
    private static $memoryCache = [];
    private static $maxMemoryCacheSize;
    private static $encryptionKey;
    private static $cleanupInterval;
    private static $enableEncryption;
    private static $enableCompression;
    private static $initialized = false;

    private static function loadConfig()
    {
        if (self::$initialized) {
            return;
        }

        $config = config('CACHE');
        if (!is_array($config)) {
            throw new Exception("Config option not found: {$config}");
        }

        $requiredKeys = [
            'CACHE_DIR',
            'DEFAULT_EXPIRATION',
            'ENCRYPTION_KEY',
            'CLEANUP_INTERVAL',
            'MAX_MEMORY_CACHE_SIZE',
            'ENABLE_ENCRYPTION',
            'ENABLE_COMPRESSION'
        ];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new Exception("Missing configuration key: {$key}");
            }
        }

        self::$cacheDir = rtrim($config['CACHE_DIR'], '/') . '/';
        self::$defaultExpiration = (int)$config['DEFAULT_EXPIRATION'];
        self::$encryptionKey = $config['ENCRYPTION_KEY'];
        self::$cleanupInterval = (int)$config['CLEANUP_INTERVAL'];
        self::$maxMemoryCacheSize = (int)$config['MAX_MEMORY_CACHE_SIZE'];
        self::$enableEncryption = (bool)$config['ENABLE_ENCRYPTION'];
        self::$enableCompression = (bool)$config['ENABLE_COMPRESSION'];

        if (!is_dir(self::$cacheDir)) {
            if (!mkdir(self::$cacheDir, 0777, true) && !is_dir(self::$cacheDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', self::$cacheDir));
            }
        }

        self::scheduleCleanup();
        self::$initialized = true;
    }

    private static function compress($data)
    {
        return self::$enableCompression ? gzcompress(serialize($data)) : serialize($data);
    }

    private static function decompress($data)
    {
        return self::$enableCompression ? unserialize(gzuncompress($data)) : unserialize($data);
    }

    private static function encrypt($data)
    {
        if (!self::$enableEncryption) {
            return $data;
        }
        $iv = substr(hash('sha256', self::$encryptionKey), 0, 16);
        return openssl_encrypt($data, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
    }

    private static function decrypt($data)
    {
        if (!self::$enableEncryption) {
            return $data;
        }
        $iv = substr(hash('sha256', self::$encryptionKey), 0, 16);
        return openssl_decrypt($data, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
    }

    public static function set($key, $data, $expiration = null, $tags = [], $encrypt = null, $compress = null)
    {
        self::loadConfig();

        $expiration = $expiration ?: self::$defaultExpiration;

        $useEncryption = $encrypt !== null ? $encrypt : self::$enableEncryption;
        $useCompression = $compress !== null ? $compress : self::$enableCompression;

        $serializedData = serialize($data);
        $cacheData = [
            'data' => $useCompression ? self::compress($data) : $serializedData,
            'expiration' => time() + $expiration,
            'tags' => $tags,
            'encrypted' => $useEncryption,
            'compressed' => $useCompression,
        ];

        self::$memoryCache[$key] = $cacheData;

        if (count(self::$memoryCache) > self::$maxMemoryCacheSize) {
            self::evictMemoryCache();
        }

        $cacheFile = self::getCacheFilePath($key);
        $encryptedData = $useEncryption ? self::encrypt(serialize($cacheData)) : serialize($cacheData);
        file_put_contents($cacheFile, $encryptedData);
    }

    public static function get($key)
    {
        self::loadConfig();

        if (isset(self::$memoryCache[$key])) {
            if (self::$memoryCache[$key]['expiration'] >= time()) {
                self::updateMemoryCacheAccess($key);

                return self::$memoryCache[$key]['compressed'] ? self::decompress(self::$memoryCache[$key]['data']) : unserialize(self::$memoryCache[$key]['data']);
            } else {
                unset(self::$memoryCache[$key]);
            }
        }

        $cacheFile = self::getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            $cacheData = unserialize(self::decrypt(file_get_contents($cacheFile)));

            if ($cacheData['expiration'] >= time()) {
                self::$memoryCache[$key] = $cacheData;
                if (count(self::$memoryCache) > self::$maxMemoryCacheSize) {
                    self::evictMemoryCache();
                }

                return $cacheData['compressed'] ? self::decompress($cacheData['data']) : unserialize($cacheData['data']);
            } else {
                unlink($cacheFile);
            }
        }

        return null;
    }

    private static function scheduleCleanup()
    {
        $cleanupFile = self::$cacheDir . 'cleanup_time';
        if (!file_exists($cleanupFile) || time() - filemtime($cleanupFile) > self::$cleanupInterval) {
            file_put_contents($cleanupFile, time());
            self::cleanup();
        }
    }

    public static function has($key)
    {
        self::loadConfig();

        if (isset(self::$memoryCache[$key])) {
            if (self::$memoryCache[$key]['expiration'] >= time()) {
                return true;
            } else {
                unset(self::$memoryCache[$key]);
            }
        }

        $cacheFile = self::getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            $cacheData = unserialize(self::decrypt(file_get_contents($cacheFile)));
            if ($cacheData['expiration'] >= time()) {
                self::$memoryCache[$key] = $cacheData;
                if (count(self::$memoryCache) > self::$maxMemoryCacheSize) {
                    self::evictMemoryCache();
                }
                return true;
            } else {
                unlink($cacheFile);
            }
        }

        return false;
    }

    public static function delete($key)
    {
        self::loadConfig();

        unset(self::$memoryCache[$key]);
        $cacheFile = self::getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public static function deleteByTag($tag)
    {
        self::loadConfig();

        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                $cacheData = unserialize(self::decrypt(file_get_contents($file)));
                if (in_array($tag, $cacheData['tags'])) {
                    unlink($file);
                    unset(self::$memoryCache[array_search($file, self::$memoryCache) ?: null]);
                }
            }
        }
    }

    public static function clear()
    {
        self::loadConfig();

        self::$memoryCache = [];
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public static function remember($key, $callback, $expiration = null, $tags = [])
    {
        self::loadConfig();

        if (self::has($key)) {
            return self::get($key);
        }

        $data = $callback();
        self::set($key, $data, $expiration, $tags);
        return $data;
    }

    private static function getCacheFilePath($key)
    {
        return self::$cacheDir . md5($key) . '.cache';
    }

    private static function cleanup()
    {
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                $cacheData = unserialize(self::decrypt(file_get_contents($file)));
                if ($cacheData['expiration'] < time()) {
                    unlink($file);
                    unset(self::$memoryCache[array_search($file, self::$memoryCache) ?: null]);
                }
            }
        }
    }

    private static function evictMemoryCache()
    {
        if (empty(self::$memoryCache)) {
            return;
        }

        $oldestKey = null;
        $oldestTime = PHP_INT_MAX;
        foreach (self::$memoryCache as $key => $cacheData) {
            if (isset($cacheData['last_access']) && $cacheData['last_access'] < $oldestTime) {
                $oldestTime = $cacheData['last_access'];
                $oldestKey = $key;
            }
        }

        if ($oldestKey !== null) {
            unset(self::$memoryCache[$oldestKey]);
        }
    }

    private static function updateMemoryCacheAccess($key)
    {
        if (isset(self::$memoryCache[$key])) {
            self::$memoryCache[$key]['last_access'] = time();
        }
    }
}
