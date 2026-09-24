<?php

use Aura\Base\Resources\Option;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Services\SeoCache;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;

function sitemapProfile(string $host = 'example.test', bool $index = true, ?string $rules = null): SiteProfileData
{
    createSeoSettings([
        'seo-canonical-base-url' => 'https://'.$host,
        'seo-enabled' => true,
        'seo-robots-follow' => true,
        'seo-robots-index' => $index,
        'seo-robots-rules' => $rules,
        'seo-site-name' => 'Example',
        'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
    ]);

    return app(SiteProfileResolver::class)->resolve($host) ?? throw new LogicException('SEO settings were not resolved.');
}

function registerSitemapDefinition(): void
{
    app(SeoRegistry::class)->register(
        SeoResourceDefinition::make('articles', Article::class)
            ->title('title')
            ->description('summary')
            ->lastModified('updated_at')
            ->url(fn (Article $article): string => '/articles/'.$article->getKey())
            ->publicIndex(
                fn (SiteProfileData $site) => Article::query()->where('type', Article::$type)->orderBy('id'),
                fn (Article $article, SiteProfileData $site): bool => $article->title !== 'Hidden',
            )
            ->sitemap()
    );
}

test('sitemap XML lists only explicit public indexable canonicals and chunks output', function () {
    sitemapProfile();
    registerSitemapDefinition();
    config()->set('aura-seo.sitemap.chunk_size', 1);

    $first = Article::query()->create([
        'summary' => 'First description',
        'title' => 'First',
    ]);
    $second = Article::query()->create([
        'summary' => 'Second description',
        'title' => 'Second',
    ]);
    $hidden = Article::query()->create([
        'fields' => ['seo_index' => false],
        'summary' => 'Hidden description',
        'title' => 'Hidden',
    ]);

    $index = $this->get('https://example.test/sitemap.xml');

    $index->assertOk()
        ->assertHeader('content-type', 'application/xml; charset=UTF-8')
        ->assertSee('/sitemap/articles-1.xml', false)
        ->assertSee('/sitemap/articles-2.xml', false)
        ->assertDontSee('/sitemap/articles-3.xml', false);

    $this->get('https://example.test/sitemap/articles-1.xml')
        ->assertOk()
        ->assertSee('https://example.test/articles/'.$first->getKey(), false)
        ->assertDontSee('https://example.test/articles/'.$second->getKey(), false);

    $this->get('https://example.test/sitemap/articles-2.xml')
        ->assertOk()
        ->assertSee('https://example.test/articles/'.$second->getKey(), false)
        ->assertDontSee('https://example.test/articles/'.$hidden->getKey(), false);

    $this->get('https://unknown.test/sitemap.xml')
        ->assertNotFound();
});

test('robots.txt is sanitized and remains fail-closed for unknown hosts', function () {
    sitemapProfile('robots.test', false, "# keep private\nAllow: /private\nDisallow: /admin\nCrawl-delay: 5\nNope: invalid");

    $private = $this->get('https://robots.test/robots.txt');

    $private->assertOk()
        ->assertHeader('content-type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /')
        ->assertSee('Disallow: /admin')
        ->assertSee('Crawl-delay: 5')
        ->assertSee('Sitemap: https://robots.test/sitemap.xml')
        ->assertDontSee('Allow: /private')
        ->assertDontSee('Nope: invalid');

    $this->get('https://unknown.test/robots.txt')
        ->assertOk()
        ->assertSee("User-agent: *\nDisallow: /", false)
        ->assertDontSee('Sitemap:');
});

test('deployment configuration can disable the sitemap and robots routes', function () {
    config([
        'aura-seo.routes.robots' => false,
        'aura-seo.routes.sitemap' => false,
    ]);
    createSeoSettings([
        'seo-canonical-base-url' => 'https://disabled.test',
        'seo-enabled' => true,
    ]);
    registerSitemapDefinition();

    $this->get('https://disabled.test/sitemap.xml')->assertNotFound();
    $this->get('https://disabled.test/robots.txt')->assertNotFound();
});

test('a content type can be removed from the sitemap without disabling its SEO output', function () {
    registerSitemapDefinition();
    createSeoSettings([
        'seo-canonical-base-url' => 'https://example.test',
        'seo-enabled' => true,
        'seo-resource-articles-enabled' => true,
        'seo-resource-articles-sitemap' => false,
        'seo-robots-follow' => true,
        'seo-robots-index' => true,
    ]);
    Article::query()->create([
        'summary' => 'Still has SEO metadata.',
        'title' => 'Not in sitemap',
    ]);

    $this->get('https://example.test/sitemap.xml')
        ->assertOk()
        ->assertDontSee('/sitemap/articles-1.xml', false);
    $this->get('https://example.test/sitemap/articles-1.xml')->assertNotFound();
});

test('cache keys are reused and invalidated after registered resource changes', function () {
    $profile = sitemapProfile();
    registerSitemapDefinition();

    $article = Article::query()->create([
        'summary' => 'Cache',
        'title' => 'Cache',
    ]);

    $calls = 0;
    $cache = app(SeoCache::class);

    expect($cache->remember($profile, 'probe', function () use (&$calls): int {
        $calls++;

        return $calls;
    }))->toBe(1)
        ->and($cache->remember($profile, 'probe', function () use (&$calls): int {
            $calls++;

            return $calls;
        }))->toBe(1);

    $article->update(['title' => 'Cache updated']);

    expect($cache->remember($profile, 'probe', function () use (&$calls): int {
        $calls++;

        return $calls;
    }))->toBe(2);

    $settings = Option::withoutGlobalScopes()->where('name', 'settings')->firstOrFail();
    $settings->update(['value' => array_replace($settings->value, ['seo-site-name' => 'Changed'])]);

    expect($cache->remember($profile, 'probe', function () use (&$calls): int {
        $calls++;

        return $calls;
    }))->toBe(3);
});
