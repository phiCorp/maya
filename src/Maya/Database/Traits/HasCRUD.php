<?php

namespace Maya\Database\Traits;

use Maya\Database\Connection\Connection;
use Maya\Database\Model\Model;


trait HasCRUD
{

    protected function incrementMethod($attribute, $amount = 1): Model
    {
        $this->$attribute += $amount;
        return $this->saveMethod();
    }

    protected function decrementMethod($attribute, $amount = 1): Model
    {
        $this->$attribute -= $amount;
        return $this->saveMethod();
    }

    protected function createMethod($values): Model
    {
        $values = $this->arrayToCastEncodeValue($values);
        $this->arrayToAttributes($values, $this);
        return $this->saveMethod();
    }

    protected function updateMethod($values): Model
    {
        $values = $this->arrayToCastEncodeValue($values);
        $this->arrayToAttributes($values, $this);
        return $this->saveMethod();
    }

    protected function deleteMethod($id = null)
    {
        $object = $this;
        $this->resetQuery();
        if ($id) {
            $object = $this->findMethod($id);
            $this->resetQuery();
        }
        $object->setSql("DELETE FROM " . $object->getTableName());
        if (isset($object->prop[$object->primaryKey])) {
            $object->setWhere("AND", $this->getAttributeName($this->primaryKey) . " = ? ");
            $object->addValue($object->prop[$object->primaryKey]);
        }
        return $object->executeQuery() == true;
    }

    protected function allMethod()
    {

        $this->setSql("SELECT * FROM " . $this->getTableName());
        $statement = $this->executeQuery();
        $data = $statement->fetchAll();
        if ($data) {
            $this->arrayToObjects($data);
            return $this->collection;
        }
        return [];
    }

    protected function findMethod($id, array $array = [])
    {
        if (empty($array)) {
            $fields = $this->getTableName() . '.*';
        } else {
            foreach ($array as $key => $field) {
                $array[$key] = $this->getAttributeName($field);
            }
            $fields = implode(' , ', $array);
        }
        $this->setSql("SELECT {$fields} FROM " . $this->getTableName());
        $this->setWhere("AND", $this->getAttributeName($this->primaryKey) . " = ? ");
        $this->addValue($id);
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        $this->setAllowedMethods(['increment', 'decrement', 'update', 'delete', 'save', 'restore', 'forceDelete']);
        if ($data)
            return $this->arrayToAttributes($data);
        return null;
    }

    protected function findFromMethod($attribute, $value, array $array = [])
    {
        if (empty($array)) {
            $fields = $this->getTableName() . '.*';
        } else {
            foreach ($array as $key => $field) {
                $array[$key] = $this->getAttributeName($field);
            }
            $fields = implode(' , ', $array);
        }
        $this->setSql("SELECT {$fields} FROM " . $this->getTableName());
        $this->setWhere("AND", $this->getAttributeName($attribute) . " = ? ");
        $this->addValue($value);
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        $this->setAllowedMethods(['increment', 'decrement', 'update', 'delete', 'save', 'restore', 'forceDelete']);
        if ($data)
            return $this->arrayToAttributes($data);
        return null;
    }

