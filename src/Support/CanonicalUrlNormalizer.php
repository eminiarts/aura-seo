<?php

namespace Aura\Seo\Support;

use InvalidArgumentException;

final class CanonicalUrlNormalizer
{
    public function normalize(string $url, string $baseUrl, bool $allowExternal = false): string
    {
        $url = trim($url);
        $baseUrl = trim($baseUrl);

        if ($url === '' || $baseUrl === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            throw new InvalidArgumentException('Canonical URL and base URL must be non-empty and contain no control characters.');
        }

        $base = $this->parts($baseUrl);

        if (! isset($base['scheme'], $base['host']) || ! in_array(strtolower($base['scheme']), ['http', 'https'], true)) {
            throw new InvalidArgumentException('The canonical base URL must be an absolute HTTP(S) URL.');
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) && ! preg_match('#^https?://#i', $url)) {
            throw new InvalidArgumentException('Canonical URLs must use HTTP or HTTPS.');
        }

        if (str_starts_with($url, '//')) {
            $url = strtolower($base['scheme']).':'.$url;
        } elseif (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = rtrim($this->origin($base), '/').'/'.ltrim($url, '/');
        }

        $parts = $this->parts($url);

        if (! isset($parts['scheme'], $parts['host']) || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Canonical URLs must use HTTP or HTTPS.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Canonical URLs cannot contain credentials.');
        }

        if (! $allowExternal && $this->origin($parts) !== $this->origin($base)) {
            throw new InvalidArgumentException('Canonical URL host must match the active SEO settings profile.');
        }

        $path = $this->normalizePath($parts['path'] ?? '/');

        if (! config('aura-seo.canonical.trailing_slash', false) && $path !== '/') {
            $path = rtrim($path, '/');
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$this->normalizeQuery($parts['query']) : '';

        return $this->origin($parts).$path.$query;
    }

    /** @return array<string, int|string> */
    private function parts(string $url): array
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw new InvalidArgumentException('Invalid canonical URL.');
        }

        return $parts;
    }

    /** @param array<string, int|string> $parts */
    private function origin(array $parts): string
    {
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $port = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443) ? null : $port;

        return $scheme.'://'.$host.($port ? ':'.$port : '');
    }

    private function normalizePath(string $path): string
    {
        $segments = [];

        foreach (explode('/', preg_replace('#/+#', '/', $path) ?: '/') as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);

                continue;
            }
            $segments[] = rawurlencode(rawurldecode($segment));
        }

        return '/'.implode('/', $segments);
    }

    private function normalizeQuery(string $query): string
    {
        parse_str($query, $values);
        ksort($values);

        return http_build_query($values, '', '&', PHP_QUERY_RFC3986);
    }
}
