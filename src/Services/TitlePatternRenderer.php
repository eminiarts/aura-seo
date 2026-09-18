<?php

namespace Aura\Seo\Services;

final class TitlePatternRenderer
{
    public function render(string $pattern, ?string $postTitle, string $separator, ?string $siteName): ?string
    {
        $postTitle = $this->string($postTitle);
        $siteName = $this->string($siteName);
        $separator = trim($separator);
        $pattern = trim($pattern);

        if ($postTitle === null && $siteName === null) {
            return null;
        }

        if ($pattern === '') {
            return $postTitle ?? $siteName;
        }

        $title = str_replace(
            ['[Post Title]', '[Separator]', '[Site Name]', '%site%', '%s'],
            [$postTitle ?? '', $separator, $siteName ?? '', $siteName ?? '', $postTitle ?? ''],
            $pattern,
        );
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        if ($separator !== '') {
            $quoted = preg_quote($separator, '/');
            $title = preg_replace("/^(?:\s*{$quoted}\s*)+|(?:\s*{$quoted}\s*)+$/u", '', $title) ?? $title;
            $title = preg_replace("/(?:\s*{$quoted}\s*){2,}/u", ' '.$separator.' ', $title) ?? $title;
        }

        return $this->string($title);
    }

    private function string(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
