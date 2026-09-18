<?php

use Aura\Seo\Contracts\SiteProfileResolver;
use Illuminate\Support\Facades\DB;

test('Teams-on resolution requires an explicit matching Team and cannot cross-resolve', function () {
    $user = DB::table('users')->insertGetId(['email' => 'owner@example.test', 'name' => 'Owner', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()]);
    $teamOne = DB::table('teams')->insertGetId(['user_id' => $user, 'name' => 'One', 'created_at' => now(), 'updated_at' => now()]);
    $teamTwo = DB::table('teams')->insertGetId(['user_id' => $user, 'name' => 'Two', 'created_at' => now(), 'updated_at' => now()]);

    createSeoSettings([
        'seo-canonical-base-url' => 'https://one.test',
        'seo-enabled' => true,
    ], $teamOne);
    createSeoSettings([
        'seo-canonical-base-url' => 'https://two.test',
        'seo-enabled' => true,
    ], $teamTwo);

    expect(app(SiteProfileResolver::class)->resolve('one.test')?->team_id)->toBe($teamOne)
        ->and(app(SiteProfileResolver::class)->resolve('two.test')?->team_id)->toBe($teamTwo);
});

test('Teams-on resolution fails closed for ambiguous hosts', function () {
    $user = DB::table('users')->insertGetId(['email' => 'owner@example.test', 'name' => 'Owner', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()]);
    $teamOne = DB::table('teams')->insertGetId(['user_id' => $user, 'name' => 'One', 'created_at' => now(), 'updated_at' => now()]);
    $teamTwo = DB::table('teams')->insertGetId(['user_id' => $user, 'name' => 'Two', 'created_at' => now(), 'updated_at' => now()]);

    createSeoSettings([
        'seo-canonical-base-url' => 'https://same.test',
        'seo-enabled' => true,
    ], $teamOne);
    createSeoSettings([
        'seo-canonical-base-url' => 'https://same.test',
        'seo-enabled' => true,
    ], $teamTwo);

    expect(app(SiteProfileResolver::class)->resolve('same.test'))->toBeNull();
});
