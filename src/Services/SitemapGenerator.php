<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Support\CanonicalUrlNormalizer;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use XMLWriter;

final readonly class SitemapGenerator
{
    public function __construct(
        private CanonicalUrlNormalizer $canonicals,
        private MetadataResolver $resolver,
        private SeoCache $cache,
        private SitemapRegistry $registry,
    ) {}

    public function renderIndex(SiteProfileData $profile): string
    {
        return $this->cache->remember($profile, 'sitemap:index', function () use ($profile): string {
            $writer = $this->writer();
            $writer->startDocument('1.0', 'UTF-8');
            $writer->startElement('sitemapindex');
            $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

            foreach ($this->registry->all() as $definition) {
                if (! $profile->defaultsFor($definition)->sitemap) {
                    continue;
                }

                foreach (array_keys($this->chunksFor($definition, $profile)) as $pageIndex) {
                    $page = $pageIndex + 1;
                    $entries = $this->entriesFor($definition, $profile);
                    $lastModified = $this->latestLastModified($entries);

                    $writer->startElement('sitemap');
                    $writer->writeElement('loc', $this->publicUrl($profile, "/sitemap/{$definition->key}-{$page}.xml"));

                    if ($lastModified !== null) {
                        $writer->writeElement('lastmod', $lastModified);
                    }

                    $writer->endElement();
                }
            }

            $writer->endElement();

            return $writer->outputMemory();
        });
    }

    public function renderPage(SiteProfileData $profile, string $source, int $page): ?string
    {
        if ($page < 1) {
            return null;
        }

        $definition = $this->registry->get($source);

        if ($definition === null) {
            return null;
        }

        return $this->cache->remember($profile, "sitemap:page:{$source}:{$page}", function () use ($definition, $page, $profile): ?string {
            $chunks = $this->chunksFor($definition, $profile);
            $entries = $chunks[$page - 1] ?? null;

            if ($entries === null) {
                return null;
            }

            $writer = $this->writer();
            $writer->startDocument('1.0', 'UTF-8');
            $writer->startElement('urlset');
            $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

            foreach ($entries as $entry) {
                $writer->startElement('url');
                $writer->writeElement('loc', $entry['loc']);

                if ($entry['lastmod'] !== null) {
                    $writer->writeElement('lastmod', $entry['lastmod']);
                }

                $writer->endElement();
            }

            $writer->endElement();

            return $writer->outputMemory();
        });
    }

    /** @return array<int, array<int, array{loc: string, lastmod: string|null}>> */
    private function chunksFor(SeoResourceDefinition $definition, SiteProfileData $profile): array
    {
        if (! $profile->defaultsFor($definition)->sitemap) {
            return [];
        }

        $size = max(1, (int) config('aura-seo.sitemap.chunk_size', 1000));

        return array_values(array_chunk($this->entriesFor($definition, $profile), $size));
    }

    /** @return array<int, array{loc: string, lastmod: string|null}> */
    private function entriesFor(SeoResourceDefinition $definition, SiteProfileData $profile): array
    {
        return $this->cache->remember($profile, "sitemap:entries:{$definition->key}", function () use ($definition, $profile): array {
            $entries = [];
            $query = $definition->publicQuery($profile);

            if ($query === null) {
                return [];
            }

            foreach ($query->cursor() as $resource) {
                if (! $resource instanceof Model
                    || ! $resource instanceof $definition->resourceClass
                    || ! $definition->isPublic($resource, $profile)) {
                    continue;
                }

                $metadata = $this->resolver->resolve($resource, $profile, $definition);

                if (! $metadata->isIndexable()
                    || $metadata->canonical === null
                    || ! $this->sharesOrigin($metadata->canonical, $profile->baseUrl)) {
                    continue;
                }

                $entries[] = [
                    'loc' => $metadata->canonical,
                    'lastmod' => $this->formatLastModified($definition->lastModifiedValue($resource) ?: $resource->getAttribute('updated_at')),
                ];
            }

            return $entries;
        });
    }

    /** @param array<int, array{loc: string, lastmod: string|null}> $entries */
    private function latestLastModified(array $entries): ?string
    {
        $values = array_filter(array_column($entries, 'lastmod'));
        sort($values);

        if ($values === []) {
            return null;
        }

        $latest = end($values);

        return $latest === false ? null : $latest;
    }

    private function formatLastModified(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->toAtomString();
        }

        if (is_numeric($value)) {
            return CarbonImmutable::createFromTimestamp((int) $value)->toAtomString();
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return CarbonImmutable::parse($value)->toAtomString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function publicUrl(SiteProfileData $profile, string $path): string
    {
        return $this->canonicals->normalize($path, $profile->baseUrl);
    }

    private function sharesOrigin(string $url, string $baseUrl): bool
    {
        $canonical = parse_url($url);
        $base = parse_url($baseUrl);

        if (! is_array($canonical) || ! is_array($base)) {
            return false;
        }

        $canonicalPort = (int) ($canonical['port'] ?? 0);
        $basePort = (int) ($base['port'] ?? 0);

        return strtolower((string) ($canonical['scheme'] ?? '')) === strtolower((string) ($base['scheme'] ?? ''))
            && strtolower((string) ($canonical['host'] ?? '')) === strtolower((string) ($base['host'] ?? ''))
            && $canonicalPort === $basePort;
    }

    private function writer(): XMLWriter
    {
        $writer = new XMLWriter;
        $writer->openMemory();

        return $writer;
    }
}
