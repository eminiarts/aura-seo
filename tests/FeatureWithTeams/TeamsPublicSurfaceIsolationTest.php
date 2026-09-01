<?php

use Aura\Base\Resources\Team;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Resources\SiteProfile;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Tests\Fixtures\Article;

test('Team-scoped SiteProfiles keep sitemap output isolated per host', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $profileA = SiteProfile::withoutGlobalScopes()->create([
        'team_id' => $teamA->id,
        'fields' => [
            'canonical_base_url' => 'https://team-a.test',
            'enabled' => true,
            'hostname' => 'team-a.test',
            'robots_follow' => true,
            'robots_index' => true,
            'title_template' => '%s | %site%',
        ],
        'title' => 'Team A',
    ]);
    $profileB = SiteProfile::withoutGlobalScopes()->create([
        'team_id' => $teamB->id,
        'fields' => [
            'canonical_base_url' => 'https://team-b.test',
            'enabled' => true,
            'hostname' => 'team-b.test',
            'robots_follow' => true,
            'robots_index' => true,
            'title_template' => '%s | %site%',
        ],
        'title' => 'Team B',
    ]);

    config()->set('aura-seo.sites', [
        'team-a.test' => ['profile_id' => $profileA->getKey(), 'team_id' => $teamA->id],
        'team-b.test' => ['profile_id' => $profileB->getKey(), 'team_id' => $teamB->id],
    ]);

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
