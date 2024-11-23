<?php

use Maya\Database\Connection\Connection;
use Maya\Database\lightSQL;
use Maya\Disk\File;
use Maya\Http\Redirect;
use Maya\Http\Request;
use Maya\Http\Response;
use Maya\Support\Auth;
use Maya\Support\Config;
use Maya\Support\Cookie;
use Maya\Support\Error;
use Maya\Support\Flash;
use Maya\Support\Session;
use Maya\Support\Token;
use Maya\Support\Arr;
use Maya\Support\URL;
use Maya\System\Container;
use Maya\System\Debugger;
use Maya\System\ErrorHandler;
use Maya\View\View;


/**
 * It for display a view
 * @param string $dir
 * @param array $data
 * @param int $httpStatus
 * @param array $httpHeaders
 * @return void|View
 */
function view(string $dir = null, array $data = [], int $httpStatus = 200, array $httpHeaders = [])
{
    if (is_null($dir)) {
        return app("view");
    }
    app('view')->make($dir, $data, $httpStatus, $httpHeaders);
}

if (!function_exists('response')) {
    function response(): Response
    {
        return app('response');
    }
}

function import($path, $once = true, $include = false)
{
    if ($include) {
        if ($once) {
            include_once $path;
        } else {
            include $path;
        }
    } else {
        if ($once) {
            require_once $path;
        } else {
            require $path;
        }
    }
}

function isPortOpen($host, $port, $timeout = 3)
{
    set_error_handler(function () {});
    $connection = fsockopen($host, $port, $errno, $errstr, $timeout);
    restore_error_handler();
    if ($connection) {
        fclose($connection);
        return true;
    } else {
        return false;
    }
}

function arrToObj(array $array, $depth = -1, $className = 'stdClass')
{
    $object = new $className();

    if ($depth === 0) {
        return $array;
    }

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $object->$key = arrToObj($value, $className, $depth - 1);
        } else {
            $object->$key = $value;
        }
    }

    return $object;
}
/**
 * This function is summarized in html_entity_decode
 * @param $text
 * @return string
 */
function html($text): string
{
    return html_entity_decode($text);
}

/**
 * Encode HTML special characters in a string.
 *
 * @param mixed  $value
 * @param  bool  $doubleEncode
 * @return string
 */
function e($value, $doubleEncode = false)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
}

function currentDomain(): string
{
    return url()->currentDomain();
}

function url($url = null)
{
    $urlInstance = app('url');
    if (is_null($url)) {
        return $urlInstance;
    } else {
        return $urlInstance->to($url);
    }
}

/**
 * find and return route url with routeName
 * @param string $name
 * @param array $params
 * @return string
 */
function route(string $name, array $params = [], $absolute = true): string
{
    return url()->route($name, $params, $absolute);
}


if (!function_exists('app')) {
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }
        return Container::getInstance()->make($abstract, $parameters);
    }
}

function get_csrf()
{
    return session('CSRF_TOKEN');
}

function get_csrf_input(): string
{
    return '<input type="hidden" hidden value="' . get_csrf() . '" name="CSRF_TOKEN">';
}

function get_methodField_input(string $method): string
{
    if (!in_array(strtoupper($method), ['PUT', 'DELETE'])) {
        throw new InvalidArgumentException("The " . strtoupper($method) . " method is wrong");
    }
    return '<input type="hidden" hidden value="' . strtoupper($method) . '" name="_method">';
}


function config($config = null, $default = null)
{
    $configInstance = app('config');
    if ($config == null) {
        return $configInstance;
    }
    return $configInstance->get($config, $default);
}

function arr()
{
    return app('arr');
}

function terminate()
{
    return app()->terminate();
}

function request(): Request
{
    return app('request');
}

function token(): Token
{
    return app('token');
}

function filer(): File
{
    return app('file');
}

function directory(): Directory
{
    return app('directory');
}

if (!function_exists('abort')) {
    function abort(int $statusCode, string $message = ''): void
    {
        $errorHandler = app('errorHandler');

        if (empty($message)) {
            $message = $errorHandler->getMessageForStatusCode($statusCode);
        }


        $errorHandler->abort($statusCode, $message);
    }
}
if (!function_exists('abort_if')) {
    function abort_if(bool $condition, int $statusCode, string $message = ''): void
    {
        if ($condition) {
            abort($statusCode, $message);
        }
    }
}
if (!function_exists('abort_unless')) {
    function abort_unless(bool $condition, int $statusCode, string $message = ''): void
    {
        if (!$condition) {
            abort($statusCode, $message);
        }
    }
}
if (!function_exists('assetAlias')) {
    function assetAlias(string $key)
    {
        $alias = config("ASSETS.ALIAS.{$key}", null);
        if (is_string($alias)) {
            return $alias;
        }
        return null;
    }
}

