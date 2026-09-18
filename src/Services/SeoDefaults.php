<?php

namespace Aura\Seo\Services;

use Aura\Base\Facades\Aura;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final readonly class SeoDefaults
{
    public function __construct(private TitlePatternRenderer $titles) {}

    public function metaTitle(Model $resource, mixed $value): mixed
    {
        if ($resource->exists || $this->string($value) !== null) {
            return $value;
        }

        $title = $this->titles->render(
            pattern: (string) Aura::setting(
                'seo-title-pattern',
                config('aura-seo.settings.title_pattern', '[Post Title] [Separator] [Site Name]'),
            ),
            postTitle: $this->string($resource->getAttribute('title')),
            separator: (string) Aura::setting('seo-separator', config('aura-seo.settings.separator', '|')),
            siteName: $this->string(Aura::setting('seo-site-name', config('aura-seo.settings.site_name', config('app.name')))),
        );

        return $title === null ? null : Str::limit($title, 60, '');
    }

    private function string(mixed $value): ?string
    {
        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
