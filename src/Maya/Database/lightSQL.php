<?php

namespace Maya\Database;


use PDOException;
use PDOStatement;

class lightSQL
{
    public static function create($table, $data): bool
    {
        try {
            $sql = "INSERT INTO " . $table . "(" . implode(', ', array_keys($data)) . " , `created_at`) VALUES ( :" . implode(', :', array_keys($data)) . " , now() );;";
            $query = DB::getConnection()->prepare($sql);
            $query->execute($data);
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public static function update($table, $id, $data, $primaryKey = null): bool
    {
        $sql = "UPDATE " . $table . " SET";
        foreach ($data as $column => $value) {
            if ($value) {
                $sql .= " `" . $column . "` = ? ,";
            } else {
                $sql .= " `" . $column . "` = NULL ,";
            }
        }
        $sql .= " updated_at = now()";
        $sql .= $primaryKey == null ? " WHERE `id` = ?" : " WHERE `" . $primaryKey . "` = ?";
        try {
            $query = DB::getConnection()->prepare($sql);
            $query->execute(array_merge(array_filter(array_values($data)), [$id]));
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public static function delete($table, $valuePrimaryKey, $primaryKey = null): bool
    {
        try {
            $sql = $primaryKey != null ? "DELETE FROM " . $table . " WHERE " . $primaryKey . " = ? ;" : "DELETE FROM " . $table . " WHERE `id` = ? ;";
            $query = DB::getConnection()->prepare($sql);
            $query->execute([$valuePrimaryKey]);
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public static function select($sql, $values = null): bool|PDOStatement
    {
        try {
            $query = DB::getConnection()->prepare($sql);
            if ($values == null) {
                $query->execute();
            } else {
                $query->execute($values);
            }
            return $query;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function __call(string $name, array $arguments)
    {
        $instance = new self();
        call_user_func_array([$instance, $name], $arguments);
    }
}
