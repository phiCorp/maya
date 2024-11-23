<?php

namespace Maya\Http;

class Redirect
{
    /**
     * The target URL for the redirection.
     *
     * @var string
     */
    protected $to;

    /**
     * The HTTP status code for the redirection.
     *
     * @var int
     */
    protected $status;

    /**
     * Additional HTTP headers to include in the redirection response.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Whether to use a secure (https) URL for the redirection.
     *
     * @var bool|null
     */
    protected $secure;

    /**
     * Indicates whether the redirection URL is external.
     *
     * @var bool
     */
    protected $isExternal;

    /**
     * The timeout for the redirection (automatic refresh) in seconds.
     *
     * @var int
     */
    protected $timeout;

    /**
     * The message to display during the timeout period.
     *
     * @var string|null
     */
    protected $timeoutMessage;

    /**
     * The custom HTML content for the timeout page.
     *
     * @var string|null
     */
    protected $timeoutPage = null;

    /**
     * Data to attach to the session flash data for the next request.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Error data to attach to the session for the next request.
     *
     * @var array
     */
    protected $withError = [];

    /**
     * Create a new Redirect instance or chain an existing one and set the target URL.
     *
     * @param string|null $to The URL to redirect to. If null, no redirection URL is initially set.
     * @param int $status The HTTP status code for the redirection (default is 302 Found).
     * @param array $headers Additional HTTP headers to include in the redirection response.
     * @param bool|null $secure Whether to use a secure (https) URL for the redirection.
     * @return Redirect The Redirect instance for chaining.
     */
    public static function redirect(?string $to = null, int $status = 302, array $headers = [], ?bool $secure = null): Redirect
    {
        return (new static())->to($to, $status, $headers, $secure);
    }

    /**
     * Set the target URL and other options for the redirection.
     *
     * @param string|null $to The URL to redirect to. If null, no redirection URL is set.
     * @param int $status The HTTP status code for the redirection (default is 302 Found).
     * @param array $headers Additional HTTP headers to include in the redirection response.
     * @param bool|null $secure Whether to use a secure (https) URL for the redirection.
     * @return $this The Redirect instance for chaining.
     */
    public function to(?string $to = null, int $status = 302, array $headers = [], ?bool $secure = null): Redirect
    {
        $this->to = $to;
        $this->status = $status;
        foreach ($headers as $name => $headerValue) {
            if (is_string($name)) {
                $this->headers[$name] = $headerValue;
            }
        }
        $this->secure = $secure;
        return $this;
    }

    /**
     * Set whether the redirection URL should use a secure (https) protocol.
     *
     * @param bool $secure Whether to use a secure (https) URL for the redirection.
     * @return $this The Redirect instance for chaining.
     */
    public function secure(bool $secure = true): Redirect
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * Set the HTTP status code for the redirection.
     *
     * @param int $status The HTTP status code for the redirection.
     * @return $this The Redirect instance for chaining.
     */
    public function status(int $status = 302): Redirect
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set one or more HTTP headers for the redirection response.
     *
     * @param array|string $headers An array of HTTP headers or a single header name (string).
     * @param string|null $value The value of the HTTP header (only used if $headers is a string).
     * @return $this The Redirect instance for chaining.
     */
    public function setHeader($headers, ?string $value = null): Redirect
    {
        if (is_array($headers)) {
            foreach ($headers as $name => $headerValue) {
                if (is_string($name)) {
                    $this->headers[$name] = $headerValue;
                }
            }
        } elseif (is_string($headers) && $value !== null) {
            $this->headers[$headers] = $value;
        }

        return $this;
    }

    /**
     * Generate a redirect to a named route with optional parameters and headers.
     *
     * @param string $name The name of the route to redirect to.
     * @param array $params Optional parameters for the route.
     * @param int $status The HTTP status code for the redirection (default is 302 Found).
     * @param array $headers Additional HTTP headers to include in the redirection response.
     * @return $this The Redirect instance for chaining.
     */
    public function route(string $name, array $params = [], $status = 302, $headers = []): Redirect
    {
        $this->to(route($name, $params, false), $status, $headers);
        return $this;
    }

    /**
     * Attach data to the session flash data for the next request.
     *
     * @param mixed $key The key or array of key-value pairs to attach to the session flash data.
     * @param mixed $value The value to attach if a key is provided.
     * @return $this The Redirect instance for chaining.
     */
    public function with($key, $value = null): Redirect
    {
        if (is_array($key)) {
            $this->with = array_merge($this->with, $key);
        } elseif (is_string($key)) {
            $this->with[$key] = $value;
        }

        return $this;
    }

