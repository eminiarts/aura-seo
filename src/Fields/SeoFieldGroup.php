<?php

namespace Aura\Seo\Fields;

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Image;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Slug;
use Aura\Base\Fields\Tab;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Fields\View;
use Aura\Seo\Services\SeoDefaults;
use Illuminate\Support\Facades\Gate;

final class SeoFieldGroup
{
    /** @return array<int, array<string, mixed>> */
    public static function make(string $prefix = 'seo'): array
    {
        $prefix = trim($prefix, '_- ');
        $slug = static fn (string $value): string => $prefix === '' ? $value : $prefix.'_'.$value;
        $hidden = static fn (): bool => false;

        return [
            self::field('SEO', $slug('tab'), Tab::class, ['global' => true]),
            self::field('SEO workspace', $slug('editor'), View::class, [
                'on_view' => false,
                'seo_prefix' => $prefix,
                'view' => 'aura-seo::fields.editor',
            ]),
            self::field('AI suggestion', $slug('ai_prefill'), View::class, [
                'conditional_logic' => $hidden,
                'on_view' => false,
                'seo_prefix' => $prefix,
                'view' => 'aura-seo::fields.ai-prefill',
            ]),
            self::field('SEO slug', $slug('slug'), Slug::class, [
                'based_on' => 'title',
                'conditional_logic' => $hidden,
                'custom' => true,
                'instructions' => 'Optional public slug override.',
                'validation' => 'nullable|max:255',
            ]),
            self::field('Meta title', $slug('meta_title'), Text::class, [
                'conditional_logic' => $hidden,
                'instructions' => 'Recommended maximum: 60 characters.',
                'set' => static fn ($resource, array $field, mixed $value): mixed => app(SeoDefaults::class)->metaTitle(
                    $resource,
                    self::authorizedValue($resource, $field, $value),
                ),
                'validation' => 'nullable|max:60',
            ]),
            self::field('Meta description', $slug('meta_description'), Textarea::class, [
                'conditional_logic' => $hidden,
                'instructions' => 'Recommended maximum: 160 characters.',
                'validation' => 'nullable|max:160',
            ]),
            self::field('Canonical URL', $slug('canonical_url'), Text::class, [
                'conditional_logic' => $hidden,
                'instructions' => 'Absolute or site-relative canonical URL.',
                'validation' => 'nullable|max:2048',
            ]),
            self::field('Open Graph title', $slug('og_title'), Text::class, [
                'conditional_logic' => $hidden,
                'validation' => 'nullable|max:95',
            ]),
            self::field('Open Graph description', $slug('og_description'), Textarea::class, [
                'conditional_logic' => $hidden,
                'validation' => 'nullable|max:200',
            ]),
            self::field('Open Graph image', $slug('og_image'), Image::class, [
                'conditional_logic' => $hidden,
                'max_files' => 1,
                'style' => ['width' => '50'],
            ]),
            self::field('Twitter title', $slug('twitter_title'), Text::class, [
                'conditional_logic' => $hidden,
                'validation' => 'nullable|max:70',
            ]),
            self::field('Twitter description', $slug('twitter_description'), Textarea::class, [
                'conditional_logic' => $hidden,
                'validation' => 'nullable|max:200',
            ]),
            self::field('Twitter image', $slug('twitter_image'), Image::class, [
                'conditional_logic' => $hidden,
                'max_files' => 1,
                'style' => ['width' => '50'],
            ]),
            self::field('Allow indexing', $slug('index'), Boolean::class, [
                'conditional_logic' => $hidden,
                'default' => true,
                'style' => ['width' => '50'],
            ]),
            self::field('Allow following links', $slug('follow'), Boolean::class, [
                'conditional_logic' => $hidden,
                'default' => true,
                'style' => ['width' => '50'],
            ]),
            self::field('Twitter card', $slug('twitter_card'), Select::class, [
                'conditional_logic' => $hidden,
                'default' => 'summary_large_image',
                'options' => [
                    'summary' => 'Summary',
                    'summary_large_image' => 'Summary with large image',
                ],
            ]),
            self::field('Search and social preview', $slug('preview'), View::class, [
                'conditional_logic' => $hidden,
                'instructions' => 'Search engines and social platforms may display content differently.',
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
            'disabled' => static fn (): bool => Gate::denies('aura-seo.manage'),
            'name' => $name,
            'on_forms' => true,
            'on_index' => false,
            'on_view' => true,
            'slug' => $slug,
            'type' => $type,
            'validation' => '',
            'set' => static fn ($resource, array $field, mixed $value): mixed => self::authorizedValue(
                $resource,
                $field,
                $value,
            ),
        ], $options);
    }

    /** @param array<string, mixed> $field */
    private static function authorizedValue($resource, array $field, mixed $value): mixed
    {
        if (auth()->guest() || Gate::allows('aura-seo.manage')) {
            return $value;
        }

        $original = $resource->getRawOriginal();

        if (array_key_exists($field['slug'], $original)) {
            return $original[$field['slug']];
        }

        return $resource->usesMeta() ? $resource->getMeta($field['slug']) : null;
    }
}
