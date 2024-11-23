<?php

namespace Maya\Database\Traits;

trait HasAttributes
{

    private function registerAttribute($object, string $attribute, $value): void
    {

        $this->inCastsAttributes($attribute) ? $object->$attribute = $this->castDecodeValue($attribute, $value) : $object->$attribute = $value;
    }

    protected function arrayToAttributes(array $array, $object = null)
    {

        if (!$object) {
            $className = get_called_class();
            $object = new $className;
        }
        foreach ($array as $attribute => $value) {
            if ($this->inHiddenAttributes($attribute))
                continue;
            $this->registerAttribute($object, $attribute, $value);
        }
        return $object;
    }

    protected function arrayToObjects(array $array): void
    {

        $collection = [];

        foreach ($array as $value) {
            $object = $this->arrayToAttributes($value);
            $collection[] = $object;
        }

        $this->collection = $collection;
    }

    private function inHiddenAttributes($attribute): bool
    {

        return in_array($attribute, $this->hidden);
    }

    private function inCastsAttributes($attribute): bool
    {

        return in_array($attribute, array_keys($this->casts));
    }

    private function castDecodeValue($attributeKey, $value)
    {

        if ($this->casts[$attributeKey] == 'array' || $this->casts[$attributeKey] == 'object') {
            return unserialize($value);
        }

        return $value;
    }

    private function castEncodeValue($attributeKey, $value)
    {

        if ($this->casts[$attributeKey] == 'array' || $this->casts[$attributeKey] == 'object') {
            return serialize($value);
        }

        return $value;
    }

    private function arrayToCastEncodeValue($values): array
    {

        $newArray = [];
        foreach ($values as $attribute => $value) {
            $this->inCastsAttributes($attribute) ? $newArray[$attribute] = $this->castEncodeValue($attribute, $value) : $newArray[$attribute] = $value;
        }
        return $newArray;
    }
}