    /**
     * Attach error data to the session for the next request.
     *
     * @param mixed $key The key or array of key-value pairs to attach as error data.
     * @param mixed $value The value to attach if a key is provided.
     * @return $this The Redirect instance for chaining.
     */
    public function withError($key, $value = null): Redirect
    {
        if (is_array($key)) {
            $this->withError = array_merge($this->withError, $key);
        } elseif (is_string($key)) {
            $this->withError[$key] = $value;
        }

        return $this;
    }

    /**
     * Redirect back to the previous page, optionally specifying a fallback URL.
     *
     * @param string $to The fallback URL if no previous page is available.
     * @param int $status The HTTP status code for the redirection (default is 302 Found).
     * @param array $headers Additional HTTP headers to include in the redirection response.
     * @param bool|null $secure Whether to use a secure (https) URL for the redirection.
     * @return $this The Redirect instance for chaining.
     */
    public function back(string $to = '/', int $status = 302, array $headers = [], ?bool $secure = null): Redirect
    {
        $URL = $_SERVER['HTTP_REFERER'] ?? $to;
        $parsedURL = parse_url($URL);
        $previousPath = $parsedURL['path'] ?? '';
        $previousQuery = $parsedURL['query'] ?? '';
        $previousFragment = $parsedURL['fragment'] ?? '';
        $previousUrl = $previousPath;
        if ($previousQuery) {
            $previousUrl .= '?' . $previousQuery;
        }
        if ($previousFragment) {
            $previousUrl .= '#' . $previousFragment;
        }
        $this->to($previousUrl, $status, $headers, $secure);
        return $this;
    }

    /**
     * Set the redirection target as an external URL.
     *
     * @return $this The Redirect instance for chaining.
     */
    public function isExternal(): Redirect
    {
        $this->isExternal = true;
        return $this;
    }

    /**
     * Set a timeout for the redirection (automatic refresh) in seconds.
     *
     * @param int $seconds The number of seconds to wait before redirecting.
     * @return $this The Redirect instance for chaining.
     */
    public function timeout(int $seconds, $message = null): Redirect
    {
        $this->timeout = $seconds;
        $this->timeoutMessage = $message;
        return $this;
    }

    /**
     * Set a custom HTML content for the timeout page.
     *
     * @param string $html The HTML content for the timeout page.
     * @return $this The Redirect instance for chaining.
     */
    public function timeoutPage(string $html): Redirect
    {
        $this->timeoutPage = $html;
        return $this;
    }

    /**
     * Execute the redirection by sending appropriate headers and content.
     *
     * @return void
     */
    public function exec(): void
    {
        if ($this->isExternal) {
            $url = $this->to;
        } else {
            $url = $this->secure ? $this->createSecureUrl($this->to) : $this->createNonSecureUrl($this->to);
        }
        foreach ($this->with as $key => $value) {
            flash($key, $value);
        }
        foreach ($this->withError as $key => $value) {
            error($key, $value);
        }
        if ($this->timeout) {
            echo "<meta http-equiv=\"refresh\" content=\"{$this->timeout};url={$url}\">";
            if ($this->timeoutPage) {
                echo $this->timeoutPage;
            } elseif ($this->timeoutMessage) {
                echo "<b style='font-family:monospace;font-size:22px;'>{$this->timeoutMessage}</b>";
            }
        } else {
            http_response_code($this->status);
            foreach ($this->headers as $name => $values) {
                header("{$name}: {$values}");
            }
            if (in_array($this->status, [301, 302, 303, 307, 308])) {
                header("Location: {$url}");
            }
            if (in_array($this->status, [303, 307, 308])) {
                header("Refresh: 0;url={$url}");
            }
        }
        terminate();
    }


    /**
     * Create a secure URL based on the provided URL fragment.
     *
     * @param string $to The URL fragment to create a secure (https) URL from.
     * @return string The secure (https) URL.
     */
    protected function createSecureUrl(string $to): string
    {
        $url = $this->getBaseUrl(true) . '/' . ltrim($to, '/');
        return $url;
    }

    /**
     * Create a non-secure URL based on the provided URL fragment.
     *
     * @param string $to The URL fragment to create a non-secure (http) URL from.
     * @return string The non-secure (http) URL.
     */
    protected function createNonSecureUrl(string $to): string
    {
        $url = $this->getBaseUrl(false) . '/' . ltrim($to, '/');
        return $url;
    }

    /**
     * Get the base URL with the specified protocol (secure or non-secure).
     *
     * @param bool $secure Whether to use a secure (https) protocol for the base URL.
     * @return string The base URL with the specified protocol.
     */
    protected function getBaseUrl(bool $secure): string
    {
        $protocol = $secure ? 'https://' : 'http://';
        $basePath = $protocol . $_SERVER['HTTP_HOST'];
        return $basePath;
    }
}
