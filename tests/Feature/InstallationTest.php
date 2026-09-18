<?php

use Aura\Base\Settings\SettingsRegistry;
use Aura\Seo\Services\SitemapRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

it('installs as a free Aura package with explicit public surfaces and optional redirects only', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $commands = array_keys(Artisan::all());

    expect($composer['license'])->toBe('MIT')
        ->and($composer['require'])->not->toHaveKey('eminiarts/aura-redirects')
        ->and($composer['suggest'])->toHaveKey('eminiarts/aura-redirects')
        ->and(Route::has('aura.seo.sitemap.index'))->toBeTrue()
        ->and(Route::has('aura.seo.sitemap.page'))->toBeTrue()
        ->and(Route::has('aura.seo.robots'))->toBeTrue()
        ->and(Route::has('aura.seo.diagnostics'))->toBeFalse()
        ->and(Route::has('aura.seo.ai-metadata'))->toBeTrue()
        ->and(app(SettingsRegistry::class)->has('seo'))->toBeTrue()
        ->and($commands)->toContain('aura-seo:diagnose', 'aura-seo:sync-permissions')
        ->and(app(SitemapRegistry::class))->toBeInstanceOf(SitemapRegistry::class);
});
