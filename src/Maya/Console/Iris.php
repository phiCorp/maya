<?php

namespace Maya\Console;

class Iris
{
    const RESET = "\033[0m";

    const BLACK = "\033[30m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";

    const BG_BLACK = "\033[40m";
    const BG_RED = "\033[41m";
    const BG_GREEN = "\033[42m";
    const BG_YELLOW = "\033[43m";
    const BG_BLUE = "\033[44m";
    const BG_MAGENTA = "\033[45m";
    const BG_CYAN = "\033[46m";
    const BG_WHITE = "\033[47m";

    const BOLD = "\033[1m";
    const UNDERLINE = "\033[4m";
    const BLINK = "\033[5m";
    const REVERSE = "\033[7m";
    const HIDDEN = "\033[8m";

    const LIGHT_BLACK = "\033[90m";
    const LIGHT_RED = "\033[91m";
    const LIGHT_GREEN = "\033[92m";
    const LIGHT_YELLOW = "\033[93m";
    const LIGHT_BLUE = "\033[94m";
    const LIGHT_MAGENTA = "\033[95m";
    const LIGHT_CYAN = "\033[96m";
    const LIGHT_WHITE = "\033[97m";

    const BG_LIGHT_BLACK = "\033[100m";
    const BG_LIGHT_RED = "\033[101m";
    const BG_LIGHT_GREEN = "\033[102m";
    const BG_LIGHT_YELLOW = "\033[103m";
    const BG_LIGHT_BLUE = "\033[104m";
    const BG_LIGHT_MAGENTA = "\033[105m";
    const BG_LIGHT_CYAN = "\033[106m";
    const BG_LIGHT_WHITE = "\033[107m";

    public static function simple(): SimpleMessage
    {
        return (new SimpleMessage());
    }

    public static function red($text)
    {
        return self::RED . $text . self::RESET;
    }

    public static function br($line = 1)
    {
        for ($i = 0; $i < $line; $i++) {
            echo "\n";
        }
    }

