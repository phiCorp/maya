<?php

namespace Maya\Support;

class Session
{
    /**
     * Return the Session instance.
     *
     * @return $this
     */
    public static function instance()
    {
        return new static();
    }

    protected static function setSessionGCProbability($value)
    {
        ini_set('session.gc_probability', $value);
    }

    protected static function setSessionGCDivisor($value)
    {
        ini_set('session.gc_divisor', $value);
    }

    protected static function useCookies($value)
    {
        ini_set('session.use_cookies', $value);
    }

    protected static function useOnlyCookies($value)
    {
        ini_set('session.use_only_cookies', $value);
    }

    protected static function setSessionSidBitsPerCharacter($value)
    {
        ini_set('session.sid_bits_per_character', $value);
    }

    protected static function setSessionSidLength($value)
    {
        ini_set('session.sid_length', $value);
    }
    public static function start(array $options = [])
    {
        session_start($options);
        session_regenerate_id();
    }
    public static function setSessionConfigure()
    {
        static::setSessionGCProbability(1);
        static::setSessionGCDivisor(1);
        static::setSavePath(storagePath('Maya/Sessions'));
        static::setName('DAVINCI_SID');
        static::useCookies(true);
        static::useOnlyCookies(true);
        static::setSessionSidBitsPerCharacter(5);
        static::setSessionSidLength(192);
    }

    public static function set($key, $value)
    {
        $keys = explode('.', $key);
        $session = &$_SESSION;

        foreach ($keys as $nestedKey) {
            if (!isset($session[$nestedKey]) || !is_array($session[$nestedKey])) {
                $session[$nestedKey] = [];
            }
            $session = &$session[$nestedKey];
        }

        $session = $value;
    }

    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $session = $_SESSION;

        foreach ($keys as $nestedKey) {
            if (isset($session[$nestedKey])) {
                $session = $session[$nestedKey];
            } else {
                return $default;
            }
        }

        return $session;
    }

    public static function exists($key)
    {
        $keys = explode('.', $key);
        $session = $_SESSION;

        foreach ($keys as $nestedKey) {
            if (isset($session[$nestedKey])) {
                $session = $session[$nestedKey];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a session has started.
     *
     * @return bool
     */
    public static function isStarted()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function forget($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $session = &$_SESSION;
        foreach ($keys as $key) {
            $keyParts = is_string($key) ? explode('.', $key) : $key;
            $lastKey = array_pop($keyParts);
            foreach ($keyParts as $nestedKey) {
                if (!isset($session[$nestedKey]) || !is_array($session[$nestedKey])) {
                    return;
                }
                $session = &$session[$nestedKey];
            }
            unset($session[$lastKey]);
        }
    }


    public static function flush()
    {
        session_unset();
    }

    public static function increment($key, $amount = 1)
    {
        $currentValue = static::get($key, 0);
        $newValue = $currentValue + $amount;
        static::set($key, $newValue);
    }

    public static function decrement($key, $amount = 1)
    {
        $currentValue = static::get($key, 0);
        $newValue = max($currentValue - $amount, 0);
        static::set($key, $newValue);
    }

    public static function missing($key)
    {
        return !static::exists($key);
    }

    public static function has($key)
    {
        $session = $_SESSION;
        $keys = explode('.', $key);

        foreach ($keys as $nestedKey) {
            if (isset($session[$nestedKey])) {
                $session = $session[$nestedKey];
            } else {
                return false;
            }
        }
        return !empty($session);
    }

    public static function all()
    {
        return $_SESSION;
    }

    public static function flash($name = null, $message = null)
    {
        return flash($name, $message);
    }

    public static function error($name = null, $message = null)
    {
        return error($name, $message);
    }

    public static function regenerateID(bool $delete_old_session = false)
    {
        session_regenerate_id($delete_old_session);
    }

    public static function id()
    {
        return session_id();
    }

    public static function destroy()
    {
        session_destroy();
    }

    protected static function setName($name)
    {
        session_name($name);
    }

    public static function getName()
    {
        return session_name();
    }

    protected static function setExpiration($seconds)
    {
        ini_set('session.gc_maxlifetime', $seconds);
    }

    protected static function getExpiration()
    {
        return ini_get('session.gc_maxlifetime');
    }

    protected static function setSavePath($path)
    {
        session_save_path($path);
    }

    public static function getSavePath()
    {
        return session_save_path();
    }
}
