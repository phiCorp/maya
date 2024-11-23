<?php

namespace Maya\Support;

use ArrayAccess;

class Arr
{
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    public static function add(array $array, $key, $value): array
    {
        if (!array_key_exists($key, $array) || $array[$key] === null) {
            $array[$key] = $value;
        }
        return $array;
    }

    public static function collapse(array $array): array
    {
        $result = [];
        foreach ($array as $values) {
            if (is_array($values)) {
                $result = array_merge($result, self::collapse($values));
            } else {
                $result[] = $values;
            }
        }
        return $result;
    }

    public static function crossJoin(...$arrays): array
    {
        $result = [[]];
        foreach ($arrays as $array) {
            $newResult = [];
            foreach ($result as $oldValues) {
                foreach ($array as $value) {
                    $newResult[] = array_merge($oldValues, [$value]);
                }
            }
            $result = $newResult;
        }
        return $result;
    }

    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    public static function dot(array $array, string $prepend = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::dot($value, $prepend . $key . '.'));
            } else {
                $result[$prepend . $key] = $value;
            }
        }
        return $result;
    }
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    public static function exists(array $array, $key): bool
    {
        return array_key_exists($key, $array);
    }

    public static function first(array $array, callable $callback = null, $default = null)
    {
        if ($callback === null) {
            return empty($array) ? $default : reset($array);
        }
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        return $default;
    }

    public static function flatten(array $array): array
    {
        $result = [];
        foreach ($array as $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value));
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    public static function forget(array &$array, $key)
    {
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }
            $array = &$array[$key];
        }
        unset($array[array_shift($keys)]);
    }

    public static function get(array $array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }
        $keys = explode('.', $key);
        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                return $default;
            }
            $array = $array[$key];
        }
        return $array;
    }

    public static function has(array $array, $key)
    {
        if (is_null($key)) {
            return false;
        }
        $keys = (array) $key;
        foreach ($keys as $key) {
            $subKeyArray = $array;
            foreach (explode('.', $key) as $segment) {
                if (!isset($subKeyArray[$segment])) {
                    return false;
                }
                $subKeyArray = $subKeyArray[$segment];
            }
        }
        return true;
    }

    public static function hasAny(array $array, $keys)
    {
        foreach ((array) $keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }
        return false;
    }

    public static function isAssoc(array $array)
    {
        if (array() === $array) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    public static function isList(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    public static function join(array $array, $glue, $lastGlue = null)
    {
        if (count($array) === 0) {
            return '';
        }
        if (count($array) === 1) {
            return reset($array);
        }
        $last = array_pop($array);
        $joined = implode($glue, $array);
        if (is_null($lastGlue)) {
            return $joined . $glue . $last;
        }
        return $joined . $lastGlue . $last;
    }

    public static function keyBy(array $array, $key)
    {
        $result = [];
        foreach ($array as $item) {
            $result[$item[$key]] = $item;
        }
        return $result;
    }

    public static function last(array $array, $callback, $default = null)
    {
        if (empty($array)) {
            return $default;
        }
        $reversed = array_reverse($array, true);
        foreach ($reversed as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }
        return $default;
    }

    public static function map(array $array, callable $callback)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = call_user_func($callback, $value, $key);
        }
        return $result;
    }

    public static function mapWithKeys(array $array, callable $callback)
    {
        $result = [];
        foreach ($array as $key => $value) {
            [$innerKey, $innerValue] = array_values(call_user_func($callback, $value, $key));
            $result[$innerKey] = $innerValue;
        }
        return $result;
    }

    public static function only(array $array, array $keys)
    {
        return array_intersect_key($array, array_flip($keys));
    }

    public static function pluck(array $array, string $value, ?string $key = null)
    {
        $results = [];
        foreach ($array as $item) {
            $itemValue = self::dataGet($item, $value);
            $itemKey = $key ? self::dataGet($item, $key) : null;
            $results[$itemKey] = $itemValue;
        }
        return $results;
    }

    public static function prepend(array $array, $value, ?string $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
    }

    public static function prependKeysWith(array $array, string $prefix)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$prefix . $key] = $value;
        }
        return $result;
    }

    public static function pull(array &$array, $key, $default = null)
    {
        $value = self::get($array, $key, $default);
        self::forget($array, $key);
        return $value;
    }

    private static function dataGet($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }
        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } else {
                return $default;
            }
        }
        return $target;
    }
    public static function query(array $array)
    {
        $result = '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::query($value);
                if ($value !== '') {
                    $value = urlencode($key) . "[$value]";
                } else {
                    $value = urlencode($key);
                }
            } else {
                $value = urlencode($key) . '=' . urlencode($value);
            }
            if ($value !== '') {
                if ($result !== '') {
                    $result .= '&';
                }
                $result .= $value;
            }
        }
        return $result;
    }

    public static function random(array $array, int $num = 1)
    {
        $keys = array_rand($array, $num);
        if ($num === 1) {
            return $array[$keys];
        }
        return array_intersect_key($array, array_flip($keys));
    }

    public static function set(array &$array, string $key, $value)
    {
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
    }

    public static function shuffle(array $array)
    {
        shuffle($array);
        return $array;
    }

    public static function sort(array $array, ?callable $callback = null)
    {
        if ($callback !== null) {
            usort($array, function ($a, $b) use ($callback) {
                return $callback($a) <=> $callback($b);
            });
        } else {
            sort($array);
        }
        return $array;
    }

    public static function sortDesc(array $array, ?callable $callback = null)
    {
        if ($callback !== null) {
            usort($array, function ($a, $b) use ($callback) {
                return $callback($b) <=> $callback($a);
            });
        } else {
            rsort($array);
        }
        return $array;
    }

    public static function sortRecursive(array $array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sortRecursive($value);
            }
        }
        if (array_values($array) === $array) {
            sort($array);
        } else {
            ksort($array);
        }
        return $array;
    }

    public static function sortRecursiveDesc(array $array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sortRecursiveDesc($value);
            }
        }
        if (array_values($array) === $array) {
            rsort($array);
        } else {
            krsort($array);
        }
        return $array;
    }

    public static function toCssClasses(array $array)
    {
        $classes = [];
        foreach ($array as $key => $value) {
            if (is_int($key) || (bool) $value) {
                $classes[] = $key;
            }
        }
        return implode(' ', $classes);
    }

    public static function toCssStyles(array $array)
    {
        $styles = [];
        foreach ($array as $key => $value) {
            if (is_int($key) || (bool) $value) {
                $styles[] = $value;
            }
        }
        return implode('; ', $styles) . ';';
    }

    public static function undot(array $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $keys = explode('.', $key);
            $current = &$result;
            foreach ($keys as $i => $k) {
                if (!isset($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
            $current = $value;
        }
        return $result;
    }

    public static function whereNotNull(array $array)
    {
        return array_filter($array, function ($value) {
            return !is_null($value);
        });
    }

    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }
        return is_array($value) ? $value : [$value];
    }

    public static function where(array $array, callable $callback)
    {
        $filtered = [];
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }
}
