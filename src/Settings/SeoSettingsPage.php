<?php

namespace Aura\Seo\Settings;

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Image;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Fields\View;
use Aura\Base\Settings\SettingsPage;

final class SeoSettingsPage
{
    public static function make(): SettingsPage
    {
        return new SettingsPage(
            slug: 'seo',
            title: 'SEO',
            fields: self::fields(),
            icon: 'search',
            description: 'Configure site-wide metadata defaults, social images, robots behavior, and diagnostics.',
            order: 25,
            defaults: self::defaults(),
        );
    }

    /** @return array<string, mixed> */
    private static function defaults(): array
    {
        return [
            'seo-enabled' => config('aura-seo.settings.enabled', false),
            'seo-site-name' => config('aura-seo.settings.site_name', config('app.name', 'Aura')),
            'seo-canonical-base-url' => config('aura-seo.settings.canonical_base_url', config('app.url')),
            'seo-separator' => config('aura-seo.settings.separator', '|'),
            'seo-title-pattern' => config('aura-seo.settings.title_pattern', '[Post Title] [Separator] [Site Name]'),
            'seo-default-description' => config('aura-seo.settings.default_description'),
            'seo-default-open-graph-image' => config('aura-seo.settings.default_open_graph_image'),
            'seo-default-twitter-image' => config('aura-seo.settings.default_twitter_image'),
            'seo-locale' => config('aura-seo.settings.locale', 'en'),
            'seo-robots-index' => config('aura-seo.settings.robots_index', false),
            'seo-robots-follow' => config('aura-seo.settings.robots_follow', false),
            'seo-robots-rules' => config('aura-seo.settings.robots_rules'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function fields(): array
    {
        return [
            self::field('Site', 'seo-site-panel', Panel::class, ['style' => ['width' => '50']]),
            self::field('Enable SEO output', 'seo-enabled', Boolean::class),
            self::field('Site name', 'seo-site-name', Text::class, [
                'validation' => 'required|string|max:255',
                'style' => ['width' => '50'],
            ]),
            self::field('Canonical base URL', 'seo-canonical-base-url', Text::class, [
                'instructions' => 'Absolute HTTP(S) origin used for canonical and sitemap URLs.',
                'validation' => 'required|url:http,https|max:2048',
                'style' => ['width' => '50'],
            ]),
            self::field('Title separator', 'seo-separator', Text::class, [
                'validation' => 'required|string|max:10',
                'style' => ['width' => '25'],
            ]),
            self::field('Locale', 'seo-locale', Text::class, [
                'validation' => 'required|string|max:16',
                'style' => ['width' => '25'],
            ]),
            self::field('Defaults', 'seo-defaults-panel', Panel::class, ['style' => ['width' => '50']]),
            self::field('SEO title pattern', 'seo-title-pattern', Text::class, [
                'instructions' => 'Available tokens: [Post Title], [Separator], and [Site Name].',
                'validation' => 'required|string|max:255',
            ]),
            self::field('Default description', 'seo-default-description', Textarea::class, [
                'validation' => 'nullable|string|max:160',
            ]),
            self::field('Default Open Graph image', 'seo-default-open-graph-image', Image::class, [
                'max_files' => 1,
                'style' => ['width' => '50'],
            ]),
            self::field('Default Twitter image', 'seo-default-twitter-image', Image::class, [
                'max_files' => 1,
                'style' => ['width' => '50'],
            ]),
            self::field('Robots', 'seo-robots-panel', Panel::class, ['style' => ['width' => '100']]),
            self::field('Allow indexing by default', 'seo-robots-index', Boolean::class, [
                'style' => ['width' => '50'],
            ]),
            self::field('Allow following links by default', 'seo-robots-follow', Boolean::class, [
                'style' => ['width' => '50'],
            ]),
            self::field('Additional robots.txt rules', 'seo-robots-rules', Textarea::class, [
                'instructions' => 'One Allow, Disallow, Crawl-delay, or comment line per row.',
                'validation' => 'nullable|string|max:10000',
            ]),
            self::field('Diagnostics', 'seo-diagnostics-panel', Panel::class, ['style' => ['width' => '100']]),
            self::field('SEO diagnostics', 'seo-diagnostics', View::class, [
                'view' => 'aura-seo::settings.diagnostics',
            ]),
        ];
    }

    /** @param array<string, mixed> $options */
    private static function field(string $name, string $slug, string $type, array $options = []): array
    {
        return array_merge([
            'name' => $name,
            'type' => $type,
            'slug' => $slug,
            'validation' => '',
        ], $options);
    }
}
