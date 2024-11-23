<?php

namespace Maya\Http;

use Maya\Disk\File;

class Response
{
    public function view(string $dir, array $data = [], int $httpStatus = 200, array $httpHeaders = [])
    {
        return view($dir, $data, $httpStatus, $httpHeaders);
    }

    public function json($data = [], int $status = 200, array $headers = [], int $options = 0)
    {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException('Invalid HTTP status code.');
        }

        $json = json_encode($data, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }

        $defaultHeaders = ['Content-Type' => 'application/json'];
        $headers = array_merge($defaultHeaders, $headers);

        http_response_code($status);

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo $json;
        app()->terminate();
    }

    public function redirect(?string $to = null, int $status = 302, array $headers = [], ?bool $secure = null)
    {
        return redirect($to, $status, $headers, $secure);
    }

    public function download(string $filename, $fileAsName = null, string $type = null, bool $age = false)
    {
        return File::download($filename, $type, $age, $fileAsName);
    }

    public function stream(callable $callback, int $status = 200, array $headers = [], ?int $chunkSize = null)
    {
        $headers = array_merge(['Content-Type' => 'application/octet-stream'], $headers);
        $this->sendHeaders($headers, $status);

        $chunkSize = $chunkSize ?: 8192;
        $this->disableOutputBuffering();

        if (is_callable($callback)) {
            $callback($chunkSize);
        }

        flush();
        app()->terminate();
    }

    public function streamFile(string $filePath, ?string $fileName = null, array $headers = [])
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo 'File not found or not readable.';
            return;
        }

        $fileName = $fileName ?: basename($filePath);

        $headers = array_merge([
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Type' => mime_content_type($filePath),
            'Content-Length' => filesize($filePath),
        ], $headers);

        $this->stream(function ($chunkSize) use ($filePath) {
            $handle = fopen($filePath, 'rb');
            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                flush();
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function streamData(iterable $dataGenerator, array $headers = [])
    {
        $headers = array_merge(['Content-Type' => 'text/plain'], $headers);

        $this->stream(function () use ($dataGenerator) {
            foreach ($dataGenerator as $chunk) {
                echo $chunk;
                flush();
            }
        }, 200, $headers);
    }

    private function disableOutputBuffering()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
    }

    private function sendHeaders(array $headers, int $status)
    {
        http_response_code($status);
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }
}
