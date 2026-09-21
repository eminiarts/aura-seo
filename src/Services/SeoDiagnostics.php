<?php

namespace Aura\Seo\Services;

use Aura\Base\Resource;
use Aura\Seo\Data\DiagnosticIssue;
use Aura\Seo\Data\SiteProfileData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

final readonly class SeoDiagnostics
{
    public function __construct(private MetadataResolver $resolver, private SeoRegistry $registry, private SitemapRegistry $sitemaps) {}

    /** @return array<int, DiagnosticIssue> */
    public function scan(SiteProfileData $profile): array
    {
        $issues = [];
        $canonicals = [];

        if ($this->registry->all() === []) {
            $issues[] = new DiagnosticIssue(
                'warning',
                'No content types are available for SEO.',
                actionLabel: 'Review settings',
                actionUrl: $this->settingsUrl(),
            );
        }

        if ($profile->sitemapEnabled && $this->sitemaps->all() === []) {
            $issues[] = new DiagnosticIssue(
                'warning',
                'The sitemap has no content types.',
                actionLabel: 'Review settings',
                actionUrl: $this->settingsUrl(),
            );
        }

        if ($profile->defaultOpenGraphImage === null && $profile->defaultSocialImage === null) {
            $issues[] = new DiagnosticIssue(
                'warning',
                'Default social image is missing.',
                actionLabel: 'Review settings',
                actionUrl: $this->settingsUrl(),
            );
        }

        foreach ($this->registry->all() as $definition) {
            $defaults = $profile->defaultsFor($definition);

            if (! $defaults->enabled) {
                continue;
            }

            if (! $definition->hasUrlResolver() || ! $definition->hasPublicBoundary()) {
                $issues[] = new DiagnosticIssue(
                    level: 'warning',
                    message: 'This content type is not ready for SEO checks.',
                    source: $definition->key,
                );

                continue;
            }

            $publicCount = 0;
            $query = $definition->publicQuery($profile);

            if ($query === null) {
                continue;
            }

            foreach ($query->cursor() as $resource) {
                if (! $resource instanceof Model) {
                    continue;
                }

                if (! $resource instanceof $definition->resourceClass || ! $definition->isPublic($resource, $profile)) {
                    continue;
                }

                $publicCount++;
                $metadata = $this->resolver->resolve($resource, $profile, $definition);
                $record = $definition->key.'#'.$resource->getKey();
                [$actionLabel, $actionUrl] = $this->recordAction($resource);

                if ($metadata->title === null) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'SEO title is missing.',
                        $definition->key,
                        $record,
                        actionLabel: $actionLabel,
                        actionUrl: $actionUrl,
                    );
                }

                if ($metadata->description === null) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'Meta description is missing.',
                        $definition->key,
                        $record,
                        actionLabel: $actionLabel,
                        actionUrl: $actionUrl,
                    );
                }

                if ($metadata->title !== null && mb_strlen($metadata->title) > 60) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'SEO title may be too long.',
                        $definition->key,
                        $record,
                        actionLabel: $actionLabel,
                        actionUrl: $actionUrl,
                    );
                }

                if ($metadata->description !== null && mb_strlen($metadata->description) > 160) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'Meta description may be too long.',
                        $definition->key,
                        $record,
                        actionLabel: $actionLabel,
                        actionUrl: $actionUrl,
                    );
                }

                if ($metadata->canonical === null) {
                    $issues[] = new DiagnosticIssue(
                        'error',
                        'Canonical URL is missing or invalid.',
                        $definition->key,
                        $record,
                        actionLabel: $actionLabel,
                        actionUrl: $actionUrl,
                    );

                    continue;
                }

                if (! $metadata->isIndexable()) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'This record is excluded from search and the sitemap.',
                        $definition->key,
                        $record,
                        $metadata->canonical,
                        $actionLabel,
                        $actionUrl,
                    );

                    continue;
                }

                if (! $this->sharesOrigin($metadata->canonical, $profile->baseUrl)) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'Canonical URL points to a different website.',
                        $definition->key,
                        $record,
                        $metadata->canonical,
                        $actionLabel,
                        $actionUrl,
                    );

                    continue;
                }

                $canonicals[$metadata->canonical][] = [
                    'actionLabel' => $actionLabel,
                    'actionUrl' => $actionUrl,
                    'record' => $record,
                ];

                if (count($definition->alternateUrls($resource, $profile)) !== count($metadata->alternates)) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'One or more alternate language URLs are invalid.',
                        $definition->key,
                        $record,
                        $metadata->canonical,
                        $actionLabel,
                        $actionUrl,
                    );
                }
            }

            if ($profile->sitemapEnabled && $defaults->sitemap && $definition->includesSitemap() && $publicCount === 0) {
                $issues[] = new DiagnosticIssue(
                    'warning',
                    'No published records are available for the sitemap.',
                    $definition->key,
                    actionLabel: 'Review settings',
                    actionUrl: $this->settingsUrl(),
                );
            }
        }

        foreach ($canonicals as $canonical => $entries) {
            if (count($entries) < 2) {
                continue;
            }

            foreach ($entries as $entry) {
                $issues[] = new DiagnosticIssue(
                    'error',
                    'Canonical URL is used by more than one record.',
                    record: $entry['record'],
                    canonical: $canonical,
                    actionLabel: $entry['actionLabel'],
                    actionUrl: $entry['actionUrl'],
                );
            }
        }

        return $issues;
    }

    /** @return array{string|null, string|null} */
    private function recordAction(Model $resource): array
    {
        if (! $resource instanceof Resource) {
            return [null, null];
        }

        $route = 'aura.'.$resource::getSlug().'.edit';

        return Route::has($route)
            ? ['Edit record', route($route, ['id' => $resource->getKey()])]
            : ['Edit record', null];
    }

    private function settingsUrl(): ?string
    {
        return Route::has('aura.settings.page') ? route('aura.settings.page', ['page' => 'seo']) : null;
    }

    private function sharesOrigin(string $url, string $baseUrl): bool
    {
        $url = parse_url($url);
        $base = parse_url($baseUrl);

        if (! is_array($url) || ! is_array($base)) {
            return false;
        }

        return strtolower((string) ($url['scheme'] ?? '')) === strtolower((string) ($base['scheme'] ?? ''))
            && strtolower((string) ($url['host'] ?? '')) === strtolower((string) ($base['host'] ?? ''))
            && (int) ($url['port'] ?? 0) === (int) ($base['port'] ?? 0);
    }
}
