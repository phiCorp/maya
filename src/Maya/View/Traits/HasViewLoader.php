<?php

namespace Maya\View\Traits;

use Exception;
use Maya\Support\Config;

trait HasViewLoader
{
    private $viewNameArray = [];

    private function viewLoader($dir): string
    {
        $dir = trim($dir, " ./");
        $dir = str_replace(".", "/", $dir);
        $viewFile = config('VIEW.VIEW_DIRECTORY') . DIRECTORY_SEPARATOR . $dir . config('VIEW.VIEW_FILE_EXTENSION');
        if (file_exists($viewFile)) {
            $this->registerView($dir);
            return htmlentities(file_get_contents($viewFile), ENT_COMPAT);
        } else {
            throw new Exception($dir . ' not found.');
        }
    }

    private function registerView($view): void
    {
        $this->viewNameArray[] = $view;
    }
}
