<?php

namespace Maya\Http\traits;


trait HasFileValidationRules
{
    public function fileValidation($name, $ruleArray): void
    {
        foreach ($ruleArray as $rule) {
            if ($rule == "required") {
                $this->fileRequired($name);
            } elseif (str_starts_with($rule, "mimes:")) {
                $rule = str_replace('mimes:', "", $rule);
                $rule = explode(',', $rule);
                $this->fileType($name, $rule);
            } elseif (str_starts_with($rule, "max:")) {
                $rule = str_replace('max:', "", $rule);
                $this->maxFile($name, $rule);
            } elseif (str_starts_with($rule, "min:")) {
                $rule = str_replace('min:', "", $rule);
                $this->minFile($name, $rule);
            }
        }
    }


    protected function fileRequired($name): void
    {
        if (!isset($this->files[$name]['name']) || empty($this->files[$name]['name']) && $this->checkFirstError($name)) {
            $this->setError($name, "$name is required");
        }
    }

    protected function fileType($name, $typesArray): void
    {
        if ($this->checkFirstError($name) && $this->checkFileExist($name)) {
            $currentFileType = explode('/', $this->files[$name]['type'])[1];
            if (!in_array($currentFileType, $typesArray)) {
                $this->setError($name, "$name type must be " . implode(', ', $typesArray));
            }
        }
    }

    protected function maxFile($name, $size): void
    {
        $size = $size * 1024;
        if ($this->checkFirstError($name) && $this->checkFileExist($name)) {
            if ($this->files[$name]['size'] > $size) {
                $this->setError($name, "$name size must be lower than " . ($size / 1024) . " KB");
            }
        }
    }

    protected function minFile($name, $size): void
    {
        $size = $size * 1024;
        if ($this->checkFirstError($name) && $this->checkFileExist($name)) {
            if ($this->files[$name]['size'] < $size) {
                $this->setError($name, "$name size must be upper than " . ($size / 1024) . " KB");
            }
        }
    }
}