    public static function write(...$input)
    {
        $result = '';

        foreach ($input as $i) {
            if (is_null($i)) {
                $i = 'null';
            } elseif (is_bool($i)) {
                $i = $i ? 'true' : 'false';
            } elseif (is_array($i) || is_object($i)) {
                $i = json_encode($i, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif (is_resource($i)) {
                $i = get_resource_type($i);
            } else {
                $i = (string)$i;
            }

            $result .= $i;
        }

        echo $result;
    }

    public static function writeLine(...$input)
    {
        self::write(...$input);
        self::br();
    }

    public static function readLine($prompt = '', $default = null, $options = [])
    {
        $attempts = 0;
        $maxAttempts = $options['maxAttempts'] ?? 3;
        $timeout = $options['timeout'] ?? null;
        $maskInput = $options['maskInput'] ?? false;
        $validator = $options['validator'] ?? null;
        $errorMessage = $options['errorMessage'] ?? 'Invalid input, please try again.';
        $successMessage = $options['successMessage'] ?? null;
        $language = $options['language'] ?? 'en';
        $historyEnabled = $options['history'] ?? false;

        function getInputWithTimeout($prompt, $timeout)
        {
            echo $prompt;
            $read = [STDIN];
            $write = $except = null;
            $seconds = (int) floor($timeout);
            $microseconds = ($timeout - $seconds) * 1000000;

            if (stream_select($read, $write, $except, $seconds, $microseconds)) {
                return trim(fgets(STDIN));
            }

            return false;
        }

        while ($attempts < $maxAttempts) {
            if ($prompt) {
                echo "\033[1;36m" . $prompt . ($default ? " [$default]" : '') . ": \033[0m";
            }

            if ($timeout !== null) {
                $input = getInputWithTimeout('', $timeout);
                if ($input === false) {
                    echo "\n\033[1;31mInput timed out.\033[0m\n";
                    return self::error("Input timed out after $timeout seconds.");
                }
            } else {
                if ($maskInput) {
                    echo "\033[30;40m";
                }
                $input = trim(fgets(STDIN));
                if ($maskInput) {
                    echo "\033[0m";
                }
            }

            if (empty($input) && $default !== null) {
                $input = $default;
            }

            if ($historyEnabled) {
                file_put_contents('input_history.log', $input . PHP_EOL, FILE_APPEND);
            }

            if ($validator && is_callable($validator)) {
                if ($validator($input)) {
                    if ($successMessage) {
                        echo "\033[1;32m" . $successMessage . "\033[0m\n";
                    }
                    return $input;
                } else {
                    echo "\033[1;31m" . $errorMessage . "\033[0m\n";
                }
            } else {
                return $input;
            }

            $attempts++;
        }

        return self::error("Maximum number of attempts reached.");
    }

    public static function confirm($message, $default = 'y', $options = [])
    {
        $attempts = 0;
        $maxAttempts = $options['maxAttempts'] ?? 3;
        $errorMessage = $options['errorMessage'] ?? 'Invalid input. Please enter y (yes) or n (no).';
        $defaultText = strtoupper($default);

        if (!in_array(strtolower($default), ['y', 'n'])) {
            return self::error('Default value must be "y" or "n".');
        }

        while ($attempts < $maxAttempts) {
            echo "\033[1;36m" . $message . " [{$defaultText}]: \033[0m";

            $input = strtolower(trim(fgets(STDIN)));

            if (empty($input)) {
                $input = $default;
            }

            if (in_array($input, ['y', 'n'])) {
                return $input === 'y';
            } else {
                echo "\033[1;31m" . $errorMessage . "\033[0m\n";
            }

            $attempts++;
        }

        return self::error('Maximum number of attempts reached.');
    }

    public static function error($title, $description = null)
    {
        echo "\n" . self::bgRed("         ") . "\n";
        echo self::bgRed(self::white('  ERROR  ')) . self::LIGHT_RED . " $title" . self::RESET . "\n";
        echo self::bgRed("         ") . "\n";
        if (!is_null($description)) {
            echo "\n";
            echo self::red($description) . "\n";
            echo "\n";
        }
    }

    public static function success($title, $description = null)
    {
        echo "\n" . self::bgGreen("           ") . "\n";
        echo self::bgGreen(self::white('  SUCCESS  ')) . self::LIGHT_GREEN . " $title" . self::RESET . "\n";
        echo self::bgGreen("           ") . "\n";
        if (!is_null($description)) {
            echo "\n";
            echo self::green($description) . "\n";
            echo "\n";
        }
    }

    public static function info($title, $description = null)
    {
        echo "\n" . self::bgBlue("        ") . "\n";
        echo self::bgBlue(self::white('  INFO  ')) . self::LIGHT_BLUE . " $title" . self::RESET . "\n";
        echo self::bgBlue("        ") . "\n";
        if (!is_null($description)) {
            echo "\n";
            echo self::blue($description) . "\n";
            echo "\n";
        }
    }

    public static function warning($title, $description = null)
    {
        echo "\n" . self::bgYellow("           ") . "\n";
        echo self::bgYellow(self::white('  Warning  ')) . self::LIGHT_YELLOW . " $title" . self::RESET . "\n";
        echo self::bgYellow("           ") . "\n";
        if (!is_null($description)) {
            echo "\n";
            echo self::yellow($description) . "\n";
            echo "\n";
        }
    }

    public static function green($text)
    {
        return self::GREEN . $text . self::RESET;
    }

    public static function yellow($text)
    {
        return self::YELLOW . $text . self::RESET;
    }

    public static function blue($text)
    {
        return self::BLUE . $text . self::RESET;
    }

    public static function magenta($text)
    {
        return self::MAGENTA . $text . self::RESET;
    }

    public static function cyan($text)
    {
        return self::CYAN . $text . self::RESET;
    }

    public static function white($text)
    {
        return self::WHITE . $text . self::RESET;
    }

    public static function bgRed($text)
    {
        return self::BG_RED . $text . self::RESET;
    }

    public static function bgGreen($text)
    {
        return self::BG_GREEN . $text . self::RESET;
    }

    public static function bgYellow($text)
    {
        return self::BG_YELLOW . $text . self::RESET;
    }

    public static function bgBlue($text)
    {
        return self::BG_BLUE . $text . self::RESET;
    }

    public static function bgMagenta($text)
    {
        return self::BG_MAGENTA . $text . self::RESET;
    }

    public static function bgCyan($text)
    {
        return self::BG_CYAN . $text . self::RESET;
    }

    public static function bgWhite($text)
    {
        return self::BG_WHITE . $text . self::RESET;
    }

    public static function bold($text)
    {
        return self::BOLD . $text . self::RESET;
    }

    public static function underline($text)
    {
        return self::UNDERLINE . $text . self::RESET;
    }

    public static function reverse($text)
    {
        return self::REVERSE . $text . self::RESET;
    }

    public static function hidden($text)
    {
        return self::HIDDEN . $text . self::RESET;
    }

    public static function box($text)
    {
        $line = str_repeat('-', strlen($text) + 4);
        return "\n$line\n| $text |\n$line\n";
    }

    public static function unorderedList(array $items)
    {
        $output = "";
        foreach ($items as $item) {
            $output .= "- $item\n";
        }
        return $output;
    }

    public static function orderedList(array $items)
    {
        $output = "";
        foreach ($items as $index => $item) {
            $output .= ($index + 1) . ". $item\n";
        }
        return $output;
    }

    public static function divider($char = '-', $length = 50)
    {
        return str_repeat($char, $length) . PHP_EOL;
    }

    public static function typing($text, $delay = 100000)
    {
        foreach (str_split($text) as $char) {
            echo $char;
            usleep($delay);
        }
        echo PHP_EOL;
    }

    public static function emojiSuccess($text)
    {
        return "✅ " . $text;
    }

    public static function emojiError($text)
    {
        return "❌ " . $text;
    }

    public static function progressBar($percentage, $width = 50)
    {
        $progress = round(($percentage / 100) * $width);
        $bar = str_repeat('#', $progress) . str_repeat('-', $width - $progress);
        return "[" . $bar . "] " . $percentage . "%\r";
    }

    public static function time()
    {
        return date("H:i:s");
    }

    public static function date()
    {
        return date("Y-m-d");
    }

    public static function padLeft($text, $length, $char = ' ')
    {
        return str_pad($text, $length, $char, STR_PAD_LEFT);
    }

    public static function padRight($text, $length, $char = ' ')
    {
        return str_pad($text, $length, $char, STR_PAD_RIGHT);
    }

    public static function padCenter($text, $length, $char = ' ')
    {
        $leftPadding = floor(($length - strlen($text)) / 2);
        $rightPadding = $length - strlen($text) - $leftPadding;
        return str_repeat($char, $leftPadding) . $text . str_repeat($char, $rightPadding);
    }

    public static function text($text, $foregroundColor = self::RESET, $backgroundColor = self::RESET)
    {
        return $foregroundColor . $backgroundColor . $text . self::RESET;
    }

    public static function randomColor()
    {
        $colors = [self::RED, self::GREEN, self::YELLOW, self::BLUE, self::MAGENTA, self::CYAN, self::WHITE];
        return $colors[array_rand($colors)];
    }

    public static function randomBgColor()
    {
        $bgColors = [self::BG_RED, self::BG_GREEN, self::BG_YELLOW, self::BG_BLUE, self::BG_MAGENTA, self::BG_CYAN, self::BG_WHITE];
        return $bgColors[array_rand($bgColors)];
    }

    public static function multiLineColor($text, $foregroundColor = self::RESET, $backgroundColor = self::RESET)
    {
        $lines = explode("\n", $text);
        $output = "";
        foreach ($lines as $line) {
            $output .= self::text($line, $foregroundColor, $backgroundColor) . PHP_EOL;
        }
        return $output;
    }

    public static function gradientText($text, $startColor, $endColor)
    {
        $length = strlen($text);
        $output = "";
        for ($i = 0; $i < $length; $i++) {
            $r1 = hexdec(substr($startColor, 1, 2));
            $g1 = hexdec(substr($startColor, 3, 2));
            $b1 = hexdec(substr($startColor, 5, 2));

            $r2 = hexdec(substr($endColor, 1, 2));
            $g2 = hexdec(substr($endColor, 3, 2));
            $b2 = hexdec(substr($endColor, 5, 2));

            $r = (int)(($r1 + (($r2 - $r1) * $i / $length)));
            $g = (int)(($g1 + (($g2 - $g1) * $i / $length)));
            $b = (int)(($b1 + (($b2 - $b1) * $i / $length)));

            $colorCode = sprintf("\033[38;2;%d;%d;%dm", $r, $g, $b);
            $output .= $colorCode . $text[$i];
        }
        return $output . self::RESET;
    }

    public static function animatedLoadingBar($total = 100, $delay = 100000)
    {
        $steps = 50;
        for ($i = 0; $i <= $total; $i++) {
            $progress = (int)(($i / $total) * $steps);
            $bar = str_repeat('#', $progress) . str_repeat('-', $steps - $progress);
            echo "[" . self::randomColor() . $bar . self::RESET . "] " . $i . "%\r";
            usleep($delay);
        }
        echo PHP_EOL;
    }

    public static function circularLoading($cycles = 10, $delay = 100000)
    {
        $spinner = ['|', '/', '-', '\\'];
        for ($i = 0; $i < $cycles * count($spinner); $i++) {
            echo $spinner[$i % count($spinner)] . "\r";
            usleep($delay);
        }
        echo PHP_EOL;
    }
}
