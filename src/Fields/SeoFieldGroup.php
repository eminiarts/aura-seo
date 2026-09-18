<?php

namespace Aura\Seo\Fields;

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Image;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Slug;
use Aura\Base\Fields\Tab;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Fields\View;
use Aura\Seo\Services\SeoDefaults;

final class SeoFieldGroup
{
    /** @return array<int, array<string, mixed>> */
    public static function make(string $prefix = 'seo'): array
    {
        $prefix = trim($prefix, '_- ');
        $slug = static fn (string $value): string => $prefix === '' ? $value : $prefix.'_'.$value;

        return [
            self::field('SEO', $slug('tab'), Tab::class, ['global' => true]),
            self::field('Search metadata', $slug('search_panel'), Panel::class, ['style' => ['width' => '50']]),
            self::field('Pre-fill metadata with AI', $slug('ai_prefill'), View::class, [
                'global' => true,
                'on_view' => false,
                'seo_prefix' => $prefix,
                'view' => 'aura-seo::fields.ai-prefill',
            ]),
            self::field('SEO slug', $slug('slug'), Slug::class, [
                'based_on' => 'title',
                'custom' => true,
                'instructions' => 'Optional public slug override.',
                'validation' => 'nullable|max:255',
            ]),
            self::field('Meta title', $slug('meta_title'), Text::class, [
                'instructions' => 'Recommended maximum: 60 characters.',
                'set' => static fn ($resource, array $field, mixed $value): mixed => app(SeoDefaults::class)->metaTitle($resource, $value),
                'validation' => 'nullable|max:60',
            ]),
            self::field('Meta description', $slug('meta_description'), Textarea::class, [
                'instructions' => 'Recommended maximum: 160 characters.',
                'validation' => 'nullable|max:160',
            ]),
            self::field('Canonical URL', $slug('canonical_url'), Text::class, [
                'instructions' => 'Absolute or site-relative canonical URL.',
                'validation' => 'nullable|max:2048',
            ]),
            self::field('Allow indexing', $slug('index'), Boolean::class, ['default' => true]),
            self::field('Allow following links', $slug('follow'), Boolean::class, ['default' => true]),
            self::field('Social metadata', $slug('social_panel'), Panel::class, ['style' => ['width' => '50']]),
            self::field('Open Graph title', $slug('og_title'), Text::class, ['validation' => 'nullable|max:95']),
            self::field('Open Graph description', $slug('og_description'), Textarea::class, ['validation' => 'nullable|max:200']),
            self::field('Open Graph image', $slug('og_image'), Image::class, ['max_files' => 1]),
            self::field('Twitter title', $slug('twitter_title'), Text::class, ['validation' => 'nullable|max:70']),
            self::field('Twitter description', $slug('twitter_description'), Textarea::class, ['validation' => 'nullable|max:200']),
            self::field('Twitter image', $slug('twitter_image'), Image::class, ['max_files' => 1]),
            self::field('Twitter card', $slug('twitter_card'), Select::class, [
                'default' => 'summary_large_image',
                'options' => [
                    'summary' => 'Summary',
                    'summary_large_image' => 'Summary with large image',
                ],
            ]),
            self::field('Preview', $slug('preview_panel'), Panel::class, ['style' => ['width' => '100']]),
            self::field('Resolved preview', $slug('preview'), View::class, [
                'global' => true,
                'instructions' => 'Preview uses the first enabled SEO settings profile for this team and Resource.',
                'on_view' => false,
                'validation' => '',
                'view' => 'aura-seo::fields.preview',
            ]),
        ];
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
}
