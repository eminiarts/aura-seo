<?php

namespace Aura\Seo\Settings;

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Image;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Tab;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Fields\View;
use Aura\Base\Settings\SettingsPage;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Services\SeoResourceSettings;
use Illuminate\Support\Str;

final class SeoSettingsPage
{
    public static function make(?SeoRegistry $registry = null): SettingsPage
    {
        $registry ??= app(SeoRegistry::class);

        return new SettingsPage(
            slug: 'seo',
            title: 'SEO',
            fields: self::fields($registry),
            icon: 'search',
            description: 'Manage search metadata, social sharing, SEO checks, the sitemap, and robots.txt.',
            order: 25,
            defaults: self::defaults($registry),
            viewAbility: 'aura-seo.view',
            updateAbility: 'aura-seo.manage',
        );
    }

    /** @return array<string, mixed> */
    private static function defaults(SeoRegistry $registry): array
    {
        $defaults = [
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
            'seo-sitemap-enabled' => config('aura-seo.routes.sitemap', true) && config('aura-seo.settings.sitemap_enabled', true),
            'seo-robots-route-enabled' => config('aura-seo.routes.robots', true) && config('aura-seo.settings.robots_route_enabled', true),
        ];

        foreach ($registry->all() as $definition) {
            $defaults[SeoResourceSettings::slug($definition, 'enabled')] = true;
            $defaults[SeoResourceSettings::slug($definition, 'title-pattern')] = '';
            $defaults[SeoResourceSettings::slug($definition, 'default-description')] = '';
            $defaults[SeoResourceSettings::slug($definition, 'default-social-image')] = null;
            $defaults[SeoResourceSettings::slug($definition, 'index')] = 'inherit';
            $defaults[SeoResourceSettings::slug($definition, 'follow')] = 'inherit';

            if ($definition->includesSitemap()) {
                $defaults[SeoResourceSettings::slug($definition, 'sitemap')] = true;
            }
        }

        return $defaults;
    }

