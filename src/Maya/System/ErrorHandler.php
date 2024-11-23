<?php

namespace Maya\System;

use Maya\Http\Request;
use Maya\System\Traits\ErrorAssets;
use Throwable;

class ErrorHandler
{
    use ErrorAssets;

    public function handle($error): void
    {
        http_response_code(500);

        if (function_exists('config') && config("APP.DEBUG", false)) {
            if ($error instanceof Throwable) {
                $type = get_class($error);
                $message = $error->getMessage();
                $file = $error->getFile();
                $line = $error->getLine();
                $this->renderErrorPage($type, $message, $file, $line);
            } else {
                $this->renderErrorPage('Unknown Error', 'An unknown error occurred.', 'Unknown File', 0);
            }
        } else {
            $this->renderBeautifulErrorPage(500);
        }
    }

    public function abort(int $statusCode, string $message = ''): void
    {
        http_response_code($statusCode);

        if (empty($message)) {
            $message = $this->getMessageForStatusCode($statusCode);
        }

        if (function_exists('basePath') && file_exists($errorPage = basePath('View/errors/' . $statusCode . '.php'))) {
            include $errorPage;
        } else {
            $this->renderBeautifulErrorPage($statusCode, $message);
        }


        exit();
    }


    private function renderBeautifulErrorPage(int $statusCode, $message = null): void
    {
        if (is_null($message)) {
            $message = $this->getMessageForStatusCode($statusCode);
        }
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$statusCode} Error</title>
            <style>{$this->styles}</style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-box">
                    <h1 style="text-align:center">{$statusCode}</h1>
                    <p style="text-align:center">{$message}</p>
                    <p style="text-align:center">Please try again later or contact support.</p>
                </div>
            </div>
            <script>{$this->scripts}</script>
        </body>
        </html>
        HTML;
    }


    private function renderErrorPage($type, $message, $file, $line): void
    {
        $codeSnippet = $this->getCodeSnippet($file, $line);
        $phpVersion = PHP_VERSION;
        $davinciVersion = Application::VERSION;
        $methodField = request()->method();
        $hostName = request()->url();
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>{$this->styles}</style>
            <title>Error</title>
        </head>
        <body>
        <div class="error-container">
        <div class="error-box">
            <div class="error-title"
                style="display: flex;align-items: baseline;justify-content: space-between;width: 100%;">
                <h2>{$type}</h2>
                <h3>{$methodField} {$hostName}</h3>
            </div>
            <div class="error-message">{$message}</div>
            <div class="error-suggestion">{$this->getErrorSuggestion($type,$message)}</div>
            <h3>{$file}:{$line}</h3>
            <pre class="code-snippet">{$codeSnippet}</pre>
        </div>
        <span>
            PHP {$phpVersion}, Davinci {$davinciVersion}
        </span>
        </div>
        <script>{$this->scripts}</script>
        </body>
        </html>
        HTML;
    }
    private function findClosestDefinedFunction(string $functionName): ?string
    {
        $definedFunctions = get_defined_functions()['internal'];
        $userDefinedFunctions = get_defined_functions()['user'];

        $allFunctions = array_merge($definedFunctions, $userDefinedFunctions);

        $closestFunction = null;
        $shortestDistance = -1;

        foreach ($allFunctions as $definedFunction) {
            $distance = levenshtein($functionName, $definedFunction);

            if ($shortestDistance == -1 || $distance < $shortestDistance) {
                if ($distance <= 2) {
                    $shortestDistance = $distance;
                    $closestFunction = $definedFunction;
                }
            }
        }

        return $closestFunction;
    }

    private function findClosestDefinedClass(string $className): ?string
    {
        $declaredClasses = get_declared_classes();
        $closestClass = null;
        $shortestDistance = -1;

        foreach ($declaredClasses as $declaredClass) {
            if (strpos($declaredClass, $className) !== false) {
                return $declaredClass;
            }

            $distance = levenshtein($className, $declaredClass);
            if ($shortestDistance == -1 || $distance < $shortestDistance) {
                if ($distance <= 2) {
                    $shortestDistance = $distance;
                    $closestClass = $declaredClass;
                }
            }
        }

        return $closestClass;
    }




    private function getErrorSuggestion($type, $message): string
    {
        if (strpos($message, 'Call to undefined function') !== false) {
            preg_match('/Call to undefined function (\w+)/', $message, $matches);
            if (isset($matches[1])) {
                $functionName = $matches[1];
                $suggestedFunction = $this->findClosestDefinedFunction($functionName);
                if ($suggestedFunction) {
                    return "Check if the function '{$functionName}' is defined or if you have a typo. Did you mean '{$suggestedFunction}'?";
                }
            }
        }

        if (strpos($message, 'Class "') !== false) {
            preg_match('/Class "(.*?)" not found/', $message, $matches);
            if (isset($matches[1])) {
                $className = $matches[1];
                $closestClass = $this->findClosestDefinedClass($className);
                if ($closestClass) {
                    return sprintf("Check if the class '%s' is defined or if you have a typo. Did you mean '%s'?", $className, $closestClass);
                } else {
                    return sprintf("Class '%s' not found. Ensure it is defined correctly.", $className);
                }
            }
        }

        switch ($type) {
            case 'ErrorException':
                return 'Check your code for syntax errors or missing files.';

            case 'PDOException':
                return 'Review your database connection settings and validate your SQL queries for correctness.';

            case 'TypeError':
                return 'Ensure you are passing the correct data types to functions. Check for nullable types and default values.';

            case 'NotFoundHttpException':
                return 'Verify the URL you are trying to access is correct and check your routing configuration for any issues.';

            case 'MissingArgumentException':
                return 'Check the function definition and ensure all required arguments are provided.';

            default:
                return 'Refer to the official documentation for additional insights and troubleshooting steps.';
        }
    }

    public function getMessageForStatusCode(int $statusCode): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => "I'm a teapot",
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        ];

        return $messages[$statusCode] ?? 'An error occurred';
    }

    private function getCodeSnippet($file, $line, $linesBefore = 5, $linesAfter = 5)
    {
        if (!is_file($file)) {
            return 'Unable to load file.';
        }

        $fileContent = file($file);
        $start = max(0, $line - $linesBefore - 1);
        $end = min(count($fileContent), $line + $linesAfter);

        $snippet = array_slice($fileContent, $start, $end - $start, true);
        $highlightedSnippet = '';

        foreach ($snippet as $lineNumber => $codeLine) {
            $highlightClass = ($lineNumber + 1 == $line) ? 'highlight' : '';
            $highlightedSnippet .= sprintf(
                '<span class="line-number">%d</span> <span class="code-line %s">%s</span>',
                $lineNumber + 1,
                $highlightClass,
                htmlspecialchars($codeLine)
            );
        }

        return $highlightedSnippet;
    }
}
