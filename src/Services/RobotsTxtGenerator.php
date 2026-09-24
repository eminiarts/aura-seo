<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Support\CanonicalUrlNormalizer;

final readonly class RobotsTxtGenerator
{
    public function __construct(private CanonicalUrlNormalizer $canonicals, private SeoCache $cache) {}

    public function render(?SiteProfileData $profile): string
    {
        if ($profile === null || ! $profile->enabled) {
            return $this->denyAll();
        }

        return $this->cache->remember($profile, 'robots', function () use ($profile): string {
            $rules = $this->sanitizeRules($profile->robotsRules, $profile->index);
            $lines = ['User-agent: *', $profile->index ? 'Allow: /' : 'Disallow: /'];

            foreach ($rules as $rule) {
                if ($profile->index || ! str_starts_with($rule, 'Allow:')) {
                    $lines[] = $rule;
                }
            }

            if (config('aura-seo.routes.sitemap', true) && $profile->sitemapEnabled) {
                $lines[] = 'Sitemap: '.$this->canonicals->normalize('/sitemap.xml', $profile->baseUrl);
            }

            return implode(PHP_EOL, array_values(array_unique($lines))).PHP_EOL;
        });
    }

    private function denyAll(): string
    {
        return "User-agent: *\nDisallow: /\n";
    }

    /** @return array<int, string> */
    private function sanitizeRules(?string $rules, bool $indexable): array
    {
        if ($rules === null || trim($rules) === '') {
            return [];
        }

        $sanitized = [];

        foreach (preg_split('/\r\n|\r|\n/', $rules) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#')) {
                $sanitized[] = $line;

                continue;
            }

            if (preg_match('/^(Allow|Disallow)\s*:\s*(.+)$/i', $line, $matches)) {
                $directive = ucfirst(strtolower($matches[1]));

                if ($indexable || $directive === 'Disallow') {
                    $sanitized[] = $directive.': '.trim($matches[2]);
                }

                continue;
            }

            if (preg_match('/^Crawl-delay\s*:\s*(\d+)$/i', $line, $matches)) {
                $sanitized[] = 'Crawl-delay: '.$matches[1];
            }
        }

        return $sanitized;
    }
}
