<?php

use Aura\Base\Ai\AiConnectionResult;
use Aura\Base\Contracts\AiConnector;
use Aura\Base\Fields\Tab;
use Aura\Base\Resources\Option;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Services\SeoDefaults;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Services\SeoResourceSettings;
use Aura\Seo\Settings\SeoSettingsPage;
use Aura\Seo\Tests\Fixtures\Article;
use Illuminate\Support\Facades\Route;

it('fills a new record meta title from the configured pattern without overwriting explicit metadata', function (): void {
    Option::withoutGlobalScopes()->create([
        'name' => 'settings',
        'value' => [
            'seo-separator' => '•',
            'seo-site-name' => 'Example',
            'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
        ],
    ]);

    $generated = Article::query()->create([
        'fields' => ['seo_meta_title' => ''],
        'title' => 'A new article',
    ])->fresh();
    $explicit = Article::query()->create([
        'fields' => ['seo_meta_title' => 'A custom search title'],
        'title' => 'Another article',
    ])->fresh();

    expect($generated->seo_meta_title)->toBe('A new article • Example')
        ->and($explicit->seo_meta_title)->toBe('A custom search title');
});

it('uses the registered title field when generating a default meta title', function (): void {
    createSeoSettings([
        'seo-separator' => '|',
        'seo-site-name' => 'Example',
        'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
    ]);
    app(SeoRegistry::class)->register(SeoResourceDefinition::make('articles', Article::class)->title('overview'));

    $article = new Article;
    $article->setAttribute('overview', 'Mapped overview');
    $fallback = new Article;
    $fallback->setAttribute('title', 'Fallback title');

    expect(app(SeoDefaults::class)->metaTitle($article, null))->toBe('Mapped overview | Example');
    expect(app(SeoDefaults::class)->metaTitle($fallback, null))->toBe('Fallback title | Example');
});

it('uses a content type title pattern for new records when one is configured', function (): void {
    createSeoSettings([
        'seo-resource-articles-title-pattern' => '[Post Title] - Articles',
        'seo-site-name' => 'Example',
        'seo-title-pattern' => '[Post Title] | [Site Name]',
    ]);
    app(SeoRegistry::class)->register(SeoResourceDefinition::make('articles', Article::class)->title('title'));

    $article = new Article;
    $article->setAttribute('title', 'Content defaults');

    expect(app(SeoDefaults::class)->metaTitle($article, null))->toBe('Content defaults - Articles');
});

it('builds the SEO settings page from Aura fields without a separate permissions tab', function (): void {
    $registry = new SeoRegistry;
    $registry->register(SeoResourceDefinition::make('articles', Article::class)->title('title'));

    $page = SeoSettingsPage::make($registry);
    $fields = collect($page->fields);

    expect($fields->where('type', Tab::class)->pluck('name')->all())->toBe([
        'Overview',
        'Site defaults',
        'Content types',
        'Diagnostics',
        'Sitemap & robots',
    ])->and($page->viewAbility)->toBe('aura-seo.view')
        ->and($page->updateAbility)->toBe('aura-seo.manage')
        ->and($fields->pluck('name'))->not->toContain('Permissions')
        ->and($fields->pluck('slug'))->toContain(
            SeoResourceSettings::slug('articles', 'enabled'),
            SeoResourceSettings::slug('articles', 'title-pattern'),
            SeoResourceSettings::slug('articles', 'default-social-image'),
        );
});

it('accepts stable kebab-case and snake-case resource keys', function (): void {
    $registry = new SeoRegistry;

    $registry->register(SeoResourceDefinition::make('blog-posts', Article::class));
    $registry->register(SeoResourceDefinition::make('news_articles', Article::class));

    expect(array_keys($registry->all()))->toBe(['blog-posts', 'news_articles']);
});

it('renders the production SEO settings page through the standard Aura settings layout', function (): void {
    $admin = createSeoUserWithPermissions([], superAdmin: true);

    foreach (['seo-test-article', 'seo-test-page'] as $slug) {
        if (! Route::has("aura.{$slug}.index")) {
            Route::get("/admin/{$slug}", fn (): string => $slug)->name("aura.{$slug}.index");
        }
    }

    $this->actingAs($admin)
        ->get(route('aura.settings.page', ['page' => 'seo']))
        ->assertOk()
        ->assertSee('SEO overview')
        ->assertSee('Site defaults')
        ->assertSee('Content types')
        ->assertSee('Diagnostics')
        ->assertSee('Sitemap &amp; robots', false)
        ->assertDontSee('Capability matrix');
});

it('exposes string title and description mappings for the AI prefill field', function (): void {
    $definition = SeoResourceDefinition::make('articles', Article::class)
        ->title('headline')
        ->description('overview');

    expect($definition->titleField())->toBe('headline')
        ->and($definition->descriptionField())->toBe('overview')
        ->and(SeoResourceDefinition::make('pages', Article::class)->title(fn (): string => 'Title')->titleField())->toBeNull();
});

