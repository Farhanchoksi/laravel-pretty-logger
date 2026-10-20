<?php

namespace Farhanchoksi\PrettyLogger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pretty, colored, boxed HTTP request/response logger (pretty_dio_logger style).
 *
 * Reads all its behaviour from config('pretty-logger'). Writes to the terminal
 * channel (php://stderr) and/or a file channel, both registered by the package
 * service provider. Sensitive keys/headers are masked automatically.
 */
class PrettyLoggerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldLog($request)) {
            return $next($request);
        }

        $start = microtime(true);

        $this->logRequest($request);

        $response = $next($request);

        $this->logResponse($request, $response, $start);

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        $enabled = config('pretty-logger.enabled');

        // null → follow APP_DEBUG; true/false → explicit override.
        if ($enabled === null) {
            $enabled = (bool) config('app.debug');
        }

        if (! $enabled) {
            return false;
        }

        $path = ltrim($request->path(), '/');

        foreach ((array) config('pretty-logger.skip_prefixes', []) as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }

    private function logRequest(Request $request): void
    {
        $lines = [];

        $query = $request->query();
        if ($query !== []) {
            $lines['query'] = $this->json($this->mask($query));
        }

        if (! in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $body = $request->all();
            if ($body !== []) {
                $lines['body'] = $this->json($this->mask($body));
            }
        }

        $lines['headers'] = $this->formatHeaders($request->headers->all());

        $title = sprintf('REQUEST  %s  %s', $request->getMethod(), $this->safeUrl($request));

        $this->write($title, $lines, "\033[36m"); // cyan
    }

    private function logResponse(Request $request, Response $response, float $start): void
    {
        $ms = (int) round((microtime(true) - $start) * 1000);
        $status = $response->getStatusCode();

        $lines = [];
        $lines['duration'] = $ms.'ms';
        $lines['headers'] = $this->formatHeaders($response->headers->all());
        $lines['body'] = $this->renderBody($response);

        $title = sprintf('RESPONSE  %d  %s  %s', $status, $request->getMethod(), $this->safeUrl($request));

        $color = match (true) {
            $status >= 400 => "\033[31m",
            $status >= 300 => "\033[33m",
            default => "\033[32m",
        };

        $this->write($title, $lines, $color);
    }

    private function renderBody(Response $response): string
    {
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        $content = $response->getContent();
        $size = $content === false ? 0 : strlen($content);

        if ($size === 0) {
            return '<empty>';
        }

        if (preg_match('#image/|octet-stream|font/|pdf|zip|video/|audio/#', $contentType)) {
            return sprintf('<binary, %s bytes>', number_format($size));
        }

        if (str_contains($contentType, 'html') && ! config('pretty-logger.html_body', false)) {
            return sprintf('<html document, %s bytes — body hidden>', number_format($size));
        }

        $max = (int) config('pretty-logger.max_body', 600);
        $content = trim((string) $content);

        if ($size > $max) {
            return substr($content, 0, $max).sprintf('… (truncated, total %s bytes)', number_format($size));
        }

        return $content;
    }

    /**
     * Build and print a boxed, colored log entry to the configured channels.
     *
     * @param  array<string,string>  $lines
     */
    private function write(string $title, array $lines, string $color): void
    {
        $width = (int) config('pretty-logger.width', 78);
        $reset = "\033[0m";
        $bar = str_repeat('─', $width + 3);

        $out = $color.'┌'.$bar."\n";
        $out .= $this->row($title);
        $out .= $color.'├'.$bar."\n";

        foreach ($lines as $label => $value) {
            foreach ($this->wrap("$label: $value", $width) as $chunk) {
                $out .= $this->row($chunk);
            }
        }

        $out .= $color.'└'.$bar.$reset."\n";

        if (config('pretty-logger.to_terminal', true)) {
            Log::channel('pretty_logger_terminal')->debug($out);
        }

        if (config('pretty-logger.to_file', true)) {
            Log::channel('pretty_logger_file')->debug($out);
        }
    }

    private function row(string $text): string
    {
        return '│ '.$text.' │'."\n";
    }

    private function wrap(string $text, int $width): array
    {
        $result = [];

        foreach (explode("\n", $text) as $paragraph) {
            $result = array_merge($result, str_split($paragraph, $width) ?: ['']);
        }

        return $result;
    }

    /** Full URL with any sensitive query params masked. */
    private function safeUrl(Request $request): string
    {
        $url = $request->fullUrl();

        foreach ((array) config('pretty-logger.mask_keys', []) as $key) {
            $url = preg_replace('/([?&]'.preg_quote($key, '/').'=)[^&]*/i', '$1******', $url) ?? $url;
        }

        return $url;
    }

    private function json(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    private function mask(array $data): array
    {
        $keys = (array) config('pretty-logger.mask_keys', []);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mask($value);
            } elseif (in_array(strtolower((string) $key), $keys, true)) {
                $data[$key] = '******';
            }
        }

        return $data;
    }

    private function formatHeaders(array $headers): string
    {
        $redact = (array) config('pretty-logger.redact_headers', []);
        $maxValue = (int) config('pretty-logger.max_header_value', 80);
        $out = [];

        foreach ($headers as $name => $values) {
            $name = strtolower((string) $name);
            $value = is_array($values) ? implode(', ', array_map('strval', $values)) : (string) $values;

            if (in_array($name, $redact, true)) {
                $value = '<redacted>';
            } elseif (strlen($value) > $maxValue) {
                $value = substr($value, 0, $maxValue - 1).'…';
            }

            $out[] = "$name: $value";
        }

        return implode("\n", $out);
    }
}
