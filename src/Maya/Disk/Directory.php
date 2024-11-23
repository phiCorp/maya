<?php

namespace Maya\Disk;

use DirectoryIterator;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Directory
{
    public static function exists(string $directory): bool
    {
        return is_dir($directory);
    }

    public static function make(string $directory, int $permissions = 0755, bool $recursive = true): bool
    {
        if (self::exists($directory)) {
            throw new Exception("Directory already exists: {$directory}");
        }
        return mkdir($directory, $permissions, $recursive);
    }

    public static function remove(string $directory): bool
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }
        return rmdir($directory);
    }

    public static function read(string $directory): array
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }

        $contents = scandir($directory);
        if ($contents === false) {
            throw new Exception("Unable to read directory: {$directory}");
        }

        return array_diff($contents, ['.', '..']);
    }

    public static function rename(string $directory, string $newName): bool
    {
        $newPath = dirname($directory) . DIRECTORY_SEPARATOR . $newName;
        if (self::exists($newPath)) {
            throw new Exception("Directory already exists: {$newPath}");
        }
        return rename($directory, $newPath);
    }

    public static function move(string $from, string $to): bool
    {
        if (!self::exists($from)) {
            throw new Exception("Directory not found: {$from}");
        }

        if (!is_dir($to)) {
            mkdir($to, 0755, true);
        }

        $newPath = rtrim($to, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($from);
        return rename($from, $newPath);
    }

    public static function size(string $directory): int
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    public static function copy(string $from, string $to): bool
    {
        if (!self::exists($from)) {
            throw new Exception("Directory not found: {$from}");
        }

        if (!is_dir($to)) {
            mkdir($to, 0755, true);
        }

        $directoryIterator = new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $subPath = $directoryIterator->getSubPathname();
            $targetPath = $to . DIRECTORY_SEPARATOR . $subPath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }

        return true;
    }

    public static function canRead(string $directory): bool
    {
        return is_readable($directory);
    }

    public static function canWrite(string $directory): bool
    {
        return is_writable($directory);
    }

    public static function updatedAt(string $directory): int
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }
        return filemtime($directory);
    }

    public static function setPermissions(string $directory, int $permissions): bool
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }
        return chmod($directory, $permissions);
    }

    public static function isEmpty(string $directory): bool
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }
        return count(scandir($directory)) === 2;
    }

    public static function truncate(string $directory): bool
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        return true;
    }

    public static function truncateAndRemove(string $directory): bool
    {
        self::truncate($directory);
        return self::remove($directory);
    }

    public static function hash(string $directory, string $algorithm = 'md5'): string
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }

        $hashContext = hash_init($algorithm);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                hash_update($hashContext, $item->getRealPath());
                hash_update_file($hashContext, $item->getRealPath());
            }
        }

        return hash_final($hashContext);
    }

    public static function zip(string $directory, string $destination): bool
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }

        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Unable to create zip file: {$destination}");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath($directory)) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        return $zip->close();
    }


    public static function unzip(string $zipFile, string $destination): bool
    {
        if (!file_exists($zipFile)) {
            throw new Exception("Zip file not found: {$zipFile}");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new Exception("Unable to open zip file: {$zipFile}");
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        if (!$zip->extractTo($destination)) {
            throw new Exception("Unable to extract zip file to: {$destination}");
        }

        return $zip->close();
    }

    public static function compress(string $source, string $destination): bool
    {
        if (!self::exists($source) && !is_file($source)) {
            throw new Exception("Source not found: {$source}");
        }

        $data = '';

        if (is_dir($source)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(realpath($source)) + 1);
                $data .= file_get_contents($filePath);
            }
        } else {
            $data = file_get_contents($source);
        }

        $compressedData = gzcompress($data);

        if (file_put_contents($destination, $compressedData) === false) {
            throw new Exception("Failed to write compressed data to: {$destination}");
        }

        return true;
    }

    public static function decompress(string $source, string $destination): bool
    {
        if (!file_exists($source)) {
            throw new Exception("Compressed file not found: {$source}");
        }

        $compressedData = file_get_contents($source);
        $decompressedData = gzuncompress($compressedData);

        if ($decompressedData === false) {
            throw new Exception("Failed to decompress file: {$source}");
        }

        if (file_put_contents($destination, $decompressedData) === false) {
            throw new Exception("Failed to write decompressed data to: {$destination}");
        }

        return true;
    }

    public static function glob(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags);
        if ($files === false) {
            throw new Exception("Failed to execute glob pattern: {$pattern}");
        }
        return $files;
    }

    public static function search(string $directory, array $patterns, bool $recursive = false, array $excludes = [], array $fileTypes = [], array $dateRange = []): array
    {
        if (!self::exists($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }

        $results = [];
        $excludes = array_map('realpath', $excludes);

        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }

            $currentPath = $file->getRealPath();

            foreach ($excludes as $exclude) {
                if ($currentPath === $exclude || strpos($currentPath, $exclude . DIRECTORY_SEPARATOR) === 0) {
                    continue 2;
                }
            }

            $matchesPattern = false;
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $file->getFilename())) {
                    $matchesPattern = true;
                    break;
                }
            }

            if ($matchesPattern && !empty($fileTypes)) {
                $fileExtension = pathinfo($currentPath, PATHINFO_EXTENSION);
                if (!in_array($fileExtension, $fileTypes)) {
                    continue;
                }
            }

            if ($matchesPattern && !empty($dateRange)) {
                $fileMTime = $file->getMTime();
                if ($fileMTime < strtotime($dateRange[0]) || $fileMTime > strtotime($dateRange[1])) {
                    continue;
                }
            }

            if ($matchesPattern) {
                if ($file->isFile()) {
                    $results[] = [
                        'path' => $currentPath,
                        'size' => $file->getSize(),
                        'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                        'type' => $file->getType(),
                    ];
                } else {
                    error_log("Warning: File not found or not accessible: " . $currentPath);
                }
            }
        }

        return $results;
    }
}
