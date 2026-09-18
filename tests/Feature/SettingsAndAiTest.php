<?php

use Aura\Base\Ai\AiConnectionResult;
use Aura\Base\Contracts\AiConnector;
use Aura\Base\Resources\Option;
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
