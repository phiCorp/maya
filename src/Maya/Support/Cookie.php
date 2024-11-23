<?php

namespace Maya\Support;


class Cookie
{
    /**
     * Set a cookie with optional encryption.
     *
     * @param string $name The name of the cookie.
     * @param mixed $value The value to store in the cookie.
     * @param int $expirationMinutes The expiration time in minutes (default is 0 for session).
     * @param bool $httponly Restrict the cookie to be accessible only through HTTP.
     * @param bool $secure Restrict the cookie to be sent only over secure HTTPS connections.
     * @param string $path The path on the server in which the cookie will be available.
     * @param string $domain The domain where the cookie is accessible.
     */
    public static function set($name, $value, $expirationMinutes = 0, $httponly = false,  $secure = false, $path = "/", $domain = "")
    {
        setcookie($name, self::encrypt($value), time() + $expirationMinutes * 60, $path, $domain, $secure, $httponly);
    }

    /**
     * Set a cookie with a long expiration time (forever).
     *
     * @param string $name The name of the cookie.
     * @param mixed $value The value to store in the cookie.
     */
    public static function forever($name, $value)
    {
        self::set($name, $value, 262800000);
    }

    /**
     * Get the value of a cookie with optional decryption.
     *
     * @param string $name The name of the cookie.
     * @return mixed|null The value of the cookie or null if it doesn't exist.
     */
    public static function get($name)
    {
        if (self::exists($name)) {
            return self::decrypt($_COOKIE[$name]);
        }
        return null;
    }

    /**
     * Check if a cookie exists.
     *
     * @param string $name The name of the cookie.
     * @return bool True if the cookie exists, false otherwise.
     */
    public static function exists($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete one or more cookies by name.
     *
     * @param string|array $names The name or an array of names of the cookies to delete.
     */
    public static function forget($names)
    {
        if (!is_array($names)) {
            $names = [$names];
        }
        foreach ($names as $name) {
            if (self::exists($name)) {
                setcookie($name, '', time() - 3600, '/');
                unset($_COOKIE[$name]);
            }
        }
    }

    /**
     * Delete all cookies.
     */
    public static function flush()
    {
        foreach (array_keys($_COOKIE) as $name) {
            self::forget($name);
        }
    }

    /**
     * Check if a cookie is missing (not set).
     *
     * @param string $name The name of the cookie.
     * @return bool True if the cookie is missing, false otherwise.
     */
    public static function missing($name)
    {
        return !self::exists($name);
    }

    /**
     * Get an array of all cookies with optional decryption.
     *
     * @return array An associative array of cookies and their values.
     */
    public static function all()
    {
        $decryptedCookies = [];
        foreach ($_COOKIE as $name => $value) {
            $decryptedCookies[$name] = self::decrypt($value);
        }
        return $decryptedCookies;
    }

    /**
     * Encrypt a value for storing in a cookie.
     *
     * @param mixed $value The value to encrypt.
     * @return string The encrypted value.
     */
    private static function encrypt($value)
    {
        return base64_encode(encrypt($value, config('APP.KEY')));
    }

    /**
     * Decrypt a value stored in a cookie.
     *
     * @param string $encryptedValue The encrypted value.
     * @return mixed The decrypted value.
     */
    private static function decrypt($encryptedValue)
    {
        return decrypt(base64_decode($encryptedValue), config('APP.KEY'));
    }
}
