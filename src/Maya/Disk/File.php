<?php

namespace Maya\Disk;

use Exception;
use Generator;

class File
{
    public static function exists(string $filename)
    {
        return file_exists($filename);
    }

    public static function read(string $filename, bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null)
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return file_get_contents($filename, $use_include_path, $context, $offset, $length);
    }

    public static function write(string $filename, mixed $data, int $flags = 0, $context = null): bool
    {
        return file_put_contents($filename, $data, $flags, $context) !== false;
    }

    public static function append(string $filename, mixed $data): bool
    {
        $result = file_put_contents($filename, $data, FILE_APPEND);
        return $result !== false;
    }

    public static function remove(string $filename)
    {
        if (self::exists($filename)) {
            return unlink($filename);
        }
        return false;
    }

    public static function size(string $filename): int
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return filesize($filename);
    }

    public static function rename(string $filename, string $newName): bool
    {
        $newPath = dirname($filename) . DIRECTORY_SEPARATOR . $newName;
        if (self::exists($newPath)) {
            throw new Exception("File already exists: {$newPath}");
        }
        return rename($filename, $newPath);
    }

    public static function move(string $from, string $to): bool
    {
        if (!self::exists($from)) {
            throw new Exception("File not found: {$from}");
        }

        if (is_dir($to)) {
            $newName = basename($from);
            $to = rtrim($to, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;
        } else {
            $directory = dirname($to);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }

        if (self::exists($to)) {
            throw new Exception("File already exists: {$to}");
        }

        return rename($from, $to);
    }

    public static function copy(string $from, string $to): bool
    {
        if (!self::exists($from)) {
            throw new Exception("File not found: {$from}");
        }
        return copy($from, $to);
    }

    public static function updatedAt(string $filename): int
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return filemtime($filename);
    }

    public static function canRead(string $filename): bool
    {
        return is_readable($filename);
    }

    public static function canWrite(string $filename): bool
    {
        return is_writable($filename);
    }

    public static function isFile(string $filename): bool
    {
        return is_file($filename);
    }

    public static function make(string $filename): bool
    {
        if (self::exists($filename)) {
            throw new Exception("File already exists: {$filename}");
        }
        return touch($filename);
    }

    public static function mime(string $filename): string|false
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return mime_content_type($filename);
    }

    public static function ext(string $filename): string
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    public static function truncate(string $filename): bool
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return file_put_contents($filename, '') !== false;
    }

    public static function readLines(string $filename): Generator
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }

        $handle = fopen($filename, 'r');
        if ($handle) {
            try {
                while (($line = fgets($handle)) !== false) {
                    yield $line;
                }
            } finally {
                fclose($handle);
            }
        } else {
            throw new Exception("File cannot be opened: {$filename}");
        }
    }

    public static function hash(string $filename, string $algo = 'sha256'): string|false
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return hash_file($algo, $filename);
    }

    public static function lock(string $filename, int $operation = LOCK_EX): bool
    {
        $handle = fopen($filename, 'c');
        if (!$handle) {
            throw new Exception("File cannot be opened: {$filename}");
        }
        return flock($handle, $operation);
    }

    public static function unlock(string $filename): bool
    {
        $handle = fopen($filename, 'c');
        if (!$handle) {
            throw new Exception("File cannot be opened: {$filename}");
        }
        return flock($handle, LOCK_UN);
    }

    public static function isBinary(string $filename): bool
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        $content = file_get_contents($filename);
        return substr_count($content, "\0") > 0;
    }

    public static function isSame(string $file1, string $file2): bool
    {
        if (!self::exists($file1) || !self::exists($file2)) {
            throw new Exception("One of the files was not found");
        }
        return md5_file($file1) === md5_file($file2);
    }

    public static function isEmpty(string $filename): bool
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return filesize($filename) === 0;
    }

    public static function duplicate(string $filename, ?string $newName = null): bool
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        if (is_null($newName)) {
            $newName = self::generateUniqueName($filename);
        }
        return copy($filename, dirname($filename) . DIRECTORY_SEPARATOR . $newName);
    }

    private static function generateUniqueName(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $directory = dirname($filename);
        $newName = $baseName . '_' . uniqid() . ($extension ? '.' . $extension : '');
        while (file_exists($directory . DIRECTORY_SEPARATOR . $newName)) {
            $newName = $baseName . '_' . uniqid() . ($extension ? '.' . $extension : '');
        }
        return $newName;
    }

    public static function writeJson(string $filename, array $data): bool
    {
        array_walk_recursive($data, function (&$item) {
            if (is_string($item)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'auto');
            }
        });
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonData === false) {
            throw new Exception("JSON encoding error: " . json_last_error_msg());
        }
        return self::write($filename, $jsonData);
    }

    public static function readJson(string $filename): array
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        $jsonData = self::read($filename);
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parsing error: " . json_last_error_msg());
        }
        return $data;
    }

    public static function linesCount(string $filename): int
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return count(file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }

    public static function download(string $filename, ?string $type = null, bool $age = false, $fileAsName = null): void
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }

        $downloadName = $fileAsName ?? basename($filename);

        header('Content-Description: File Transfer');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');

        if ($type === null || $type === '') {
            header('Content-Type: application/octet-stream');
        } else {
            header('Content-Type: ' . $type);
        }

        if ($age) {
            header('Cache-Control: max-age=604800, must-revalidate');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
        } else {
            header('Cache-Control: must-revalidate');
            header('Expires: 0');
        }

        header('Pragma: public');
        header('Content-Length: ' . filesize($filename));
        flush();
        readfile($filename);
        app()->terminate();
    }

    public static function setPermissions(string $filename, int $permissions): bool
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        return chmod($filename, $permissions);
    }

    public static function searchInFile(string $filename, string $searchString): bool
    {
        if (!self::exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }
        $content = self::read($filename);
        return strpos($content, $searchString) !== false;
    }
}