    protected function whereMethod($attribute, $firstValue = null, $secondValue = null): static
    {
        if ($attribute instanceof \Closure) {
            $this->groupWhere();
            $attribute($this);
            $this->endGroup();
        } else {
            if ($secondValue === null) {
                $condition = $this->getAttributeName($attribute) . ' = ?';
                $this->addValue($firstValue);
            } else {
                $condition = $this->getAttributeName($attribute) . ' ' . $firstValue . ' ?';
                $this->addValue($secondValue);
            }
            $operator = 'AND';
            $this->setWhere($operator, $condition);
        }

        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function orWhereMethod($attribute, $firstValue = null, $secondValue = null): static
    {
        if ($attribute instanceof \Closure) {
            $this->setWhere('OR', '(');
            $attribute($this);
            $this->setWhere('', ')');
        } else {
            if ($secondValue === null) {
                $condition = $this->getAttributeName($attribute) . ' = ?';
                $this->addValue($firstValue);
            } else {
                $condition = $this->getAttributeName($attribute) . ' ' . $firstValue . ' ?';
                $this->addValue($secondValue);
            }
            $operator = 'OR';
            $this->setWhere($operator, $condition);
        }

        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function whereNullMethod($attribute): static
    {

        $condition = $this->getAttributeName($attribute) . ' IS NULL ';
        $operator = 'AND';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function orWhereNullMethod($attribute): static
    {

        $condition = $this->getAttributeName($attribute) . ' IS NULL ';
        $operator = 'OR';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function whereNotNullMethod($attribute): static
    {
        $condition = $this->getAttributeName($attribute) . ' IS NOT NULL ';
        $operator = 'AND';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function orWhereNotNullMethod($attribute): static
    {

        $condition = $this->getAttributeName($attribute) . ' IS NOT NULL ';
        $operator = 'OR';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function whereInMethod($attribute, array $values)
    {
        $valuesArray = [];
        foreach ($values as $value) {
            $this->addValue($value);
            $valuesArray[] = '?';
        }
        $condition = $this->getAttributeName($attribute) . ' IN (' . implode(' , ', $valuesArray) . ')';
        $operator = 'AND';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function orWhereInMethod($attribute, array $values)
    {
        $valuesArray = [];
        foreach ($values as $value) {
            $this->addValue($value);
            $valuesArray[] = '?';
        }
        $condition = $this->getAttributeName($attribute) . ' IN (' . implode(' , ', $valuesArray) . ')';
        $operator = 'OR';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function whereBetweenMethod($attribute, $firstValue, $secondValue): static
    {
        $condition = $this->getAttributeName($attribute) . ' BETWEEN ? AND ?';
        $this->addValue($firstValue);
        $this->addValue($secondValue);
        $operator = 'AND';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function orWhereBetweenMethod($attribute, $firstValue, $secondValue): static
    {
        $condition = $this->getAttributeName($attribute) . ' BETWEEN ? AND ?';
        $this->addValue($firstValue);
        $this->addValue($secondValue);
        $operator = 'OR';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function whereRawMethod($rawCondition, $values = []): static
    {
        $condition = $rawCondition;
        foreach ($values as $value) {
            $this->addValue($value);
        }
        $operator = 'AND';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function orWhereRawMethod($rawCondition, $values = []): static
    {
        $condition = $rawCondition;
        foreach ($values as $value) {
            $this->addValue($value);
        }
        $operator = 'OR';
        $this->setWhere($operator, $condition);
        $this->setAllowedMethods(['orWhereNull', 'orWhereNotNull', 'orWhereIn', 'whereBetween', 'orWhereBetween', 'whereRaw', 'orWhereRaw', 'where', 'orWhere', 'whereIn', 'whereNull', 'whereNotNull', 'limit', 'orderBy', 'oldest', 'latest', 'first', 'last', 'get', 'count']);
        return $this;
    }

    protected function groupWhere(): static
    {
        $this->setWhere('', '(');
        return $this;
    }

    protected function endGroup(): static
    {
        $this->setWhere('', ')');
        return $this;
    }

    protected function orderByMethod($attribute = 'created_at', $expression = "ASC"): static
    {
        $this->setOrderBy($attribute, $expression);
        $this->setAllowedMethods(['limit', 'first', 'last', 'oldest', 'latest', 'get', 'orderBy']);
        return $this;
    }

    public function oldestMethod($attribute = 'created_at'): static
    {
        return $this->orderByMethod($attribute, 'ASC');
    }

    public function latestMethod($attribute = 'created_at'): static
    {
        return $this->orderByMethod($attribute, 'DESC');
    }

    protected function limitMethod($number = 25, $from = 0): static
    {
        $this->setLimit($from, $number);
        $this->setAllowedMethods(['limit', 'first', 'last', 'get', 'oldest', 'latest']);
        return $this;
    }

    protected function getMethod($array = [])
    {
        if ($this->sql == '') {
            if (empty($array)) {
                $fields = $this->getTableName() . '.*';
            } else {
                foreach ($array as $key => $field) {
                    $array[$key] = $this->getAttributeName($field);
                }
                $fields = implode(' , ', $array);
            }
            $this->setSql("SELECT $fields FROM " . $this->getTableName());
        }

        $statement = $this->executeQuery();
        $data = $statement->fetchAll();
        if ($data) {
            $this->arrayToObjects($data);
            return $this->collection;
        }
        return [];
    }

    public function countMethod($attribute = '*')
    {
        $this->setSql("SELECT COUNT($attribute) as count FROM " . $this->getTableName());
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        return $data['count'];
    }

    public function firstMethod($array = [])
    {
        $this->limitMethod(1, 0);
        $result = $this->getMethod($array);
        $this->setAllowedMethods(['increment', 'decrement', 'update', 'delete', 'save', 'restore', 'forceDelete']);
        return count($result) > 0 ? $result[0] : null;
    }

    public function lastMethod($array = [])
    {
        $this->orderByMethod($this->primaryKey, 'desc');
        $this->limitMethod(1, 0);
        $result = $this->getMethod($array);
        $this->setAllowedMethods(['increment', 'decrement', 'update', 'delete', 'save', 'restore', 'forceDelete']);
        return count($result) > 0 ? $result[0] : null;
    }

    protected function saveMethod(): static
    {

        $fillString = $this->fill();

        if (!isset($this->prop[$this->primaryKey])) {
            $this->setSql("INSERT INTO " . $this->getTableName() . " SET $fillString, " . $this->getAttributeName($this->createdAt) . " = NOW() ");
        } else {
            $this->setSql("UPDATE " . $this->getTableName() . " SET $fillString, " . $this->getAttributeName($this->updatedAt) . " = NOW() ");
            $this->setWhere("AND", $this->getAttributeName($this->primaryKey) . " = ?");
            $this->addValue($this->prop[$this->primaryKey]);
        }
        $this->executeQuery();
        $this->resetQuery();
        if (!isset($this->prop[$this->primaryKey])) {
            $object = $this->findMethod(Connection::lastInsertID($this->connection));
            $defaultVars = get_class_vars(get_called_class());
            $allVars = get_object_vars($object);
            $differentVars = array_diff(array_keys($allVars), array_keys($defaultVars));
            foreach ($differentVars as $attribute) {
                $this->inCastsAttributes($attribute) ? $this->registerAttribute($this, $attribute, $this->castEncodeValue($attribute, $object->$attribute)) : $this->registerAttribute($this, $attribute, $object->$attribute);
            }
        }
        $this->resetQuery();
        $this->setAllowedMethods(['update', 'delete', 'find', 'findFrom', 'increment', 'decrement', 'save', 'restore', 'forceDelete']);
        return $this;
    }

    protected function fill(): string
    {
        $fillArray = array();
        foreach ($this->fillable as $attribute) {
            if (isset($this->prop[$attribute])) {
                if ($this->prop[$attribute] === '') {
                    $this->prop[$attribute] = null;
                }
                $fillArray[] = $this->getAttributeName($attribute) . " = ?";
                $this->inCastsAttributes($attribute) ? $this->addValue($this->castEncodeValue($attribute, $this->$attribute)) : $this->addValue($this->$attribute);
            }
        }
        return implode(', ', $fillArray);
    }

}
