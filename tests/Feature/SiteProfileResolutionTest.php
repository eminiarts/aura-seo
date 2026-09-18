<?php

use Aura\Base\Aura;
use Aura\Base\Resources\Option;
use Aura\Base\Settings\SettingsRegistry;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Resources\SiteProfile;

test('SEO is a single registered settings page instead of an Aura Resource', function () {
    expect(app(Aura::class)->getResources())->not->toContain(SiteProfile::class)
        ->and(app(SettingsRegistry::class)->has('seo'))->toBeTrue();
});

test('Teams-off hostname resolution reads the single settings record', function () {
    Option::withoutGlobalScopes()->create([
        'name' => 'settings',
        'value' => [
            'seo-canonical-base-url' => 'https://example.test',
            'seo-enabled' => true,
            'seo-locale' => 'en-GB',
            'seo-robots-follow' => true,
            'seo-robots-index' => true,
            'seo-separator' => '|',
            'seo-site-name' => 'Example',
            'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
        ],
    ]);

    $resolved = app(SiteProfileResolver::class)->resolve('EXAMPLE.TEST.');

    expect($resolved?->hostname)->toBe('example.test')
        ->and($resolved?->locale)->toBe('en-GB')
        ->and($resolved?->name)->toBe('Example')
        ->and(app(SiteProfileResolver::class)->resolve('unknown.test'))->toBeNull();
});

test('disabled or hostname-mismatched profiles fail closed', function () {
    $profile = SiteProfile::withoutGlobalScopes()->create([
        'fields' => [
            'canonical_base_url' => 'https://other.test',
            'enabled' => false,
            'hostname' => 'other.test',
            'title_template' => '%s',
        ],
        'title' => 'Other',
    ]);
    config()->set('aura-seo.sites', ['other.test' => ['profile_id' => $profile->getKey()]]);

    expect(app(SiteProfileResolver::class)->resolve('other.test'))->toBeNull();

    $profile->update(['fields' => ['enabled' => true, 'hostname' => 'canonical.test']]);

    expect(app(SiteProfileResolver::class)->resolve('other.test'))->toBeNull();
});
