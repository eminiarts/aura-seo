<?php

use Aura\Base\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Image;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Resources\SiteProfile;

test('SiteProfile is a registered editable Aura Resource composed from Aura fields', function () {
    expect(app(Aura::class)->getResources())->toContain(SiteProfile::class);

    $types = collect(SiteProfile::getFields())->pluck('type')->all();

    expect($types)->toContain(Text::class)
        ->toContain(Textarea::class)
        ->toContain(Boolean::class)
        ->toContain(Image::class);
});

test('Teams-off hostname mapping resolves only an enabled unscoped profile', function () {
    $profile = SiteProfile::withoutGlobalScopes()->create([
        'fields' => [
            'canonical_base_url' => 'https://example.test',
            'enabled' => true,
            'hostname' => 'example.test',
            'locale' => 'en-GB',
            'robots_follow' => true,
            'robots_index' => true,
            'title_template' => '%s | %site%',
        ],
        'title' => 'Example',
    ]);

    config()->set('aura-seo.sites', ['example.test' => ['profile_id' => $profile->getKey()]]);

    $resolved = app(SiteProfileResolver::class)->resolve('EXAMPLE.TEST.');

    expect($resolved?->is($profile))->toBeTrue()
        ->and($resolved?->toSeoData()->locale)->toBe('en-GB')
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
