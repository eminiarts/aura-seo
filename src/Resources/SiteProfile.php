<?php

namespace Aura\Seo\Resources;

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Image;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Resource;
use Aura\Seo\Data\SiteProfileData;

class SiteProfile extends Resource
{
    public static $globalSearch = false;

    public static ?string $slug = 'seo-site-profile';

    protected static ?string $group = 'SEO';

    protected static ?int $sort = 70;

    protected static bool $title = true;

    public static string $type = 'AuraSeoSiteProfile';

    public static function getFields(): array
    {
        return [
            self::field('Site', 'site_panel', Panel::class, ['style' => ['width' => '50']]),
            self::field('Name', 'title', Text::class, ['on_index' => true, 'validation' => 'required|max:255']),
            self::field('Hostname', 'hostname', Text::class, [
                'instructions' => 'Lowercase hostname without scheme, path, or port.',
                'on_index' => true,
                'validation' => ['required', 'max:253', 'regex:/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/'],
            ]),
            self::field('Canonical base URL', 'canonical_base_url', Text::class, [
                'instructions' => 'Absolute HTTP(S) origin for canonical and sitemap URLs.',
                'validation' => 'required|url:http,https|max:2048',
            ]),
            self::field('Enabled', 'enabled', Boolean::class, ['default' => false, 'on_index' => true]),
            self::field('Defaults', 'defaults_panel', Panel::class, ['style' => ['width' => '50']]),
            self::field('Title template', 'title_template', Text::class, [
                'default' => '%s | %site%',
                'instructions' => 'Use %s for the Resource title and %site% for this profile name.',
                'validation' => 'required|max:255',
            ]),
            self::field('Default description', 'default_description', Textarea::class, ['validation' => 'nullable|max:160']),
            self::field('Default social image', 'default_social_image', Image::class, ['max_files' => 1]),
            self::field('Locale', 'locale', Text::class, ['default' => 'en', 'validation' => 'required|max:16']),
            self::field('Robots', 'robots_panel', Panel::class, ['style' => ['width' => '100']]),
            self::field('Allow indexing by default', 'robots_index', Boolean::class, ['default' => false]),
            self::field('Allow following links by default', 'robots_follow', Boolean::class, ['default' => false]),
            self::field('Additional robots.txt rules', 'robots_rules', Textarea::class, [
                'instructions' => 'One Allow, Disallow, Crawl-delay, or comment line per row.',
                'validation' => 'nullable|max:10000',
            ]),
        ];
    }

    public function toSeoData(): SiteProfileData
    {
        return new SiteProfileData(
            baseUrl: (string) $this->seoField('canonical_base_url'),
            enabled: (bool) $this->seoField('enabled'),
            follow: (bool) $this->seoField('robots_follow'),
            hostname: strtolower(rtrim((string) $this->seoField('hostname'), '.')),
            id: $this->getKey() === null ? null : (int) $this->getKey(),
            index: (bool) $this->seoField('robots_index'),
            locale: (string) ($this->seoField('locale') ?: 'en'),
            defaultDescription: $this->nonEmptyString($this->seoField('default_description')),
            defaultSocialImage: $this->resolveDefaultImage(),
            name: $this->nonEmptyString($this->getAttribute('title')),
            robotsRules: $this->nonEmptyString($this->seoField('robots_rules')),
            teamId: $this->getAttribute('team_id') === null ? null : (int) $this->getAttribute('team_id'),
            titleTemplate: (string) ($this->seoField('title_template') ?: '%s'),
        );
    }

    /** @param array<string, mixed> $options */
    private static function field(string $name, string $slug, string $type, array $options = []): array
    {
        return array_merge([
            'conditional_logic' => [],
            'name' => $name,
            'on_forms' => true,
            'on_index' => false,
            'on_view' => true,
            'slug' => $slug,
            'type' => $type,
            'validation' => '',
        ], $options);
    }

    private function nonEmptyString(mixed $value): ?string
    {
        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveDefaultImage(): ?string
    {
        $value = $this->seoField('default_social_image');
        $resolver = config('aura-seo.image_url_resolver');

        if (is_callable($resolver)) {
            return $this->nonEmptyString(app()->call($resolver, ['value' => $value, 'resource' => $this]));
        }

        if (is_array($value) && isset($value['url'])) {
            return $this->nonEmptyString($value['url']);
        }

        return is_string($value) && preg_match('#^https?://#i', $value) ? $value : null;
    }

    private function seoField(string $slug): mixed
    {
        return data_get($this->getFieldsAttribute(), $slug);
    }
}