it('generates metadata through the core AI connector', function (): void {
    app()->instance(AiConnector::class, new class implements AiConnector
    {
        public function generate(string $prompt, ?string $systemPrompt = null): string
        {
            expect($prompt)->toContain('Content type: Articles', 'A useful article', 'Useful content')
                ->and($systemPrompt)->toContain('meta_title', 'meta_description');

            return '{"meta_title":"Generated title","meta_description":"Generated description"}';
        }

        public function testConnection(): AiConnectionResult
        {
            return new AiConnectionResult(true, 'Connected');
        }
    });

    app(SeoRegistry::class)->register(
        SeoResourceDefinition::make('articles', Article::class)->title('title')->description('summary')
    );
    $manager = createSeoUserWithPermissions([
        (string) config('aura-seo.permissions.manage'),
        'create-seo-test-article',
    ]);

    $this->actingAs($manager)
        ->postJson(route('aura.seo.ai-metadata'), [
            'resource' => 'articles',
            'title' => 'A useful article',
            'content' => '<p>Useful content</p>',
        ])
        ->assertOk()
        ->assertExactJson([
            'meta_title' => 'Generated title',
            'meta_description' => 'Generated description',
        ]);
});

it('does not expose unexpected AI connector failures', function (): void {
    app()->instance(AiConnector::class, new class implements AiConnector
    {
        public function generate(string $prompt, ?string $systemPrompt = null): string
        {
            throw new Error('sensitive implementation detail');
        }

        public function testConnection(): AiConnectionResult
        {
            return new AiConnectionResult(false, 'Not connected');
        }
    });

    app(SeoRegistry::class)->register(SeoResourceDefinition::make('articles', Article::class));
    $manager = createSeoUserWithPermissions([
        (string) config('aura-seo.permissions.manage'),
        'create-seo-test-article',
    ]);

    $this->actingAs($manager)
        ->postJson(route('aura.seo.ai-metadata'), [
            'resource' => 'articles',
            'title' => 'A useful article',
        ])
        ->assertStatus(500)
        ->assertExactJson([
            'message' => 'AI metadata generation failed.',
        ]);
});

it('requires permission for the target resource before generating metadata', function (): void {
    app(SeoRegistry::class)->register(SeoResourceDefinition::make('articles', Article::class));
    $manager = createSeoUserWithPermissions([(string) config('aura-seo.permissions.manage')]);

    $this->actingAs($manager)
        ->postJson(route('aura.seo.ai-metadata'), [
            'resource' => 'articles',
            'title' => 'A useful article',
        ])
        ->assertForbidden();
});

it('requires update permission for an existing AI metadata target', function (): void {
    app(SeoRegistry::class)->register(SeoResourceDefinition::make('articles', Article::class));
    $article = Article::query()->create(['title' => 'Existing article']);
    $manager = createSeoUserWithPermissions([(string) config('aura-seo.permissions.manage')]);

    $this->actingAs($manager)
        ->postJson(route('aura.seo.ai-metadata'), [
            'resource' => 'articles',
            'record_id' => (string) $article->getKey(),
            'title' => 'Existing article',
        ])
        ->assertForbidden();
});

it('allows diagnostics users to view SEO settings without changing them', function (): void {
    $diagnosticsUser = createSeoUserWithPermissions([(string) config('aura-seo.permissions.diagnose')]);

    $this->actingAs($diagnosticsUser)
        ->get(route('aura.settings.page', ['page' => 'seo']))
        ->assertOk()
        ->assertDontSee('Save');

    $manager = createSeoUserWithPermissions([(string) config('aura-seo.permissions.manage')]);

    $this->actingAs($manager)
        ->get(route('aura.settings.page', ['page' => 'seo']))
        ->assertOk()
        ->assertSee('Save');
});

it('preserves SEO metadata when an authenticated editor lacks the SEO management permission', function (): void {
    createSeoSettings([
        'seo-separator' => '|',
        'seo-site-name' => 'Example',
        'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
    ]);
    $article = Article::query()->create([
        'fields' => [
            'seo_meta_description' => 'Original description',
            'seo_meta_title' => 'Original title',
        ],
        'title' => 'Original article',
    ])->fresh();
    $editor = createSeoUserWithPermissions(['update-seo-test-article']);

    $this->actingAs($editor);
    $article->update([
        'fields' => [
            'seo_meta_description' => 'Injected description',
            'seo_meta_title' => 'Injected title',
        ],
        'title' => 'Updated article',
    ]);

    expect($article->fresh())
        ->title->toBe('Updated article')
        ->seo_meta_title->toBe('Original title')
        ->seo_meta_description->toBe('Original description');
});

it('keeps AI suggestions in a review step before applying them to the form', function (): void {
    $template = file_get_contents(__DIR__.'/../../resources/views/fields/ai-prefill.blade.php');

    expect($template)->toContain(
        'data-ai-suggestion-review',
        'this.suggestion = payload',
        'async applySelected()',
        "__('Apply selected to form')",
    )->not->toContain("await \$wire.set(@js('form.fields.'.\$slug('meta_title')), payload.meta_title)");
});
