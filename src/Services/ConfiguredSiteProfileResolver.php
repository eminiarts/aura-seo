<?php

namespace Aura\Seo\Services;

use Aura\Base\Resources\Option;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Resources\SiteProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the single SEO settings profile for a Team. Legacy SiteProfile
 * mappings remain a read-only fallback so upgrades do not drop public tags.
 */
final class ConfiguredSiteProfileResolver implements SiteProfileResolver
{
    /** @return list<string> */
    public function hosts(): array
    {
        return $this->settingsOptions()
            ->map(fn (Option $option): ?SiteProfileData => $this->profileFromOption($option))
            ->filter(fn (?SiteProfileData $profile): bool => $profile?->enabled === true)
            ->map(fn (SiteProfileData $profile): string => $profile->hostname)
            ->merge(array_keys((array) config('aura-seo.sites', [])))
            ->filter(fn (mixed $host): bool => is_string($host) && $host !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function resolve(string $hostname): ?SiteProfileData
    {
        $hostname = $this->normalizeHostname($hostname);

        if ($hostname === null) {
            return null;
        }

        $matches = $this->settingsOptions()
            ->map(fn (Option $option): ?SiteProfileData => $this->profileFromOption($option))
            ->filter(fn (?SiteProfileData $profile): bool => $profile?->enabled === true && $profile->hostname === $hostname)
            ->values();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            return null;
        }

        return $this->resolveLegacyProfile($hostname);
    }

    public function resolveForTeam(?int $teamId): ?SiteProfileData
    {
        $profiles = $this->settingsOptions($teamId)
            ->map(fn (Option $option): ?SiteProfileData => $this->profileFromOption($option))
            ->filter(fn (?SiteProfileData $profile): bool => $profile?->enabled === true)
            ->values();

        if ($profiles->count() === 1) {
            return $profiles->first();
        }

        foreach ((array) config('aura-seo.sites', []) as $host => $site) {
            if (! is_string($host) || ! is_array($site)) {
                continue;
            }

            if (config('aura.teams') && (int) ($site['team_id'] ?? 0) !== (int) $teamId) {
                continue;
            }

            if ($profile = $this->resolveLegacyProfile($host)) {
                return $profile;
            }
        }

        return null;
    }

    private function normalizeHostname(string $hostname): ?string
    {
        $hostname = strtolower(rtrim(trim($hostname), '.'));

        if ($hostname === '' || str_contains($hostname, '/') || str_contains($hostname, ':')) {
            return null;
        }

        return filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $hostname : null;
    }

    private function profileFromOption(Option $option): ?SiteProfileData
    {
        $value = $option->getAttribute('value');

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return null;
        }

        $baseUrl = trim((string) ($value['seo-canonical-base-url'] ?? config('aura-seo.settings.canonical_base_url', '')));
        $hostname = $this->normalizeHostname((string) parse_url($baseUrl, PHP_URL_HOST));

        if ($baseUrl === '' || $hostname === null || ! in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        return new SiteProfileData(
            baseUrl: rtrim($baseUrl, '/'),
            enabled: filter_var($value['seo-enabled'] ?? config('aura-seo.settings.enabled', false), FILTER_VALIDATE_BOOL),
            follow: filter_var($value['seo-robots-follow'] ?? config('aura-seo.settings.robots_follow', false), FILTER_VALIDATE_BOOL),
            hostname: $hostname,
            id: is_numeric($option->getKey()) ? (int) $option->getKey() : null,
            index: filter_var($value['seo-robots-index'] ?? config('aura-seo.settings.robots_index', false), FILTER_VALIDATE_BOOL),
            locale: (string) ($value['seo-locale'] ?? config('aura-seo.settings.locale', 'en')),
            defaultDescription: $this->nonEmptyString($value['seo-default-description'] ?? config('aura-seo.settings.default_description')),
            defaultOpenGraphImage: $this->resolveImage($value['seo-default-open-graph-image'] ?? config('aura-seo.settings.default_open_graph_image'), $option),
            defaultTwitterImage: $this->resolveImage($value['seo-default-twitter-image'] ?? config('aura-seo.settings.default_twitter_image'), $option),
            name: $this->nonEmptyString($value['seo-site-name'] ?? config('aura-seo.settings.site_name', config('app.name'))),
            robotsRules: $this->nonEmptyString($value['seo-robots-rules'] ?? config('aura-seo.settings.robots_rules')),
            separator: (string) ($value['seo-separator'] ?? config('aura-seo.settings.separator', '|')),
            teamId: $option->getAttribute('team_id') === null ? null : (int) $option->getAttribute('team_id'),
            titleTemplate: (string) ($value['seo-title-pattern'] ?? config('aura-seo.settings.title_pattern', '[Post Title] [Separator] [Site Name]')),
        );
    }

    private function resolveImage(mixed $value, Option $option): ?string
    {
        $resolver = config('aura-seo.image_url_resolver');

        if (is_callable($resolver)) {
            return $this->nonEmptyString(app()->call($resolver, ['value' => $value, 'resource' => $option]));
        }

        if (is_array($value) && isset($value['url'])) {
            return $this->nonEmptyString($value['url']);
        }

        return is_string($value) && preg_match('#^https?://#i', $value) ? $value : null;
    }

    private function resolveLegacyProfile(string $hostname): ?SiteProfileData
    {
        $sites = (array) config('aura-seo.sites', []);
        $site = (array) ($sites[$hostname] ?? []);

        if (! isset($site['profile_id']) || ! is_numeric($site['profile_id'])) {
            return null;
        }

        $teams = (bool) config('aura.teams');
        $teamId = $site['team_id'] ?? null;

        if ($teams && (! is_numeric($teamId) || (int) $teamId < 1)) {
            return null;
        }

        $query = SiteProfile::withoutGlobalScopes()->whereKey((int) $site['profile_id']);

        if (Schema::hasColumn((new SiteProfile)->getTable(), 'team_id')) {
            $teams ? $query->where('team_id', (int) $teamId) : $query->whereNull('team_id');
        }

        $profile = $query->first();
        $data = $profile?->toSeoData();

        if (! $data?->enabled || $this->normalizeHostname($data->hostname) !== $hostname) {
            return null;
        }

        return $data;
    }

    /** @return Collection<int, Option> */
    private function settingsOptions(?int $teamId = null): Collection
    {
        $query = Option::withoutGlobalScopes();

        if (! config('aura.teams')) {
            return $query->where('name', 'settings')->get();
        }

        $query->where('name', 'like', 'team.%.settings');

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        return $query->get();
    }

    private function nonEmptyString(mixed $value): ?string
    {
        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
