<?php

namespace Maya\Database\Model;

use JsonSerializable;
use Maya\Database\Connection\Connection;
use Maya\Database\Traits\HasAttributes;
use Maya\Database\Traits\HasCRUD;
use Maya\Database\Traits\HasMethodCaller;
use Maya\Database\Traits\HasQueryBuilder;
use Maya\Database\Traits\HasRelation;
use PDO;

abstract class Model implements JsonSerializable
{
    use HasAttributes, HasCRUD, HasMethodCaller, HasQueryBuilder, HasRelation;

    protected string $table;
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected string $primaryKey = 'id';
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';
    protected $deletedAt = null;
    protected $collection = [];
    protected string $connection = 'default';
    protected $prop = [];

    public function getConnection(): PDO
    {
        return Connection::getConnection($this->connection);
    }

    public function __set($key, $value)
    {
        $this->prop[$key] = $value;
    }

    public function __get($name)
    {
        return $this->prop[$name] ?? null;
    }

    public function jsonSerialize(): array
    {
        return $this->prop;
    }

    public function __toString(): string
    {
        return json_encode($this);
    }
}
