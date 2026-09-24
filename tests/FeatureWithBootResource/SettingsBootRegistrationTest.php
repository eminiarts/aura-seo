<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Settings;
use Aura\Base\Settings\SettingsRegistry;
use Aura\Base\Settings\SettingsStore;
use Livewire\Livewire;

it('builds and preserves fields for content types registered during provider boot', function (): void {
    $fields = collect(app(SettingsRegistry::class)->page('seo')?->fields ?? []);

    expect($fields->pluck('slug'))
        ->toContain('seo-resource-boot-registered-articles-enabled');

    Aura::flushState();

    expect(collect(app(SettingsRegistry::class)->page('seo')?->fields ?? [])->pluck('slug'))
        ->toContain('seo-resource-boot-registered-articles-enabled');
});

it('saves defaults for a content type registered during provider boot', function (): void {
    $admin = createSeoUserWithPermissions([], superAdmin: true);

    $this->actingAs($admin);

    Livewire::test(Settings::class, ['page' => 'seo'])
        ->set('form.fields.seo-resource-boot-registered-articles-enabled', false)
        ->set('form.fields.seo-resource-boot-registered-articles-title-pattern', '[Post Title] - Boot')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(SettingsStore::class)->get('seo-resource-boot-registered-articles-enabled'))->toBeFalse()
        ->and(app(SettingsStore::class)->get('seo-resource-boot-registered-articles-title-pattern'))->toBe('[Post Title] - Boot');
});
