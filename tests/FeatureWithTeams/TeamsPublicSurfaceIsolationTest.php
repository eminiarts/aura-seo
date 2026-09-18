<?php

use Aura\Base\Resources\Team;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;

test('Team-scoped SEO settings keep sitemap output isolated per host', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    createSeoSettings([
        'seo-canonical-base-url' => 'https://team-a.test',
        'seo-enabled' => true,
        'seo-robots-follow' => true,
        'seo-robots-index' => true,
        'seo-site-name' => 'Team A',
        'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
    ], $teamA->id);
    createSeoSettings([
        'seo-canonical-base-url' => 'https://team-b.test',
        'seo-enabled' => true,
        'seo-robots-follow' => true,
        'seo-robots-index' => true,
        'seo-site-name' => 'Team B',
        'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
    ], $teamB->id);

    app(SeoRegistry::class)->register(
        SeoResourceDefinition::make('articles', Article::class)
            ->title('title')
            ->description('summary')
            ->url(fn (Article $article): string => '/articles/'.$article->getKey())
            ->publicIndex(
                fn (SiteProfileData $site) => Article::withoutGlobalScopes()->where('type', Article::$type)->where('team_id', $site->teamId)->orderBy('id'),
                fn (Article $article, SiteProfileData $site): bool => (int) $article->team_id === $site->teamId,
            )
            ->sitemap()
    );

    $teamAArticle = Article::withoutGlobalScopes()->create([
        'summary' => 'Team A description',
        'team_id' => $teamA->id,
        'title' => 'Team A article',
    ]);
    $teamBArticle = Article::withoutGlobalScopes()->create([
        'summary' => 'Team B description',
        'team_id' => $teamB->id,
        'title' => 'Team B article',
    ]);

    $this->get('https://team-a.test/sitemap/articles-1.xml')
        ->assertOk()
        ->assertSee('https://team-a.test/articles/'.$teamAArticle->getKey(), false)
        ->assertDontSee('https://team-b.test/articles/'.$teamBArticle->getKey(), false);

    $this->get('https://team-b.test/sitemap/articles-1.xml')
        ->assertOk()
        ->assertSee('https://team-b.test/articles/'.$teamBArticle->getKey(), false)
        ->assertDontSee('https://team-a.test/articles/'.$teamAArticle->getKey(), false);
});
