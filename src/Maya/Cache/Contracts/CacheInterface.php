<?php

namespace Maya\Cache\Contracts;

interface CacheInterface
{
    /**
     * Store an item in the cache.
     *
     * @param string $key The key under which to store the data.
     * @param mixed $data The data to store in cache.
     * @param int|null $expiration Optional. The expiration time in seconds.
     * @param array $tags Optional. Array of tags for tagging the cache.
     * @param bool|null $encrypt Optional. Whether to encrypt the data.
     * @param bool|null $compress Optional. Whether to compress the data.
     * @return void
     */
    public static function set($key, $data, $expiration = null, $tags = [], $encrypt = null, $compress = null);

    /**
     * Retrieve an item from the cache.
     *
     * @param string $key The key of the cached item.
     * @return mixed The cached data or null if not found or expired.
     */
    public static function get($key);

    /**
     * Determine if an item exists in the cache.
     *
     * @param string $key The key of the cached item.
     * @return bool True if the item exists and is not expired, otherwise false.
     */
    public static function has($key);

    /**
     * Remove an item from the cache.
     *
     * @param string $key The key of the item to remove.
     * @return void
     */
    public static function delete($key);

    /**
     * Remove all cached items associated with a specific tag.
     *
     * @param string $tag The tag by which to remove items.
     * @return void
     */
    public static function deleteByTag($tag);

    /**
     * Clear all cache data.
     *
     * @return void
     */
    public static function clear();

    /**
     * Retrieve an item from the cache or store the result of a callback if it does not exist.
     *
     * @param string $key The key of the cached item.
     * @param callable $callback The callback that will generate the value if not found in cache.
     * @param int|null $expiration Optional. The expiration time in seconds.
     * @param array $tags Optional. Array of tags for tagging the cache.
     * @return mixed The cached or newly generated data.
     */
    public static function remember($key, $callback, $expiration = null, $tags = []);
}
