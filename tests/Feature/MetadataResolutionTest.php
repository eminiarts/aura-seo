<?php

use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Services\MetadataRenderer;
use Aura\Seo\Services\MetadataResolver;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;
use Aura\Seo\Tests\Fixtures\CustomPage;

function seoTestProfile(array $overrides = []): SiteProfileData
{
    return new SiteProfileData(...array_merge([
        'baseUrl' => 'https://example.test',
        'enabled' => true,
        'follow' => true,
        'hostname' => 'example.test',
        'index' => true,
        'locale' => 'de-CH',
        'defaultDescription' => 'Profile description',
        'defaultSocialImage' => 'https://example.test/default.jpg',
        'name' => 'Example',
        'titleTemplate' => '%s | %site%',
    ], $overrides));
}

function seoArticleDefinition(bool $visible = true): SeoResourceDefinition
{
    return SeoResourceDefinition::make('articles', Article::class)
        ->title('title')
        ->description('summary')
        ->image(fn (): string => 'https://example.test/mapped.jpg')
        ->url(fn (Article $article): string => '/articles/'.$article->getKey())
        ->alternates(fn (Article $article): array => [
            'de' => '/de/artikel/'.$article->getKey(),
            'external' => 'https://other.test/article',
        ])
        ->publicIndex(fn () => Article::query(), fn () => $visible);
}

test('resolution order is record override then Resource mapping then profile then application', function () {
    config()->set('aura-seo.fallbacks.description', 'Application description');
    $article = Article::query()->create([
        'fields' => [
            'seo_meta_description' => 'Record description',
            'seo_meta_title' => 'Record title',
            'seo_og_title' => 'Record social title',
        ],
        'summary' => 'Mapped description',
        'title' => 'Mapped title',
    ])->fresh();
    $registry = app(SeoRegistry::class)->register(seoArticleDefinition());

    $metadata = app(MetadataResolver::class)->resolve($article, seoTestProfile(), $registry->get('articles'));

    expect($metadata->title)->toBe('Record title | Example')
        ->and($metadata->description)->toBe('Record description')
        ->and($metadata->openGraph['og:title'])->toBe('Record social title')
        ->and($metadata->openGraph['og:image'])->toBe('https://example.test/mapped.jpg')
        ->and($metadata->canonical)->toBe('https://example.test/articles/'.$article->getKey())
        ->and($metadata->alternates)->toBe(['de' => 'https://example.test/de/artikel/'.$article->getKey()]);
});

test('empty values do not emit empty tags and fall through deterministically', function () {
    $article = Article::query()->create([
        'fields' => ['seo_meta_description' => ' ', 'seo_meta_title' => ''],
        'summary' => '',
        'title' => 'Mapped title',
    ])->fresh();
    $definition = seoArticleDefinition();

    $metadata = app(MetadataResolver::class)->resolve($article, seoTestProfile(), $definition);
    $html = app(MetadataRenderer::class)->render($metadata)->toHtml();

    expect($metadata->title)->toBe('Mapped title | Example')
        ->and($metadata->description)->toBe('Profile description')
        ->and($html)->not->toContain('content=""');
});

test('record robots false values are preserved and visibility is a fail-closed gate', function () {
    $article = Article::query()->create([
        'fields' => ['seo_follow' => false, 'seo_index' => false],
        'title' => 'Robots',
    ])->fresh();

    $metadata = app(MetadataResolver::class)->resolve($article, seoTestProfile(), seoArticleDefinition());
    $private = app(MetadataResolver::class)->resolve($article, seoTestProfile(), seoArticleDefinition(false));

    expect($metadata->robots)->toBe(['noindex', 'nofollow'])
        ->and($private->canonical)->toBeNull()
        ->and($private->robots)->toBe(['noindex', 'nofollow']);
});

test('metadata output escapes stored values', function () {
    $article = Article::query()->create([
        'fields' => [
            'seo_meta_description' => '"><script>alert(1)</script>',
            'seo_meta_title' => '<img src=x onerror=alert(1)>',
        ],
        'title' => 'Unsafe',
    ])->fresh();

    $metadata = app(MetadataResolver::class)->resolve($article, seoTestProfile(), seoArticleDefinition());
    $html = app(MetadataRenderer::class)->render($metadata)->toHtml();

    expect($html)->not->toContain('<script>')
        ->not->toContain('<img src=x')
        ->toContain('&lt;script&gt;')
        ->toContain('&lt;img src=x onerror=alert(1)&gt;');
});

test('the same deterministic resolver supports Aura custom-table Resources', function () {
    $page = CustomPage::query()->create([
        'seo_meta_title' => 'Custom override',
        'summary' => 'Custom summary',
        'title' => 'Custom mapped',
    ]);
    $definition = SeoResourceDefinition::make('pages', CustomPage::class)
        ->title('title')
        ->description('summary')
        ->url(fn (CustomPage $record): string => '/pages/'.$record->getKey())
        ->publicIndex(fn () => CustomPage::query(), fn (): bool => true);

    $metadata = app(MetadataResolver::class)->resolve($page, seoTestProfile(), $definition);

    expect($metadata->title)->toBe('Custom override | Example')
        ->and($metadata->description)->toBe('Custom summary')
        ->and($metadata->canonical)->toBe('https://example.test/pages/'.$page->getKey());
});