    /** @return list<array<string, mixed>> */
    private static function fields(SeoRegistry $registry): array
    {
        $fields = [
            self::field('Overview', 'seo-overview-tab', Tab::class),
            self::field('SEO overview', 'seo-overview', View::class, [
                'view' => 'aura-seo::settings.overview',
            ]),
            self::field('Site defaults', 'seo-site-defaults-tab', Tab::class),
            self::input('Enable SEO output', 'seo-enabled', Boolean::class),
            self::input('Site name', 'seo-site-name', Text::class, [
                'live' => true,
                'validation' => 'required|string|max:255',
                'style' => ['width' => '50'],
            ]),
            self::input('Site URL', 'seo-canonical-base-url', Text::class, [
                'instructions' => 'Enter the full site address, including https://.',
                'live' => true,
                'validation' => 'required|url:http,https|max:2048',
            ]),
            self::input('Title separator', 'seo-separator', Text::class, [
                'live' => true,
                'validation' => 'required|string|max:10',
                'style' => ['width' => '50'],
            ]),
            self::input('Locale', 'seo-locale', Text::class, [
                'validation' => 'required|string|max:16',
                'style' => ['width' => '50'],
            ]),
            self::input('SEO title pattern', 'seo-title-pattern', Text::class, [
                'instructions' => 'Available tokens: [Post Title], [Separator], and [Site Name].',
                'live' => true,
                'validation' => 'required|string|max:255',
            ]),
            self::input('Default description', 'seo-default-description', Textarea::class, [
                'live' => true,
                'validation' => 'nullable|string|max:160',
            ]),
            self::input('Default Open Graph image', 'seo-default-open-graph-image', Image::class, [
                'max_files' => 1,
                'style' => ['width' => '50'],
            ]),
            self::input('Default Twitter image', 'seo-default-twitter-image', Image::class, [
                'max_files' => 1,
                'style' => ['width' => '50'],
            ]),
            self::input('Allow indexing by default', 'seo-robots-index', Boolean::class, [
                'style' => ['width' => '50'],
            ]),
            self::input('Allow following links by default', 'seo-robots-follow', Boolean::class, [
                'style' => ['width' => '50'],
            ]),
            self::field('Site defaults workspace', 'seo-site-defaults-workspace', View::class, [
                'view' => 'aura-seo::settings.site-defaults',
            ]),
            self::field('Content types', 'seo-content-types-tab', Tab::class),
        ];

        if ($registry->all() === []) {
            $fields[] = self::field('Content types workspace', 'seo-content-types-workspace', View::class, [
                'view' => 'aura-seo::settings.content-types',
            ]);
        }

        foreach ($registry->all() as $definition) {
            $label = Str::headline($definition->key);
            $fields[] = self::input("Enable SEO for {$label}", SeoResourceSettings::slug($definition, 'enabled'), Boolean::class, [
                'style' => ['width' => '100'],
            ]);

            if ($definition->includesSitemap()) {
                $fields[] = self::input("Include {$label} in the sitemap", SeoResourceSettings::slug($definition, 'sitemap'), Boolean::class, [
                    'style' => ['width' => '100'],
                ]);
            }

            $fields[] = self::input('Title pattern', SeoResourceSettings::slug($definition, 'title-pattern'), Text::class, [
                'instructions' => 'Leave blank to use the site title pattern.',
                'validation' => 'nullable|string|max:255',
            ]);
            $fields[] = self::input('Default description', SeoResourceSettings::slug($definition, 'default-description'), Textarea::class, [
                'instructions' => 'Used when a record has no description.',
                'validation' => 'nullable|string|max:160',
            ]);
            $fields[] = self::input('Default social image', SeoResourceSettings::slug($definition, 'default-social-image'), Image::class, [
                'max_files' => 1,
            ]);
            $fields[] = self::input('Search indexing', SeoResourceSettings::slug($definition, 'index'), Select::class, [
                'options' => [
                    'inherit' => 'Use site default',
                    'index' => 'Allow indexing',
                    'noindex' => 'Do not index',
                ],
                'style' => ['width' => '50'],
            ]);
            $fields[] = self::input('Follow links', SeoResourceSettings::slug($definition, 'follow'), Select::class, [
                'options' => [
                    'inherit' => 'Use site default',
                    'follow' => 'Allow following links',
                    'nofollow' => 'Do not follow links',
                ],
                'style' => ['width' => '50'],
            ]);
        }

        if ($registry->all() !== []) {
            $fields[] = self::field('Content types workspace', 'seo-content-types-workspace', View::class, [
                'view' => 'aura-seo::settings.content-types',
            ]);
        }

        return array_merge($fields, [
            self::field('Diagnostics', 'seo-diagnostics-tab', Tab::class),
            self::field('Diagnostics workspace', 'seo-diagnostics', View::class, [
                'view' => 'aura-seo::settings.diagnostics',
            ]),
            self::field('Sitemap & robots', 'seo-sitemap-robots-tab', Tab::class),
            self::input('Enable sitemap.xml', 'seo-sitemap-enabled', Boolean::class, [
                'instructions' => 'Make the sitemap available for this site.',
                'style' => ['width' => '50'],
            ]),
            self::input('Enable robots.txt', 'seo-robots-route-enabled', Boolean::class, [
                'instructions' => 'Use Aura SEO to provide robots.txt for this site.',
                'style' => ['width' => '50'],
            ]),
            self::input('Additional robots.txt rules', 'seo-robots-rules', Textarea::class, [
                'instructions' => 'One Allow, Disallow, Crawl-delay, or comment line per row.',
                'validation' => 'nullable|string|max:10000',
            ]),
            self::field('Sitemap and robots workspace', 'seo-sitemap-robots-workspace', View::class, [
                'view' => 'aura-seo::settings.sitemap-robots',
            ]),
        ]);
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

    /** @param array<string, mixed> $options */
    private static function input(string $name, string $slug, string $type, array $options = []): array
    {
        return self::field($name, $slug, $type, array_merge([
            'conditional_logic' => static fn (): bool => false,
        ], $options));
    }
}
