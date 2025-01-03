<?php

namespace Maya\Http\traits;

trait HasRunValidation
{
    protected function errorRedirect()
    {
        if (!$this->errorExist) {
            return $this->request;
        }
        back("/")->exec();
    }

    private function checkFirstError($name): bool
    {
        if (!error()->exists($name) && !in_array($name, $this->errorVariablesName)) {
            return true;
        }
        return false;
    }

    private function checkFieldExist($name): bool
    {
        return isset($this->request[$name]) && !empty($this->request[$name]);
    }

    private function checkFileExist($name): bool
    {
        if (isset($this->files[$name]['name'])) {
            if (!empty($this->files[$name]['name'])) {
                return true;
            }
        }
        return false;
    }

    private function setError($name, $errorMessage): void
    {
        $this->errorVariablesName[] = $name;
        error($name, $errorMessage);
        $this->errorExist = true;
    }
}
