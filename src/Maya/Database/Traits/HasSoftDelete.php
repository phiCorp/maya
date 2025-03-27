<?php

namespace Maya\Database\Traits;

trait HasSoftDelete
{

    protected function deleteMethod($id = null)
    {
        $object = $this;
        $this->resetQuery();
        if ($id) {
            $object = $this->findMethod($id);
            $this->resetQuery();
        }
        if ($object) {
            $object->resetQuery();
            $object->setSql("UPDATE " . $object->getTableName() . " SET " . $this->getAttributeName($this->deletedAt) . " = NOW() ");
            $object->setWhere("AND", $this->getAttributeName($object->primaryKey) . " = ?");
            $object->addValue($object->{$object->primaryKey});
            return $object->executeQuery() == true;
        }
        return false;
    }

    protected function allMethod()
    {

        $this->setSql("SELECT * FROM " . $this->getTableName());
        $this->setWhere("AND", $this->getAttributeName($this->deletedAt) . " IS NULL ");
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
        $this->setWhere("AND", $this->getAttributeName($this->deletedAt) . " IS NULL ");
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        $this->setAllowedMethods(['increment', 'decrement', 'update', 'delete', 'save', 'restore', 'forceDelete']);
        if ($data)
            return $this->arrayToAttributes($data);
        return null;
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
        $this->setWhere("AND", $this->getAttributeName($this->deletedAt) . " IS NULL ");
        $statement = $this->executeQuery();
        $data = $statement->fetchAll();
        if ($data) {
            $this->arrayToObjects($data);
            return $this->collection;
        }
        return [];
    }

    protected function restoreMethod($id = null)
    {
        $object = $this;
        $this->resetQuery();
        if ($id) {
            $object = $this->findMethod($id);
            $this->resetQuery();
        }
        if ($object) {
            $object->resetQuery();
            $object->setSql("UPDATE " . $object->getTableName() . " SET " . $this->getAttributeName($this->deletedAt) . " = NULL ");
            $object->setWhere("AND", $this->getAttributeName($object->primaryKey) . " = ?");
            $object->addValue($object->{$object->primaryKey});
            return $object->executeQuery() == true;
        }
        return false;
    }

    protected function restoreAllMethod()
    {
        $this->resetQuery();
        $this->setSql("UPDATE " . $this->getTableName() . " SET " . $this->getAttributeName($this->deletedAt) . " = NULL ");
        $this->setWhere("AND", $this->getAttributeName($this->deletedAt) . " IS NOT NULL ");
        return $this->executeQuery() == true;
    }

    protected function restoreFromMethod($attribute, $value)
    {
        $this->resetQuery();
        $this->setSql("UPDATE " . $this->getTableName() . " SET " . $this->getAttributeName($this->deletedAt) . " = NULL ");
        $this->setWhere("AND", $this->getAttributeName($attribute) . " = ?");
        $this->addValue($value);
        return $this->executeQuery() == true;
    }

    protected function forceDeleteMethod($id = null)
    {
        $object = $this;
        $this->resetQuery();
        if ($id) {
            $object = $this->findMethod($id);
            $this->resetQuery();
        }
        if ($object) {
            $object->resetQuery();
            $object->setSql("DELETE FROM " . $object->getTableName());
            $object->setWhere("AND", $this->getAttributeName($object->primaryKey) . " = ?");
            $object->addValue($object->{$object->primaryKey});
            return $object->executeQuery() == true;
        }
        return false;
    }

    protected function forceDeleteAllMethod()
    {
        $this->resetQuery();
        $this->setSql("DELETE FROM " . $this->getTableName());
        $this->setWhere("AND", $this->getAttributeName($this->deletedAt) . " IS NOT NULL ");
        return $this->executeQuery() == true;
    }

    protected function forceDeleteFromMethod($attribute, $value)
    {
        $this->resetQuery();
        $this->setSql("DELETE FROM " . $this->getTableName());
        $this->setWhere("AND", $this->getAttributeName($attribute) . " = ?");
        $this->addValue($value);
        return $this->executeQuery() == true;
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
        $this->setWhere("AND", $this->getAttributeName($this->deletedAt) . " IS NULL ");
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        $this->setAllowedMethods(['increment', 'decrement', 'update', 'delete', 'save', 'restore', 'forceDelete']);
        if ($data)
            return $this->arrayToAttributes($data);
        return null;
    }

    protected function withTrashedMethod()
    {
        $this->ignoreDeletedAt();
        return $this;
    }

    public function countMethod($attribute = '*')
    {
        $this->setSql("SELECT COUNT($attribute) as count FROM " . $this->getTableName());
        $this->setWhere("AND", $this->getAttributeName($this->deletedAt) . " IS NULL ");
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        return $data['count'];
    }
}
