<?php

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Image;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Slug;
use Aura\Base\Fields\Tab;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Fields\View;
use Aura\Seo\Fields\SeoFieldGroup;

test('the reusable group is composed only from existing Aura field types', function () {
    $fields = SeoFieldGroup::make();
    $types = collect($fields)->pluck('type')->unique()->values()->all();

    expect($types)->each->toBeIn([
        Boolean::class,
        Image::class,
        Panel::class,
        Select::class,
        Slug::class,
        Tab::class,
        Text::class,
        Textarea::class,
        View::class,
    ]);
});

test('the reusable group provides every documented SEO value with unique prefixed slugs', function () {
    $fields = SeoFieldGroup::make('page_seo');
    $slugs = collect($fields)->pluck('slug');

    expect($slugs->duplicates()->all())->toBe([])
        ->and($slugs->all())->toContain(
            'page_seo_slug',
            'page_seo_ai_prefill',
            'page_seo_meta_title',
            'page_seo_meta_description',
            'page_seo_canonical_url',
            'page_seo_index',
            'page_seo_follow',
            'page_seo_og_title',
            'page_seo_og_description',
            'page_seo_og_image',
            'page_seo_twitter_title',
            'page_seo_twitter_description',
            'page_seo_twitter_image',
            'page_seo_twitter_card',
            'page_seo_preview',
        );
});

test('title description canonical and image fields carry editor validation and guidance', function () {
    $fields = collect(SeoFieldGroup::make())->keyBy('slug');

    expect($fields['seo_meta_title']['validation'])->toBe('nullable|max:60')
        ->and($fields['seo_meta_description']['validation'])->toBe('nullable|max:160')
        ->and($fields['seo_canonical_url']['validation'])->toBe('nullable|max:2048')
        ->and($fields['seo_og_image']['max_files'])->toBe(1)
        ->and($fields['seo_twitter_card']['options'])->toHaveKeys(['summary', 'summary_large_image']);
});
