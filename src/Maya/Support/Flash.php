<?php

namespace Maya\Support;

class Flash
{
    /**
     * Return the Flash instance.
     *
     * @return $this
     */
    public static function instance()
    {
        /**
         * Get an instance of the Flash class.
         *
         * @return Flash
         */
        return new static();
    }

    /**
     * Set a flash message by name.
     *
     * @param string $name    The name of the flash message.
     * @param mixed $message  The message to be stored in the flash session.
     */
    public static function set($name, $message)
    {
        return $_SESSION["flash"][$name] = $message;
    }

    /**
     * Get the flash message by name.
     *
     * @param string $name      The name of the flash message.
     * @param mixed $default    The default value to return if the flash message does not exist.
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        if (isset($_SESSION["temporary_flash"][$name])) {
            $temporary = $_SESSION["temporary_flash"][$name];
            unset($_SESSION["temporary_flash"][$name]);
            return $temporary;
        } else {
            return $default;
        }
    }

    /**
     * Check if an Flash message exists.
     *
     * @param string|null $name The name of the Flash. If not provided, checks if any Flash messages exist.
     *
     * @return bool|int If $name is provided, returns true if the Flash message exists, false otherwise. If $name is not provided, returns the count of existing Flash messages.
     */
    public static function exists($name = null)
    {
        if ($name === null) {
            return isset($_SESSION['temporary_flash']) === true ? count($_SESSION['temporary_flash']) : false;
        } else {
            return isset($_SESSION['temporary_flash'][$name]) === true;
        }
    }

    /**
     * Get all flash messages and clear the temporary flash storage.
     *
     * @return array|false  An array of flash messages, or false if there are no messages.
     */
    public static function all()
    {
        if (isset($_SESSION["temporary_flash"])) {
            $temporary = $_SESSION["temporary_flash"];
            unset($_SESSION["temporary_flash"]);
            return $temporary;
        } else {
            return false;
        }
    }
}
