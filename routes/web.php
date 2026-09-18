<?php

use Aura\Seo\Http\Controllers\AiMetadataController;
use Aura\Seo\Http\Controllers\RobotsController;
use Aura\Seo\Http\Controllers\SitemapIndexController;
use Aura\Seo\Http\Controllers\SitemapPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    if (config('aura-seo.routes.sitemap', true)) {
        Route::get('/sitemap.xml', SitemapIndexController::class)->name('aura.seo.sitemap.index');
        Route::get('/sitemap/{source}-{page}.xml', SitemapPageController::class)
            ->whereNumber('page')
            ->where('source', '[A-Za-z0-9_-]+')
            ->name('aura.seo.sitemap.page');
    }

    if (config('aura-seo.routes.robots', true)) {
        Route::get('/robots.txt', RobotsController::class)->name('aura.seo.robots');
    }
});

$adminMiddleware = (array) config('aura-seo.admin_middleware', ['web', 'auth']);
$adminMiddleware[] = 'throttle:10,1';

Route::domain(config('aura.domain'))
    ->middleware($adminMiddleware)
    ->prefix(trim(config('aura.path', 'admin'), '/').'/seo')
    ->name('aura.seo.')
    ->group(function (): void {
        Route::post('/ai-metadata', AiMetadataController::class)->name('ai-metadata');
    });
