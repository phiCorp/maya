<?php

namespace Maya\Http\traits;

use Maya\Database\Connection\Connection;

trait HasValidationRules
{

    public function normalValidation($name, $ruleArray, $messages): void
    {
        foreach ($ruleArray as $rule) {
            $customMessage = $messages[$rule] ?? null;
            if ($rule == 'required') {
                $this->required($name, $customMessage);
            } elseif (str_starts_with($rule, "max:")) {
                $rule = str_replace('max:', "", $rule);
                $this->maxStr($name, $rule, $customMessage);
            } elseif (str_starts_with($rule, "min:")) {
                $rule = str_replace('min:', "", $rule);
                $this->minStr($name, $rule, $customMessage);
            } elseif (str_starts_with($rule, "exists:")) {
                $rule = str_replace('exists:', "", $rule);
                $rule = explode(',', $rule);
                $key = !isset($rule[1]) ? null : $rule[1];
                $connection = $rule[2] ?? 'default';
                $this->existsIn($name, $rule[0], $key, $connection, $customMessage);
            } elseif (str_starts_with($rule, "unique:")) {
                $rule = str_replace('unique:', "", $rule);
                $rule = explode(',', $rule);
                $key = !isset($rule[1]) ? null : $rule[1];
                $connection = $rule[2] ?? 'default';
                $this->unique($name, $rule[0], $key, $connection, $customMessage);
            } elseif ($rule == 'confirmed') {
                $this->confirm($name, $customMessage);
            } elseif ($rule == 'email') {
                $this->email($name, $customMessage);
            } elseif ($rule == 'date') {
                $this->date($name, $customMessage);
            } elseif (str_starts_with($rule, "regex:")) {
                $rule = str_replace('regex:', "", $rule);
                $this->regex($name, $rule, $customMessage);
            }
        }
    }

    public function numberValidation($name, $ruleArray, $messages): void
    {
        foreach ($ruleArray as $rule) {
            $customMessage = $messages[$rule] ?? null;
            if ($rule == 'required')
                $this->required($name, $customMessage);
            elseif (str_starts_with($rule, "max:")) {
                $rule = str_replace('max:', "", $rule);
                $this->maxNumber($name, $rule, $customMessage);
            } elseif (str_starts_with($rule, "min:")) {
                $rule = str_replace('min:', "", $rule);
                $this->minNumber($name, $rule, $customMessage);
            } elseif (str_starts_with($rule, "exists:")) {
                $rule = str_replace('exists:', "", $rule);
                $rule = explode(',', $rule);
                $key = !isset($rule[1]) ? null : $rule[1];
                $connection = $rule[2] ?? 'default';
                $this->existsIn($name, $rule[0], $key, $connection, $customMessage);
            } elseif ($rule == 'number') {
                $this->number($name, $customMessage);
            } elseif (str_starts_with($rule, "regex:")) {
                $rule = str_replace('regex:', "", $rule);
                $this->regex($name, $rule, $customMessage);
            }
        }
    }

    protected function regex($name, $regex, $messages = null)
    {
        if ($this->checkFieldExist($name)) {
            if (!preg_match($regex, $this->request[$name]) && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "error message for regex");
            }
        }
    }

    protected function maxStr($name, $count, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if (strlen($this->request[$name]) > $count && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "max length equal or lower than $count character");
            }
        }
    }

    protected function minStr($name, $count, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if (strlen($this->request[$name]) < $count && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "min length equal or upper than $count character");
            }
        }
    }

    protected function maxNumber($name, $count, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if ($this->request[$name] > $count && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "max number equal or lower than $count character");
            }
        }
    }

    protected function minNumber($name, $count, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if ($this->request[$name] < $count && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "min number equal or upper than $count character");
            }
        }
    }

    protected function required($name, $messages = null): void
    {
        if ((!isset($this->request[$name]) || $this->request[$name] === '') && $this->checkFirstError($name)) {
            $this->setError($name, $messages ?? "$name is required");
        }
    }

    protected function number($name, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if (!is_numeric($this->request[$name]) && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "$name must be number format");
            }
        }
    }

    protected function date($name, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $this->request[$name]) && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "$name must be date format");
            }
        }
    }

    protected function email($name, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if (!filter_var($this->request[$name], FILTER_VALIDATE_EMAIL) && $this->checkFirstError($name)) {
                $this->setError($name, $messages ?? "$name must be email format");
            }
        }
    }

    protected function confirm($name, $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            $fieldName = "confirm_" . $name;
            if (!isset($this->$fieldName)) {
                $this->setError($name, " $name $fieldName not exist");
            } elseif ($this->$fieldName != $this->$name) {
                $this->setError($name, $messages ?? "$name confirmation does not match");
            }
        }
    }

    public function existsIn($name, $table, $field = "id", $connection = 'default', $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if ($this->checkFirstError($name)) {
                $value = $this->$name;
                $sql = "SELECT COUNT(*) FROM $table WHERE $field = ?";
                $statement = Connection::getConnection($connection)->prepare($sql);
                $statement->execute([$value]);
                $result = $statement->fetchColumn();
                if ($result == 0) {
                    $this->setError($name, $messages ?? "$name not already exist");
                }
            }
        }
    }

    public function unique($name, $table, $field = "id", $connection = 'default', $messages = null): void
    {
        if ($this->checkFieldExist($name)) {
            if ($this->checkFirstError($name)) {
                $value = $this->$name;
                $sql = "SELECT COUNT(*) FROM $table WHERE $field = ?";
                $statement = Connection::getConnection($connection)->prepare($sql);
                $statement->execute([$value]);
                $result = $statement->fetchColumn();
                if ($result != 0) {
                    $this->setError($name, $messages ?? "$name must be unique");
                }
            }
        }
    }
}
