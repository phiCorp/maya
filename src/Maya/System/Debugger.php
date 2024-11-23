<?php

namespace Maya\System;

use Maya\System\Traits\DebugAssets;
use ReflectionClass;

class Debugger
{
    use DebugAssets;

    public function dump(mixed ...$vars): void
    {
        try {
            echo '<div class="debugger">';
            $this->loadAssets();

            foreach ($vars as $index => $var) {
                echo '<pre class="variable">';
                echo $this->renderVariable($var);
                echo '</pre>';
            }
            echo '</div>';
        } catch (\Exception $e) {
            echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
    }

    private function renderVariable(mixed $var, string $parent = ''): string
    {
        $id = $parent . uniqid('var_', true);

        if (is_array($var) || is_object($var)) {
            $output = is_array($var) ? 'Array(' . count($var) . ') ' : get_class($var) . ' ';
            $output .= '<span id="' . $id . '_btn" class="toggle-button" onclick="toggleElement(\'' . $id . '\')">[+]</span>';
            $output .= '<div id="' . $id . '" class="hidden">';

            if (empty($var)) {
                $output .= '<em class="null">(empty)</em>';
            } else {
                if (is_object($var)) {
                    $output .= $this->renderObject($var, $id);
                } else {
                    foreach ($var as $key => $value) {
                        $output .= '<strong class="highlight">' . htmlspecialchars($key) . ':</strong> ' . $this->renderVariable($value, $id . '_') . '<br>';
                    }
                }
            }

            $output .= '</div>';
        } else {
            $copyButton = '';
            if (is_string($var) || is_int($var)) {
                $copyButton = '<button style="background:none; border:none; cursor:pointer; color:#00aaff; margin-right:5px;" onclick="copyToClipboard(\'' . htmlspecialchars((string)$var) . '\')">copy</button>';
            }
            $output = $copyButton . $this->formatScalar($var);
        }

        return $output;
    }

    private function renderObject(object $object, string $id): string
    {
        $reflection = new ReflectionClass($object);
        $properties = $reflection->getProperties();
        $output = '';

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $key = $property->getName();
            $value = $property->getValue($object);

            $output .= '<strong class="highlight">' . htmlspecialchars($key) . ':</strong> ' . $this->renderVariable($value, $id . '_') . '<br>';
        }

        return $output;
    }

    private function formatScalar(mixed $var): string
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? '<span class="boolean-true">true</span>' : '<span class="boolean-false">false</span>';
            case 'NULL':
                return '<em class="null">null</em>';
            case 'integer':
                return '<span class="integer">' . $var . '</span>';
            case 'double':
                return '<span class="double">' . $var . '</span>';
            case 'string':
                return '<span class="string">"' . htmlspecialchars($var) . '"</span>';
            case 'resource':
                return '<em class="resource">resource</em>';
            case 'object':
                return '<em class="object">object</em>';
            case 'array':
                return '<em class="array">array</em>';
            case 'callable':
                return '<em class="callable">callable</em>';
            default:
                return htmlspecialchars(var_export($var, true));
        }
    }
}
