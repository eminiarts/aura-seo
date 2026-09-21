<?php

use Aura\Base\Settings\SettingsRegistry;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;

test('SEO is a single registered settings page instead of an Aura Resource', function () {
    expect(app(SettingsRegistry::class)->has('seo'))->toBeTrue();
});

test('Teams-off hostname resolution reads the single settings record', function () {
    createSeoSettings([
        'seo-canonical-base-url' => 'https://example.test',
        'seo-enabled' => true,
        'seo-locale' => 'en-GB',
        'seo-robots-follow' => true,
        'seo-robots-index' => true,
        'seo-separator' => '|',
        'seo-site-name' => 'Example',
        'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
    ]);

    $resolved = app(SiteProfileResolver::class)->resolve('EXAMPLE.TEST.');

    expect($resolved?->hostname)->toBe('example.test')
        ->and($resolved?->locale)->toBe('en-GB')
        ->and($resolved?->name)->toBe('Example')
        ->and(app(SiteProfileResolver::class)->resolve('unknown.test'))->toBeNull();
});

test('disabled or hostname-mismatched profiles fail closed', function () {
    createSeoSettings([
        'seo-canonical-base-url' => 'https://other.test',
        'seo-enabled' => false,
    ]);

    expect(app(SiteProfileResolver::class)->resolve('other.test'))->toBeNull();
});

test('content type and public file defaults resolve from the Team settings record', function () {
    $definition = SeoResourceDefinition::make('articles', Article::class)->title('title');
    app(SeoRegistry::class)->register($definition);
    createSeoSettings([
        'seo-canonical-base-url' => 'https://example.test',
        'seo-enabled' => true,
        'seo-resource-articles-default-description' => 'Article fallback',
        'seo-resource-articles-enabled' => true,
        'seo-resource-articles-follow' => 'nofollow',
        'seo-resource-articles-index' => 'noindex',
        'seo-resource-articles-title-pattern' => '[Post Title] - Articles',
        'seo-robots-route-enabled' => false,
        'seo-sitemap-enabled' => false,
    ]);

    $profile = app(SiteProfileResolver::class)->resolve('example.test');
    $defaults = $profile?->defaultsFor($definition);

    expect($profile?->sitemapEnabled)->toBeFalse()
        ->and($profile?->robotsEnabled)->toBeFalse()
        ->and($defaults?->defaultDescription)->toBe('Article fallback')
        ->and($defaults?->titlePattern)->toBe('[Post Title] - Articles')
        ->and($defaults?->index)->toBeFalse()
        ->and($defaults?->follow)->toBeFalse();
});
