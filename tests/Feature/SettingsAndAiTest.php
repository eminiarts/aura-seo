<?php

use Aura\Base\Ai\AiConnectionResult;
use Aura\Base\Contracts\AiConnector;
use Aura\Base\Resources\Option;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Services\SeoDefaults;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;

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
            expect($prompt)->toContain('A useful article', 'Useful content')
                ->and($systemPrompt)->toContain('meta_title', 'meta_description');

            return '{"meta_title":"Generated title","meta_description":"Generated description"}';
        }

        public function testConnection(): AiConnectionResult
        {
            return new AiConnectionResult(true, 'Connected');
        }
    });

    $manager = createSeoUserWithPermissions([(string) config('aura-seo.permissions.manage')]);

    $this->actingAs($manager)
        ->postJson(route('aura.seo.ai-metadata'), [
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

    $manager = createSeoUserWithPermissions([(string) config('aura-seo.permissions.manage')]);

    $this->actingAs($manager)
        ->postJson(route('aura.seo.ai-metadata'), [
            'title' => 'A useful article',
        ])
        ->assertStatus(500)
        ->assertExactJson([
            'message' => 'AI metadata generation failed.',
        ]);
});
