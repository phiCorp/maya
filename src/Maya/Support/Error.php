<?php

namespace Maya\Support;

class Error
{
    /**
     * Return the Error instance.
     *
     * @return $this
     */
    public static function instance()
    {
        /**
         * Get an instance of the Error class.
         *
         * @return Error
         */
        return new static();
    }

    /**
     * Set an error message.
     *
     * @param string $name The name of the error.
     * @param string $message The error message to be set.
     */
    public static function set($name, $message)
    {
        return $_SESSION["errorFlash"][$name] = $message;
    }

    /**
     * Get an error message.
     *
     * @param string $name The name of the error.
     * @param mixed $default The default value to return if the error message is not set.
     *
     * @return mixed The error message if found, or the default value if not found.
     */
    public static function get($name, $default = null)
    {
        if (isset($_SESSION["temporary_errorFlash"][$name])) {
            $temporary = $_SESSION["temporary_errorFlash"][$name];
            unset($_SESSION["temporary_errorFlash"][$name]);
            return $temporary;
        } else {
            return $default;
        }
    }

    /**
     * Check if an error message exists.
     *
     * @param string|null $name The name of the error. If not provided, checks if any error messages exist.
     *
     * @return bool|int If $name is provided, returns true if the error message exists, false otherwise. If $name is not provided, returns the count of existing error messages.
     */
    public static function exists($name = null)
    {
        if ($name === null) {
            return isset($_SESSION['temporary_errorFlash']) === true ? count($_SESSION['temporary_errorFlash']) : false;
        } else {
            return isset($_SESSION['temporary_errorFlash'][$name]) === true;
        }
    }

    /**
     * Get all error messages and clear them from the session.
     *
     * @return array|false An array of error messages if they exist, or false if none are found.
     */
    public static function all()
    {
        if (isset($_SESSION["temporary_errorFlash"])) {
            $temporary = $_SESSION["temporary_errorFlash"];
            unset($_SESSION["temporary_errorFlash"]);
            return $temporary;
        } else {
            return false;
        }
    }
}
