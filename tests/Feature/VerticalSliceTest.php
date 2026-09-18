<?php

use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Services\MetadataRenderer;
use Aura\Seo\Services\MetadataResolver;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;

test('one Aura Resource persists SEO fields and renders metadata without entering the sitemap', function () {
    $article = Article::query()->create([
        'fields' => [
            'seo_meta_description' => 'A persisted summary.',
            'seo_meta_title' => 'A persisted title',
        ],
        'summary' => 'Mapped summary',
        'title' => 'Mapped title',
    ])->fresh();

    $definition = SeoResourceDefinition::make('articles', Article::class)
        ->title('title')
        ->description('summary')
        ->url(fn (Article $record, SiteProfileData $site): string => $site->baseUrl.'/articles/'.$record->getKey())
        ->publicIndex(
            fn (SiteProfileData $site) => Article::query(),
            fn (Article $record, SiteProfileData $site): bool => true,
        );

    $registry = app(SeoRegistry::class)->register($definition);
    $profile = new SiteProfileData(
        baseUrl: 'https://example.test',
        enabled: true,
        follow: true,
        hostname: 'example.test',
        index: true,
        name: 'Example',
        titleTemplate: '%s | %site%',
    );

    $metadata = app(MetadataResolver::class)->resolve($article, $profile);
    $html = app(MetadataRenderer::class)->render($metadata)->toHtml();

    expect($article->seo_meta_title)->toBe('A persisted title')
        ->and($article->seo_meta_description)->toBe('A persisted summary.')
        ->and($metadata->title)->toBe('A persisted title')
        ->and($metadata->canonical)->toBe('https://example.test/articles/'.$article->getKey())
        ->and($html)->toContain('<title>A persisted title</title>')
        ->and($html)->toContain('name="description" content="A persisted summary."')
        ->and($registry->sitemapDefinitions())->toBe([]);
});

test('unregistered Resources remain noindex and have no public canonical', function () {
    $article = Article::query()->create(['title' => 'Private']);
    $profile = new SiteProfileData(
        baseUrl: 'https://example.test',
        enabled: true,
        follow: true,
        hostname: 'example.test',
        index: true,
    );

    $metadata = app(MetadataResolver::class)->resolve($article, $profile);

    expect($metadata->canonical)->toBeNull()
        ->and($metadata->robots)->toBe(['noindex', 'nofollow']);
});
