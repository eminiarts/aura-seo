<?php

use Aura\Base\Resources\Permission;
use Aura\Base\Settings\SettingsRegistry;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Livewire\SeoDiagnosticsPanel;
use Aura\Seo\Services\SeoDiagnostics;
use Aura\Seo\Services\SeoPermissionRegistrar;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function diagnosticsProfile(string $host = 'example.test'): SiteProfileData
{
    createSeoSettings([
        'seo-canonical-base-url' => 'https://'.$host,
        'seo-enabled' => true,
        'seo-robots-follow' => true,
        'seo-robots-index' => true,
        'seo-site-name' => 'Example',
        'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
    ]);

    return app(SiteProfileResolver::class)->resolve($host) ?? throw new LogicException('SEO settings were not resolved.');
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

    if (! Route::has('aura.seo-test-article.edit')) {
        Route::get('/admin/seo-test-article/{id}/edit', fn (): string => 'edit')
            ->name('aura.seo-test-article.edit');
    }

    Article::query()->create([
        'fields' => ['seo_canonical_url' => '/shared'],
        'title' => 'First',
    ]);
    Article::query()->create([
        'fields' => ['seo_canonical_url' => '/shared'],
        'title' => 'Second',
    ]);

    $issues = app(SeoDiagnostics::class)->scan($profile);

    expect(collect($issues)->contains(fn ($issue): bool => $issue->message === 'Meta description is missing.'))->toBeTrue()
        ->and(collect($issues)->contains(fn ($issue): bool => $issue->message === 'Canonical URL is used by more than one record.'))->toBeTrue()
        ->and(collect($issues)->first(fn ($issue): bool => $issue->message === 'Meta description is missing.')?->actionLabel)->toBe('Edit record');
});

test('diagnostics flag missing site defaults that affect search visibility', function () {
    $profile = new SiteProfileData(
        baseUrl: 'https://example.test',
        enabled: true,
        follow: true,
        hostname: 'example.test',
    );

    $messages = collect(app(SeoDiagnostics::class)->scan($profile))->pluck('message');

    expect($messages)
        ->toContain('Search indexing is disabled by default.')
        ->toContain('Default meta description is missing.')
        ->toContain('Default social image is missing.');
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
        ->and(collect(app(SettingsRegistry::class)->page('seo')->fields)->contains(
            fn (array $field): bool => ($field['view'] ?? null) === 'aura-seo::settings.diagnostics',
        ))->toBeTrue();

    $this->artisan('aura-seo:diagnose', ['--host' => 'example.test'])
        ->expectsOutputToContain('Canonical URL is used by more than one record.')
        ->assertExitCode(1);
});

test('opening SEO settings does not scan public records', function () {
    diagnosticsProfile();
    registerDiagnosticsDefinition();
    Article::query()->create(['title' => 'Do not scan me']);
    $auditor = createSeoUserWithPermissions([(string) config('aura-seo.permissions.diagnose')]);
    $queries = [];

    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->actingAs($auditor)
        ->get(route('aura.settings.page', ['page' => 'seo']))
        ->assertOk()
        ->assertSee('Checks have not been run yet.');

    expect(collect($queries)->contains(
        fn (string $query): bool => str_contains($query, 'seo_test_pages'),
    ))->toBeFalse();
});

test('an authorized user runs diagnostics explicitly', function () {
    diagnosticsProfile();
    registerDiagnosticsDefinition();
    Article::query()->create(['title' => 'Missing a description']);
    $auditor = createSeoUserWithPermissions([(string) config('aura-seo.permissions.diagnose')]);

    $this->actingAs($auditor);

    Livewire::test(SeoDiagnosticsPanel::class)
        ->assertSet('hasRun', false)
        ->assertSee('Checks have not been run yet.')
        ->call('runChecks')
        ->assertSet('hasRun', true)
        ->assertSee('Meta description is missing.');
});

test('plugin permissions are grouped for the existing Aura role editor', function () {
    $registrar = app(SeoPermissionRegistrar::class);
    $definitions = $registrar->definitions();
    $registrar->synchronize();

    expect($definitions[(string) config('aura-seo.permissions.manage')])->toBe([
        'name' => 'Manage Aura SEO',
        'description' => 'Change SEO settings and record metadata.',
    ])->and($definitions[(string) config('aura-seo.permissions.diagnose')])->toBe([
        'name' => 'View Aura SEO checks',
        'description' => 'Review SEO issues and open affected content.',
    ])->and(Permission::withoutGlobalScopes()
        ->where('slug', config('aura-seo.permissions.manage'))
        ->value('group'))->toBe('SEO');
});
