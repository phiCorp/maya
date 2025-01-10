<?php

namespace Maya\Storage;

use Maya\Disk\File;
use Maya\Disk\Directory;
use Exception;

class Storage
{
    protected static array $config = [
        'default_disk' => 'local',
        'disks' => [
            'local' => [
                'root' => '/',
            ],
            'temp' => [
                'root' => 'Storage/temp',
            ],
            'cache' => [
                'root' => 'Storage/cache',
            ],
        ],
        'logging' => false,
    ];

    public static function path(string $path = '', string $disk = 'local'): string
    {
        $root = static::getDiskRoot($disk);
        return $path ? $root . DIRECTORY_SEPARATOR . $path : $root;
    }

    protected static function getDiskRoot(string $disk): string
    {
        if (!isset(self::$config['disks'][$disk])) {
            throw new Exception("Disk '{$disk}' is not configured.");
        }
        $root = self::$config['disks'][$disk]['root'];

        return rtrim(storagePath($root), DIRECTORY_SEPARATOR);
    }


    public static function exists(string $path, string $disk = 'local'): bool
    {
        return File::exists(self::path($path, $disk));
    }

    public static function makeDirectory(string $path, int $permissions = 0755, string $disk = 'local'): bool
    {
        return Directory::make(self::path($path, $disk), $permissions);
    }

    public static function delete(string $path, string $disk = 'local'): bool
    {
        $fullPath = self::path($path, $disk);
        if (File::exists($fullPath)) {
            return File::remove($fullPath);
        } elseif (Directory::exists($fullPath)) {
            return Directory::remove($fullPath);
        }
        return false;
    }

    public static function put(string $path, mixed $content, bool $append = false, string $disk = 'local', string $mimeType = 'text/plain'): bool
    {
        $fullPath = self::path($path, $disk);
        $result = $append ? File::append($fullPath, $content) : File::write($fullPath, $content);

        if ($result && self::$config['logging']) {
            self::log('put', $fullPath, $disk, $mimeType);
        }

        return $result;
    }

    public static function get(string $path, string $disk = 'local'): string
    {
        return File::read(self::path($path, $disk));
    }

    public static function stream(string $path, string $disk = 'local')
    {
        $fullPath = self::path($path, $disk);
        if (!File::exists($fullPath)) {
            throw new Exception("File not found: {$fullPath}");
        }
        return fopen($fullPath, 'rb');
    }

    public static function copy(string $from, string $to, string $diskFrom = 'local', string $diskTo = 'local'): bool
    {
        return File::copy(self::path($from, $diskFrom), self::path($to, $diskTo));
    }

    public static function move(string $from, string $to, string $diskFrom = 'local', string $diskTo = 'local'): bool
    {
        return File::move(self::path($from, $diskFrom), self::path($to, $diskTo));
    }

    public static function download(string $path, string $disk = 'local', string $filename = null): void
    {
        $fullPath = self::path($path, $disk);

        if (!File::exists($fullPath)) {
            throw new Exception("File not found: {$fullPath}");
        }

        $mimeType = File::mime($fullPath);
        $filename = $filename ?: basename($fullPath);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));

        readfile($fullPath);
        exit;
    }

    public static function presignedUrl(string $path, string $disk = 'local', int $expires = 3600): string
    {
        $fullPath = self::path($path, $disk);
        if (!File::exists($fullPath)) {
            throw new Exception("File not found: {$fullPath}");
        }

        $signature = hash_hmac('sha256', $fullPath . time(), config('APP.KEY'));
        $expiresAt = time() + $expires;

        return "/download?path={$fullPath}&expires={$expiresAt}&signature={$signature}";
    }

    public static function validatePresignedUrl(string $url): bool
    {
        $parts = parse_url($url);
        parse_str($parts['query'], $query);

        if (!isset($query['path'], $query['expires'], $query['signature'])) {
            return false;
        }

        if ($query['expires'] < time()) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $query['path'] . $query['expires'], config('APP.KEY'));
        return hash_equals($expectedSignature, $query['signature']);
    }

    public static function log(string $operation, string $path, string $disk, string $mimeType = null): void
    {
        error_log("Operation: {$operation}, Path: {$path}, Disk: {$disk}, MimeType: {$mimeType}");
    }
}
