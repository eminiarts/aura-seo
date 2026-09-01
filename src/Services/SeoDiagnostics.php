<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\DiagnosticIssue;
use Aura\Seo\Data\SiteProfileData;
use Illuminate\Database\Eloquent\Model;

final readonly class SeoDiagnostics
{
    public function __construct(private MetadataResolver $resolver, private SeoRegistry $registry, private SitemapRegistry $sitemaps) {}

    /** @return array<int, DiagnosticIssue> */
    public function scan(SiteProfileData $profile): array
    {
        $issues = [];
        $canonicals = [];

        if ($this->registry->all() === []) {
            $issues[] = new DiagnosticIssue('warning', 'No Aura SEO Resources are registered for metadata resolution.');
        }

        if ($this->sitemaps->all() === []) {
            $issues[] = new DiagnosticIssue('warning', 'No Aura SEO sitemap sources are registered.');
        }

        foreach ($this->registry->all() as $definition) {
            if (! $definition->hasUrlResolver() || ! $definition->hasPublicBoundary()) {
                $issues[] = new DiagnosticIssue(
                    level: 'warning',
                    message: 'This Resource is registered for metadata, but diagnostics cannot enumerate it without an explicit public URL and query boundary.',
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

                if ($metadata->title === null) {
                    $issues[] = new DiagnosticIssue('warning', 'Missing title after all SEO fallbacks resolved.', $definition->key, $record);
                }

                if ($metadata->description === null) {
                    $issues[] = new DiagnosticIssue('warning', 'Missing description after all SEO fallbacks resolved.', $definition->key, $record);
                }

                if ($metadata->canonical === null) {
                    $issues[] = new DiagnosticIssue('error', 'Missing canonical URL after normalization.', $definition->key, $record);

                    continue;
                }

                if (! $metadata->isIndexable()) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'Public record resolves to noindex or nofollow and will be excluded from the sitemap.',
                        $definition->key,
                        $record,
                        $metadata->canonical,
                    );

                    continue;
                }

                if (! $this->sharesOrigin($metadata->canonical, $profile->baseUrl)) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'Canonical points to another origin and will be excluded from this SiteProfile sitemap.',
                        $definition->key,
                        $record,
                        $metadata->canonical,
                    );

                    continue;
                }

                $canonicals[$metadata->canonical][] = $record;

                if (count($definition->alternateUrls($resource, $profile)) !== count($metadata->alternates)) {
                    $issues[] = new DiagnosticIssue(
                        'warning',
                        'One or more alternate URLs were invalid and were omitted from rendered metadata.',
                        $definition->key,
                        $record,
                        $metadata->canonical,
                    );
                }
            }

            if ($definition->includesSitemap() && $publicCount === 0) {
                $issues[] = new DiagnosticIssue(
                    'warning',
                    'Sitemap source has no public records for the active SiteProfile.',
                    $definition->key,
                );
            }
        }

        foreach ($canonicals as $canonical => $records) {
            if (count($records) < 2) {
                continue;
            }

            foreach ($records as $record) {
                $issues[] = new DiagnosticIssue(
                    'error',
                    'Duplicate canonical URL shared across multiple public records.',
                    record: $record,
                    canonical: $canonical,
                );
            }
        }

        return $issues;
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
