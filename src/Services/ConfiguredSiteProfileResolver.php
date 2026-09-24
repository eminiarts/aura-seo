<?php

namespace Aura\Seo\Services;

use Aura\Base\Settings\SettingsStore;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SeoResourceDefaults;
use Aura\Seo\Data\SiteProfileData;

final class ConfiguredSiteProfileResolver implements SiteProfileResolver
{
    public function __construct(private readonly SeoRegistry $registry, private readonly SettingsStore $settings) {}

    /** @return list<string> */
    public function hosts(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (array $settings): ?string => ($profile = $this->profileFromValues($settings['values'], $settings['team_id']))?->enabled
                ? $profile->hostname
                : null,
            $this->settings->all(),
        ))));
    }

    public function resolve(string $hostname): ?SiteProfileData
    {
        $hostname = $this->normalizeHostname($hostname);

        if ($hostname === null) {
            return null;
        }

        $matches = array_values(array_filter(array_map(
            fn (array $settings): ?SiteProfileData => $this->profileFromValues($settings['values'], $settings['team_id']),
            $this->settings->all(),
        ), fn (?SiteProfileData $profile): bool => $profile?->enabled === true && $profile->hostname === $hostname));

        if (count($matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    public function resolveForTeam(?int $teamId): ?SiteProfileData
    {
        $profile = $this->profileFromValues($this->settings->values(teamId: $teamId), $teamId);

        return $profile?->enabled ? $profile : null;
    }

    private function normalizeHostname(string $hostname): ?string
    {
        $hostname = strtolower(rtrim(trim($hostname), '.'));

        if ($hostname === '' || str_contains($hostname, '/') || str_contains($hostname, ':')) {
            return null;
        }

        return filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $hostname : null;
    }

    /** @param array<string, mixed> $values */
    private function profileFromValues(array $values, ?int $teamId): ?SiteProfileData
    {
        $baseUrl = trim((string) ($values['seo-canonical-base-url'] ?? config('aura-seo.settings.canonical_base_url', '')));
        $hostname = $this->normalizeHostname((string) parse_url($baseUrl, PHP_URL_HOST));

        if ($baseUrl === '' || $hostname === null || ! in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        return new SiteProfileData(
            baseUrl: rtrim($baseUrl, '/'),
            enabled: filter_var($values['seo-enabled'] ?? config('aura-seo.settings.enabled', false), FILTER_VALIDATE_BOOL),
            follow: filter_var($values['seo-robots-follow'] ?? config('aura-seo.settings.robots_follow', false), FILTER_VALIDATE_BOOL),
            hostname: $hostname,
            index: filter_var($values['seo-robots-index'] ?? config('aura-seo.settings.robots_index', false), FILTER_VALIDATE_BOOL),
            locale: (string) ($values['seo-locale'] ?? config('aura-seo.settings.locale', 'en')),
            defaultDescription: $this->nonEmptyString($values['seo-default-description'] ?? config('aura-seo.settings.default_description')),
            defaultOpenGraphImage: $this->resolveImage($values['seo-default-open-graph-image'] ?? config('aura-seo.settings.default_open_graph_image')),
            defaultTwitterImage: $this->resolveImage($values['seo-default-twitter-image'] ?? config('aura-seo.settings.default_twitter_image')),
            name: $this->nonEmptyString($values['seo-site-name'] ?? config('aura-seo.settings.site_name', config('app.name'))),
            robotsRules: $this->nonEmptyString($values['seo-robots-rules'] ?? config('aura-seo.settings.robots_rules')),
            separator: (string) ($values['seo-separator'] ?? config('aura-seo.settings.separator', '|')),
            teamId: $teamId,
            titleTemplate: (string) ($values['seo-title-pattern'] ?? config('aura-seo.settings.title_pattern', '[Post Title] [Separator] [Site Name]')),
            sitemapEnabled: (bool) config('aura-seo.routes.sitemap', true),
            robotsEnabled: (bool) config('aura-seo.routes.robots', true),
            resourceDefaults: $this->resourceDefaults($values),
        );
    }

    /** @param array<string, mixed> $values
     * @return array<string, SeoResourceDefaults>
     */
    private function resourceDefaults(array $values): array
    {
        $defaults = [];

        foreach ($this->registry->all() as $definition) {
            $defaults[$definition->key] = new SeoResourceDefaults(
                enabled: filter_var($values[SeoResourceSettings::slug($definition, 'enabled')] ?? true, FILTER_VALIDATE_BOOL),
                sitemap: filter_var(
                    $values[SeoResourceSettings::slug($definition, 'sitemap')] ?? $definition->includesSitemap(),
                    FILTER_VALIDATE_BOOL,
                ),
                titlePattern: $this->nonEmptyString($values[SeoResourceSettings::slug($definition, 'title-pattern')] ?? null),
                defaultDescription: $this->nonEmptyString($values[SeoResourceSettings::slug($definition, 'default-description')] ?? null),
                defaultSocialImage: $this->resolveImage($values[SeoResourceSettings::slug($definition, 'default-social-image')] ?? null),
                index: $this->nullableBool($values[SeoResourceSettings::slug($definition, 'index')] ?? null, 'index', 'noindex'),
                follow: $this->nullableBool($values[SeoResourceSettings::slug($definition, 'follow')] ?? null, 'follow', 'nofollow'),
            );
        }

        return $defaults;
    }

    private function nullableBool(bool|string|null $value, string $truthy, string $falsy): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return match ($value) {
            $truthy => true,
            $falsy => false,
            default => null,
        };
    }

    private function resolveImage(mixed $value): ?string
    {
        $resolver = config('aura-seo.image_url_resolver');

        if (is_callable($resolver)) {
            return $this->nonEmptyString(app()->call($resolver, ['value' => $value, 'resource' => null]));
        }

        if (is_array($value) && isset($value['url'])) {
            return $this->nonEmptyString($value['url']);
        }

        return is_string($value) && preg_match('#^https?://#i', $value) ? $value : null;
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
