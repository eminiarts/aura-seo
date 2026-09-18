<?php

use Aura\Base\Settings\SettingsRegistry;
use Aura\Seo\Contracts\SiteProfileResolver;

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
