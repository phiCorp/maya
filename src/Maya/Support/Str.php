<?php

namespace Maya\Support;

class Str
{
    private $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    public static function of($value)
    {
        return new self($value);
    }

    public function __toString()
    {
        return $this->value;
    }

    public function get()
    {
        return $this->value;
    }

    public function upper()
    {
        $this->value = mb_strtoupper($this->value, 'UTF-8');
        return $this;
    }

    public function lower()
    {
        $this->value = mb_strtolower($this->value, 'UTF-8');
        return $this;
    }

    public function trim($characters = null)
    {
        if ($characters) {
            $this->value = trim($this->value, $characters);
        } else {
            $this->value = trim($this->value);
        }
        return $this;
    }

    public function substr($start, $length = null)
    {
        $this->value = mb_substr($this->value, $start, $length, 'UTF-8');
        return $this;
    }

    public function replace($search, $replace)
    {
        $this->value = str_replace($search, $replace, $this->value);
        return $this;
    }

    public function length()
    {
        return mb_strlen($this->value, 'UTF-8');
    }

    public function contains($substring)
    {
        return mb_strpos($this->value, $substring, 0, 'UTF-8') !== false;
    }

    public function startsWith($prefix)
    {
        return mb_substr($this->value, 0, mb_strlen($prefix, 'UTF-8'), 'UTF-8') === $prefix;
    }

    public function endsWith($suffix)
    {
        return mb_substr($this->value, -mb_strlen($suffix, 'UTF-8'), null, 'UTF-8') === $suffix;
    }

    public function split($delimiter)
    {
        return array_map('trim', explode($delimiter, $this->value));
    }

    public function repeat($times)
    {
        $this->value = str_repeat($this->value, $times);
        return $this;
    }

    public function slug()
    {
        $this->value = preg_replace('/\s+/', '-', $this->value);
        $this->value = preg_replace('/[^A-Za-z0-9\-]+/', '', $this->value);
        $this->value = strtolower($this->value);
        return $this;
    }

    public function toArray()
    {
        return str_split($this->value);
    }

    public function reverse()
    {
        $this->value = mb_convert_encoding(strrev(mb_convert_encoding($this->value, 'UTF-16LE', 'UTF-8')), 'UTF-8', 'UTF-16LE');
        return $this;
    }

    public function capitalize()
    {
        $this->value = mb_convert_case($this->value, MB_CASE_TITLE, 'UTF-8');
        return $this;
    }

    public function random($length)
    {
        $characters = mb_substr($this->value, 0, $length, 'UTF-8');
        $this->value = substr(str_shuffle(str_repeat($characters, ceil($length / mb_strlen($characters)))), 0, $length);
        return $this;
    }

    public function find($substring)
    {
        $position = mb_strpos($this->value, $substring, 0, 'UTF-8');
        return $position !== false ? $position : -1;
    }

    public function padLeft($length, $padString = ' ')
    {
        $this->value = mb_str_pad($this->value, $length, $padString, STR_PAD_LEFT);
        return $this;
    }

    public function padRight($length, $padString = ' ')
    {
        $this->value = mb_str_pad($this->value, $length, $padString, STR_PAD_RIGHT);
        return $this;
    }

    public function isEmpty()
    {
        return $this->length() === 0;
    }

    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    public function containsAny(array $substrings)
    {
        foreach ($substrings as $substring) {
            if ($this->contains($substring)) {
                return true;
            }
        }
        return false;
    }

    public function containsAll(array $substrings)
    {
        foreach ($substrings as $substring) {
            if (!$this->contains($substring)) {
                return false;
            }
        }
        return true;
    }

    public function repeatString($string, $times)
    {
        $this->value = str_repeat($string, $times);
        return $this;
    }

    public function truncate($length, $suffix = '...')
    {
        if ($this->length() > $length) {
            $this->value = mb_substr($this->value, 0, $length - mb_strlen($suffix, 'UTF-8'), 'UTF-8') . $suffix;
        }
        return $this;
    }

    public function indexOf($substring)
    {
        return mb_strpos($this->value, $substring, 0, 'UTF-8');
    }

    public function lastIndexOf($substring)
    {
        return mb_strrpos($this->value, $substring, 0, 'UTF-8');
    }

    public function toUpperCase()
    {
        $this->value = mb_strtoupper($this->value, 'UTF-8');
        return $this;
    }

    public function toLowerCase()
    {
        $this->value = mb_strtolower($this->value, 'UTF-8');
        return $this;
    }

    public function replaceAll($search, $replace)
    {
        $this->value = preg_replace('/' . preg_quote($search, '/') . '/u', $replace, $this->value);
        return $this;
    }

    public function unique()
    {
        $this->value = implode('', array_unique(mb_str_split($this->value)));
        return $this;
    }

    public function when($condition, callable $callback)
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    public function whenEmpty(callable $callback)
    {
        return $this->when($this->isEmpty(), $callback);
    }

    public function whenNotEmpty(callable $callback)
    {
        return $this->when($this->isNotEmpty(), $callback);
    }

    public function whenStartsWith($prefix, callable $callback)
    {
        return $this->when($this->startsWith($prefix), $callback);
    }

    public function whenEndsWith($suffix, callable $callback)
    {
        return $this->when($this->endsWith($suffix), $callback);
    }

    public function whenContains($substring, callable $callback)
    {
        return $this->when($this->contains($substring), $callback);
    }
}
