<?php

namespace Maya\Http;

use CURLFile;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

class Http
{
    private static $defaultHeaders = [
        'User-Agent: Davinci Http Client',
        'Accept: application/json',
    ];

    private static $timeout = 30;
    private static $cookies = [];
    private static $loggingEnabled = false;
    private static $retryCount = 3;

    public static function enableLogging($enable = true)
    {
        self::$loggingEnabled = $enable;
    }

    private static function log($message)
    {
        if (self::$loggingEnabled) {
            file_put_contents('http_client.log', date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
        }
    }

    private static function validateUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: $url");
        }
    }

    public static function setTimeout(int $timeout)
    {
        self::$timeout = $timeout;
    }

    public static function setRetryCount(int $count)
    {
        self::$retryCount = $count;
    }

    private static function initializeCurl(string $url, string $method, $data, array $headers)
    {
        self::validateUrl($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(self::$defaultHeaders, $headers));
        curl_setopt($ch, CURLOPT_COOKIE, self::formatCookies());

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, self::prepareData($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, self::prepareData($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_POSTFIELDS, self::prepareData($data));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, self::prepareData($data));
                break;
            case 'OPTIONS':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }

        return $ch;
    }

    private static function handleResponse($httpCode, $response, $contentType)
    {
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode: " . ($response ?: 'No response'));
        }

        if (empty($response)) {
            return null;
        }

        $contentType = explode(';', $contentType)[0];

        if ($contentType === 'application/json') {
            return json_decode($response, true);
        } elseif ($contentType === 'application/xml' || $contentType === 'text/xml') {
            return simplexml_load_string($response);
        }

        return $response;
    }

    private static function manageCookies($ch)
    {
        $cookieHeader = curl_getinfo($ch, CURLINFO_COOKIELIST);
        if (!empty($cookieHeader)) {
            foreach ($cookieHeader as $cookie) {
                list($name, $value) = explode('=', $cookie, 2);
                self::$cookies[$name] = $value;
            }
        }
    }

    private static function formatCookies()
    {
        return http_build_query(self::$cookies, '', '; ');
    }

    private static function prepareData($data)
    {
        if ($data instanceof CURLFile) {
            return $data;
        } elseif (is_array($data)) {
            if (isset($data['__content_type']) && $data['__content_type'] === 'xml') {
                unset($data['__content_type']);
                return self::arrayToXml($data);
            } elseif (isset($data['__form_data']) && $data['__form_data']) {
                unset($data['__form_data']);
                return http_build_query($data);
            }
            return json_encode($data);
        } else {
            return http_build_query($data);
        }
    }

    private static function arrayToXml($data, $rootElement = '<root/>')
    {
        $xml = new SimpleXMLElement($rootElement);
        array_walk_recursive($data, function ($value, $key) use ($xml) {
            $xml->addChild($key, htmlspecialchars($value));
        });
        return $xml->asXML();
    }

    private static function makeRequest($url, $method, $data, $headers)
    {
        $attempts = 0;

        do {
            $attempts++;
            $ch = self::initializeCurl($url, $method, $data, $headers);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            self::manageCookies($ch);
            curl_close($ch);

            self::log("Attempt $attempts: $method $url - Response: $response");

            if ($httpCode < 400) {
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                return self::handleResponse($httpCode, $response, $contentType);
            }
        } while ($attempts < self::$retryCount);

        throw new Exception("Max retries reached for $method $url");
    }

    public static function get($url, array $headers = [])
    {
        return self::makeRequest($url, 'GET', [], $headers);
    }

    public static function post($url, $data = [], array $headers = [])
    {
        return self::makeRequest($url, 'POST', $data, $headers);
    }

    public static function put($url, $data = [], array $headers = [])
    {
        return self::makeRequest($url, 'PUT', $data, $headers);
    }

    public static function delete($url, $data = [], array $headers = [])
    {
        return self::makeRequest($url, 'DELETE', $data, $headers);
    }

    public static function patch($url, $data = [], array $headers = [])
    {
        return self::makeRequest($url, 'PATCH', $data, $headers);
    }

    public static function multiRequest(array $requests)
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $responses = [];

        foreach ($requests as $key => $request) {
            $method = strtoupper($request['method']);
            $url = $request['url'];
            $data = $request['data'] ?? [];
            $headers = $request['headers'] ?? [];

            self::validateUrl($url);
            $curlHandle = self::initializeCurl($url, $method, $data, $headers);
            curl_multi_add_handle($multiHandle, $curlHandle);
            $curlHandles[$key] = $curlHandle;
        }

        do {
            $status = curl_multi_exec($multiHandle, $active);
            curl_multi_select($multiHandle);
        } while ($active && $status == CURLM_CALL_MULTI_PERFORM);

        foreach ($curlHandles as $key => $handle) {
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $response = curl_multi_getcontent($handle);
            self::manageCookies($handle);
            curl_multi_remove_handle($multiHandle, $handle);
            curl_close($handle);

            $contentType = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);

            self::log("Multi Request $key - Response: $response");
            $responses[$key] = self::handleResponse($httpCode, $response, $contentType);
        }

        curl_multi_close($multiHandle);
        return $responses;
    }
}