if (!function_exists('assetUrl')) {
    function assetUrl($dir, $token, $version = null)
    {
        return route('dl', ['dir' => $dir, 'token' => $token, 'v' => $version ?? config('ASSETS.VERSION')]);
    }
}

if (!function_exists('cssUrl')) {
    function cssUrl($token, $version = null)
    {
        return assetUrl('c', $token, $version);
    }
}

if (!function_exists('jsUrl')) {
    function jsUrl($token, $version = null)
    {
        return assetUrl('j', $token, $version);
    }
}

if (!function_exists('fontUrl')) {
    function fontUrl($token, $version = null)
    {
        return assetUrl('f', $token, $version);
    }
}



/*
|--------------------------------------------------------------------------
| Public helper functions
|--------------------------------------------------------------------------
*/

if (!function_exists('is')) {
    /**
     * Check if a given value is equal to another value or to any value in an array of values.
     *
     * @param mixed $value The value to check.
     * @param mixed $compare The value or array of values to compare against.
     * @param bool $strict Whether or not to perform a strict comparison (i.e. compare variable type).
     * @return bool True if the value is equal to the compare value or any value in the compare array, false otherwise.
     */
    function is($value, $compare, $strict = false): bool
    {
        if (is_array($compare)) {
            foreach ($compare as $item) {
                if ($strict) {
                    if ($value === $item) {
                        return true;
                    }
                } else {
                    if ($value == $item) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            if ($strict) {
                return $value === $compare;
            } else {
                return $value == $compare;
            }
        }
    }
}



/*
|--------------------------------------------------------------------------
| Path helper functions
|--------------------------------------------------------------------------
*/

function basePath($path = '')
{
    return app()->basePath($path);
}

function configPath()
{
    return app()->configPath();
}

function appPath($path = '')
{
    return app()->appPath($path);
}

function storagePath($path = '')
{
    return app()->storagePath($path);
}

function assetsPath($path = '')
{
    return app()->assetsPath($path);
}

function mayaPath($path = '')
{
    return app()->mayaPath($path);
}



/*
|--------------------------------------------------------------------------
| Session AND Cookie helper functions
|--------------------------------------------------------------------------
*/

if (!function_exists('session')) {
    /**
     * Access the session or set session values.
     *
     * @param string|array|null $key     The session key, an associative array of key-value pairs, or null to get the Session instance.
     * @param mixed             $default The default value to return when the session key is not found.
     *
     * @return mixed|Session|null If $key is null, returns the Session instance. If $key is an array, sets session values and returns null. Otherwise, returns the session value or $default if not found.
     */
    function session($key = null, $default = null)
    {
        $sessionInstance = app('session');
        if (is_null($key)) {
            return $sessionInstance;
        }
        if (is_array($key)) {
            foreach ($key as $sessionKey => $value) {
                $sessionInstance->set($sessionKey, $value);
            }
            return null;
        }
        if (is_string($key)) {
            return $sessionInstance->get($key, $default);
        }
        return null;
    }
}

if (!function_exists('cookie')) {
    /**
     * Helper function for working with cookies using the Cookie class.
     *
     * @param string|null $name            The name of the cookie to get or set. If null, returns a CookieHandler instance.
     * @param mixed|null  $value           The value to set for the cookie (if setting a cookie). If null, retrieves the cookie value.
     * @param int         $expirationMinutes The expiration time of the cookie in minutes (if setting a cookie).
     *
     * @return mixed|Cookie|null If retrieving a cookie, returns the cookie value. If setting a cookie, returns void.
     */
    function cookie($name = null, $value = null, $expirationMinutes = 0, $httponly = false, $secure = false, $path = "/", $domain = "")
    {
        $cookie = app('cookie');
        if ($name === null) {
            return $cookie;
        }
        if ($value === null) {
            return $cookie->get($name);
        }
        $cookie->set($name, $value, $expirationMinutes, $httponly, $secure, $path, $domain);
    }
}



/*
|--------------------------------------------------------------------------
| Redirect helper functions
|--------------------------------------------------------------------------
*/

if (!function_exists('redirect')) {
    /**
     * Redirect to a specified URL or route.
     *
     * @param string|null $to The URL or route to redirect to. If null, it will redirect to the previous page.
     * @param int $status The HTTP status code for the redirect (default is 302).
     * @param array $headers Additional headers to include in the response.
     * @param bool|null $secure Whether the redirect should be secure (HTTPS) or not.
     * @return Redirect The redirection response.
     */
    function redirect(?string $to = null, int $status = 302, array $headers = [], ?bool $secure = null)
    {
        return app('redirect')->redirect($to, $status, $headers, $secure);
    }
}

if (!function_exists('back')) {
    /**
     * Redirect back to the previous page or a specified URL.
     *
     * @param string $to The URL to redirect back to. Default is the root path ('/').
     * @param int $status The HTTP status code for the redirect (default is 302).
     * @param array $headers Additional headers to include in the response.
     * @param bool|null $secure Whether the redirect should be secure (HTTPS) or not.
     * @return Redirect The redirection response.
     */
    function back(string $to = '/', int $status = 302, array $headers = [], ?bool $secure = null)
    {
        return redirect()->back($to, $status, $headers, $secure);
    }
}

if (!function_exists('to_route')) {
    /**
     * Redirect to a named route with optional parameters.
     *
     * @param string $name The name of the route to redirect to.
     * @param array $params Parameters to pass to the route.
     * @param int $status The HTTP status code for the redirect (default is 302).
     * @param array $headers Additional headers to include in the response.
     * @return Redirect The redirection response.
     */
    function to_route(string $name, array $params = [], $status = 302, $headers = [])
    {
        return redirect()->route($name, $params, $status, $headers);
    }
}



/*
|--------------------------------------------------------------------------
| Flash Messages helper functions
|--------------------------------------------------------------------------
*/

if (!function_exists('old')) {
    /**
     * It is used to display the value of previous inputs
     * @param $name
     * @return mixed
     */
    function old($name, $default = null): mixed
    {
        return session("temporary_old")[$name] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Flash message helper function.
     *
     * This function provides a convenient way to work with flash messages using the Flash class.
     *
     * @param string|null $name     The name of the flash message. If provided, it will retrieve or set the message.
     * @param mixed|null $message   The message to be stored in the flash session (when setting).
     *
     * @return mixed|null|Flash When $name is provided:
     * - If $message is not provided, it retrieves the flash message by name.
     * - If $message is provided, it sets the flash message with the given name and message.
     * When $name is not provided, it returns an instance of the Flash class.
     */
    function flash($name = null, $message = null)
    {
        $flashInstance = app('flash');
        if (is_null($name)) {
            return $flashInstance;
        }
        if (is_null($message)) {
            return $flashInstance->get($name);
        }
        return $flashInstance->set($name, $message);
    }
}

if (!function_exists('error')) {
    /**
     * Helper function for interacting with the Error class.
     *
     * This function allows you to work with error messages using the Error class. You can use it to retrieve an instance of the Error class, get error messages, or set new error messages.
     *
     * @param string|null $name The name of the error. If provided, the function will either return the error message associated with the name or set a new error message.
     * @param string|null $message The error message to set if a name is provided. If this is not provided, the function will retrieve an existing error message.
     *
     * @return Error|mixed|null Returns an instance of the Error class if no parameters are provided. If a name is provided but no message, it returns the error message associated with that name. If both name and message are provided, it sets a new error message and returns the result.
     */
    function error($name = null, $message = null)
    {
        if (is_null($name)) {
            return app('error');
        }
        if (is_null($message)) {
            return app('error')->get($name);
        }
        return app('error')->set($name, $message);
    }
}



/*
|--------------------------------------------------------------------------
| Debug helper functions
|--------------------------------------------------------------------------
*/

if (!function_exists('dd')) {
    /**
     * It accepts any number of parameters and takes var_dump one by one and dies at the end
     * @param ...$vars
     * @return void
     */

    function dd(...$vars)
    {
        app('debugger')->dump(...$vars);
        app()->terminate();
    }
}

if (!function_exists('d')) {
    /**
     * It accepts any number of parameters and takes var_dump one by one
     * @param ...$vars
     * @return void
     */
    function d(...$vars): void
    {
        app('debugger')->dump(...$vars);
    }
}
if (! function_exists('env')) {
    /**
     * Get the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return string|null
     */
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        return $value !== null ? $value : $default;
    }
}
if (!function_exists('dump')) {
    /**
     * It accepts any number of parameters and takes var_dump one by one
     * @param ...$vars
     * @return void
     */
    function dump(...$vars)
    {
        app('debugger')->dump(...$vars);
    }
}



/*
|--------------------------------------------------------------------------
| Auth helper functions
|--------------------------------------------------------------------------
*/

if (!function_exists('auth')) {
    function auth(): Auth
    {
        return app('auth');
    }
}

if (!function_exists('user')) {
    function user()
    {
        return auth()->user();
    }
}
