<?php

namespace Maya\Database\Traits;

trait HasRelation
{

    protected function hasOne($model, $foreignKey = 'id', $localKey = 'id')
    {
        if ($this->prop[$this->primaryKey]) {
            $modelObject = new $model();
            return $modelObject->getHasOneRelation($this->table, $foreignKey, $localKey, $this->$localKey);
        }
    }

    public function getHasOneRelation($table, $foreignKey, $otherKey, $otherKeyValue)
    {
        $this->setSql("SELECT `b`.* FROM `{$table}` AS `a` JOIN " . $this->getTableName() . " AS `b` on `a`.`{$otherKey}` = `b`.`{$foreignKey}` ");
        $this->setWhere('AND', "`a`.`$otherKey` = ? ");
        $this->table = 'b';
        $this->addValue($otherKeyValue);
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        if ($data)
            return $this->arrayToAttributes($data);
        return null;
    }

    protected function hasMany($model, $foreignKey = 'id', $otherKey = 'id')
    {
        if ($this->prop[$this->primaryKey]) {
            $modelObject = new $model;
            return $modelObject->getHasManyRelation($this->table, $foreignKey, $otherKey, $this->$otherKey);
        }
    }

    public function getHasManyRelation($table, $foreignKey, $otherKey, $otherKeyValue): static
    {
        $this->setSql("SELECT `b`.* FROM `{$table}` AS `a` JOIN " . $this->getTableName() . " AS `b` on `a`.`{$otherKey}` = `b`.`{$foreignKey}` ");
        $this->setWhere('AND', "`a`.`$otherKey` = ? ");
        $this->table = 'b';
        $this->addValue($otherKeyValue);
        return $this;
    }

    protected function belongsTo($model, $foreignKey = 'id', $localKey = 'id')
    {
        if ($this->prop[$this->primaryKey]) {
            $modelObject = new $model();
            return $modelObject->getBelongsToRelation($this->table, $foreignKey, $localKey, $this->$foreignKey);
        }
    }

    public function getBelongsToRelation($table, $foreignKey, $otherKey, $foreignKeyValue)
    {
        $this->setSql("SELECT `b`.* FROM `{$table}` AS `a` JOIN " . $this->getTableName() . " AS `b` on `a`.`{$foreignKey}` = `b`.`{$otherKey}` ");
        $this->setWhere('AND', "`a`.`$foreignKey` = ? ");
        $this->table = 'b';
        $this->addValue($foreignKeyValue);
        $statement = $this->executeQuery();
        $data = $statement->fetch();
        if ($data)
            return $this->arrayToAttributes($data);
        return null;
    }

    protected function belongsToMany($model, $commonTable, $localKey, $middleForeignKey, $middleRelation, $foreignKey)
    {
        if ($this->prop[$this->primaryKey]) {
            $modelObject = new $model();
            return $modelObject->getBelongsToManyRelation($this->table, $commonTable, $localKey, $this->$localKey, $middleForeignKey, $middleRelation, $foreignKey);
        }
    }

    protected function getBelongsToManyRelation($table, $commonTable, $localKey, $localKeyValue, $middleForeignKey, $middleRelation, $foreignKey): static
    {
        $this->setSql("SELECT `c`.* FROM ( SELECT `b`.* FROM `{$table}` AS `a` JOIN `{$commonTable}` AS `b` on `a`.`{$localKey}` = `b`.`{$middleForeignKey}` WHERE  `a`.`{$localKey}` = ? ) AS `relation` JOIN " . $this->getTableName() . " AS `c` ON `relation`.`{$middleRelation}` = `c`.`$foreignKey`");
        $this->addValue($localKeyValue);
        $this->table = 'c';
        return $this;
    }
}
