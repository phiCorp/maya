<?php

namespace Maya\Http;

use Maya\Http\traits\HasFileValidationRules;
use Maya\Http\traits\HasRunValidation;
use Maya\Http\traits\HasValidationRules;
use Maya\Support\URL;

class Request
{
    use HasValidationRules, HasFileValidationRules, HasRunValidation;

    protected $errorExist = false;
    protected $request = [];
    protected $query = [];
    protected $files = null;
    protected $errorVariablesName = [];
    protected $url = null;

    public function __construct()
    {
        $this->url = url();
        $this->request = $this->sanitizeArray($_POST);
        $this->query = $this->sanitizeArray($_GET);
        if (!empty($_FILES)) {
            $this->files = $_FILES;
        }
        $rules = $this->rules();
        empty($rules) ?: $this->runValidation($rules, $this->validatorMessages());
        return $this->errorRedirect();
    }

    protected function runValidation($rules, $messages = []): void
    {
        foreach ($rules as $att => $values) {
            $ruleArray = is_string($values) ? explode('|', $values) : $values;
            $customMessages = $messages[$att] ?? [];
            if (in_array('file', $ruleArray)) {
                unset($ruleArray[array_search('file', $ruleArray)]);
                $this->fileValidation($att, $ruleArray, $customMessages);
            } elseif (in_array('number', $ruleArray)) {
                $this->numberValidation($att, $ruleArray, $customMessages);
            } else {
                $this->normalValidation($att, $ruleArray, $customMessages);
            }
        }
    }
    public function validate(array $rules, array $messages = [])
    {
        $this->runValidation($rules, $messages);
        return $this->errorRedirect();
    }

    protected function rules()
    {
        return [];
    }

    protected function validatorMessages()
    {
        return [];
    }

    public function host()
    {
        return $this->server('SERVER_NAME');
    }

    public function httpHost()
    {
        return $this->server('HTTP_HOST');
    }

    public function file($name)
    {
        return $this->files[$name] ?? null;
    }

    public function files()
    {
        return $this->files;
    }

    protected function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = is_array($value) ? $this->sanitizeArray($value) : htmlentities($value, ENT_QUOTES, 'UTF-8');
        }
        return $sanitized;
    }

    public function __set($name, $value)
    {
        $this->setInput($name, $value);
    }

    public function setInput($name, $value)
    {
        $this->setArrayValue($name, $value, $this->request);
    }

    public function setQuery($name, $value)
    {
        $this->setArrayValue($name, $value, $this->query);
    }

    private function setArrayValue($name, $value, &$array)
    {
        $array[$name] = htmlentities($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public function __get($name)
    {
        return $this->request[$name] ?? $this->query[$name] ?? null;
    }
    public function input($name, $default = null)
    {
        return $this->request[$name] ?? $default;
    }

    public function query($name, $default = null)
    {
        return $this->query[$name] ?? $default;
    }

    public function ip(): ?string
    {
        $IP = match (true) {
            isset($_SERVER['HTTP_CLIENT_IP']) => $_SERVER['HTTP_CLIENT_IP'],
            isset($_SERVER['HTTP_X_FORWARDED_FOR']) => $_SERVER['HTTP_X_FORWARDED_FOR'],
            isset($_SERVER['HTTP_X_FORWARDED']) => $_SERVER['HTTP_X_FORWARDED'],
            isset($_SERVER['HTTP_FORWARDED_FOR']) => $_SERVER['HTTP_FORWARDED_FOR'],
            isset($_SERVER['HTTP_FORWARDED']) => $_SERVER['HTTP_FORWARDED'],
            isset($_SERVER['REMOTE_ADDR']) => $_SERVER['REMOTE_ADDR'],
            default => null,
        };
        $IP = $IP ?? null;
        if ($IP && filter_var($IP, FILTER_VALIDATE_IP)) {
            if ($IP == '::1') {
                $IP = '127.0.0.1';
            }
            return $IP;
        }
        return null;
    }

    public function url()
    {
        return $this->url->current();
    }

    public function fullUrl()
    {
        return $this->url->full();
    }

    public function all()
    {
        return $this->request;
    }

    public function allQuery()
    {
        return $this->query;
    }

    public function segment(int $index, $default = null)
    {
        return $this->segments()[$index] ?? $default;
    }

    public function segments()
    {
        return explode("/", trim(parse_url($this->uri(), PHP_URL_PATH), '/'));
    }

    public function method(): string
    {
        $method_field = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method_field == 'POST') {
            if (isset($_POST['_method'])) {
                if (strtoupper($_POST['_method']) == "PUT") {
                    $method_field = "PUT";
                } elseif (strtoupper($_POST['_method']) == "DELETE") {
                    $method_field = "DELETE";
                }
            }
        }
        return $method_field;
    }

    public function server($key, $default = null)
    {
        return $_SERVER[$key] ?? $default;
    }

    public function user()
    {
        return user();
    }

    public function headers()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[ucwords(strtolower(str_replace('_', '-', substr($key, 5))), '-')] = $value;
            }
        }
        return $headers;
    }

    public function uri()
    {
        return $this->url->path();
    }
}
