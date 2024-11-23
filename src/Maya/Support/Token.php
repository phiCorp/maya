<?php

namespace Maya\Support;

class Token
{

    /**
     * Return the Token instance.
     *
     * @return $this
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * Generate a random token.
     *
     * @param int $length
     * @return string
     */
    public static function secure($length = 32)
    {
        return bin2hex(random_bytes(ceil($length / 2)));
    }

    /**
     * Generate a unique token using a prefix and current timestamp.
     *
     * @param string $prefix
     * @return string
     */
    public static function uniqueID($prefix = '')
    {
        return uniqid($prefix);
    }

    /**
     * Generate a CSRF token with an optional expiration time.
     *
     * @param int $expirationTime
     * @return string
     */
    public static function CSRF()
    {
        return self::secure(64);
    }
    /**
     * Generate a token with custom characters.
     *
     * @param int $length
     * @param string $characters
     * @return string
     */
    public static function custom($length = 32, $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
    {
        $characterCount = strlen($characters);
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, $characterCount - 1);
            $token .= $characters[$randomIndex];
        }
        return $token;
    }

    /**
     * Generate a token that includes letters and numbers.
     *
     * @param int $length
     * @return string
     */
    public static function alphaNumeric($length = 32)
    {
        return self::custom($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
    }

    /**
     * Generate a token that includes only letters.
     *
     * @param int $length
     * @return string
     */
    public static function letters($length = 32)
    {
        return self::custom($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Generate a token that includes only lowercase letters.
     *
     * @param int $length
     * @return string
     */
    public static function lettersLower($length = 32)
    {
        return self::custom($length, 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Generate a token that includes only uppercase letters.
     *
     * @param int $length
     * @return string
     */
    public static function lettersUpper($length = 32)
    {
        return self::custom($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * Generate a token that includes only numbers.
     *
     * @param int $length
     * @return string
     */
    public static function numbers($length = 32)
    {
        return self::custom($length, '0123456789');
    }

    /**
     * Generate a token that includes letters, numbers, and special characters.
     *
     * @param int $length
     * @return string
     */
    public static function complex($length = 32)
    {
        return self::custom($length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=[]{}|');
    }

    /**
     * Generate a token that includes hex characters.
     *
     * @param int $length
     * @return string
     */
    public static function hex($length = 32)
    {
        return self::custom($length, 'abcdef0123456789');
    }

    /**
     * Generate a token that includes letters, numbers, and special characters.
     *
     * @param int $length
     * @return string
     */
    public static function specialChar($length = 32)
    {
        return self::custom($length, '!@#$%^&*()_-+=[]{}|');
    }

    /**
     * Generate a UUID (Universally Unique Identifier) version 4.
     *
     * @return string
     */
    public static function UUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * Generate a token based on a given seed.
     *
     * @param string $seed
     * @param int $length
     * @return string
     */
    public static function fromSeed($seed, $length = 32)
    {
        srand(crc32($seed));
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $characterCount = strlen($characters);
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = rand(0, $characterCount - 1);
            $token .= $characters[$randomIndex];
        }

        return $token;
    }

    /**
     * Hash a token using a specified algorithm (e.g., SHA-256).
     *
     * @param string $token
     * @param string $algorithm
     * @return string
     */
    public static function hash($token, $algorithm = 'sha256')
    {
        return hash($algorithm, $token);
    }
}
