<?php

use Aura\Base\Settings\SettingsRegistry;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Resources\SiteProfile;
use Aura\Seo\Services\SeoDiagnostics;
use Aura\Seo\Services\SeoPermissionRegistrar;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;
use Illuminate\Support\Facades\Gate;

function diagnosticsProfile(string $host = 'example.test'): SiteProfile
{
    $profile = SiteProfile::withoutGlobalScopes()->create([
        'fields' => [
            'canonical_base_url' => 'https://'.$host,
            'enabled' => true,
            'hostname' => $host,
            'robots_follow' => true,
            'robots_index' => true,
            'title_template' => '%s | %site%',
        ],
        'title' => 'Example',
    ]);

    config()->set('aura-seo.sites', [$host => ['profile_id' => $profile->getKey()]]);

    return $profile;
}

function registerDiagnosticsDefinition(): void
{
    app(SeoRegistry::class)->register(
        SeoResourceDefinition::make('articles', Article::class)
            ->title('title')
            ->description('summary')
            ->url(function (Article $article): string {
                $canonical = data_get($article->getFieldsAttribute(), 'seo_canonical_url');

                return is_string($canonical) && trim($canonical) !== '' ? $canonical : '/articles/'.$article->getKey();
            })
            ->publicIndex(
                fn (SiteProfileData $site) => Article::query()->where('type', Article::$type)->orderBy('id'),
                fn (Article $article, SiteProfileData $site): bool => true,
            )
            ->sitemap()
    );
}

test('diagnostics surface duplicate canonicals and missing descriptions', function () {
    $profile = diagnosticsProfile();
    registerDiagnosticsDefinition();

    Article::query()->create([
        'fields' => ['seo_canonical_url' => '/shared'],
        'title' => 'First',
    ]);
    Article::query()->create([
        'fields' => ['seo_canonical_url' => '/shared'],
        'title' => 'Second',
    ]);

    $issues = app(SeoDiagnostics::class)->scan($profile->toSeoData());

    expect(collect($issues)->contains(fn ($issue): bool => $issue->message === 'Missing description after all SEO fallbacks resolved.'))->toBeTrue()
        ->and(collect($issues)->contains(fn ($issue): bool => $issue->message === 'Duplicate canonical URL shared across multiple public records.'))->toBeTrue();
});

test('diagnostics are embedded in settings while the command remains permission-aware', function () {
    $profile = diagnosticsProfile();
    registerDiagnosticsDefinition();

    Article::query()->create([
        'fields' => ['seo_canonical_url' => '/shared'],
        'title' => 'First',
    ]);
    Article::query()->create([
        'fields' => ['seo_canonical_url' => '/shared'],
        'title' => 'Second',
    ]);

    app(SeoPermissionRegistrar::class)->synchronize();

    $viewer = createSeoUserWithPermissions([]);
    $auditor = createSeoUserWithPermissions([(string) config('aura-seo.permissions.diagnose')]);

    expect(Gate::forUser($viewer)->denies('aura-seo.diagnose'))->toBeTrue()
        ->and(Gate::forUser($auditor)->allows('aura-seo.diagnose'))->toBeTrue();

    expect(app(SettingsRegistry::class)->has('seo'))->toBeTrue()
        ->and(collect(app(SettingsRegistry::class)->fields())->contains(
            fn (array $field): bool => ($field['view'] ?? null) === 'aura-seo::settings.diagnostics',
        ))->toBeTrue();

    $this->artisan('aura-seo:diagnose', ['--host' => 'example.test'])
        ->expectsOutputToContain('Duplicate canonical URL shared across multiple public records.')
        ->assertExitCode(1);
});
