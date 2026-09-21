<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\ResolvedMetadata;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Data\SiteProfileData;
use Aura\Seo\Support\CanonicalUrlNormalizer;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class MetadataResolver
{
    public function __construct(
        private CanonicalUrlNormalizer $canonicals,
        private SeoRegistry $registry,
        private TitlePatternRenderer $titles,
    ) {}

    public function resolve(Model $resource, SiteProfileData $profile, ?SeoResourceDefinition $definition = null): ResolvedMetadata
    {
        $definition ??= $this->registry->findFor($resource);
        $resourceDefaults = $definition ? $profile->defaultsFor($definition) : null;
        $public = $profile->enabled
            && $resourceDefaults?->enabled === true
            && $definition?->hasUrlResolver()
            && $definition->isPublic($resource, $profile);

        $explicitTitle = $this->firstString($this->field($resource, $definition, 'meta_title'));
        $title = $explicitTitle ?? $this->firstString(
            $definition?->mappedTitle($resource, $profile),
            $profile->name,
            config('aura-seo.fallbacks.title'),
        );

        if ($explicitTitle === null) {
            $title = $this->applyTitleTemplate(
                $title,
                $resourceDefaults?->titlePattern ?? $profile->titleTemplate,
                $profile,
            );
        }

        $description = $this->firstString(
            $this->field($resource, $definition, 'meta_description'),
            $definition?->mappedDescription($resource, $profile),
            $resourceDefaults?->defaultDescription,
            $profile->defaultDescription,
            config('aura-seo.fallbacks.description'),
        );

        $canonical = $public ? $this->canonical($resource, $profile, $definition) : null;
        $index = $public && $this->firstBool(
            $this->field($resource, $definition, 'index'),
            $resourceDefaults?->index,
            $profile->index,
            config('aura-seo.fallbacks.index', false),
        );
        $follow = $public && $this->firstBool(
            $this->field($resource, $definition, 'follow'),
            $resourceDefaults?->follow,
            $profile->follow,
            config('aura-seo.fallbacks.follow', false),
        );

        $image = $this->firstImage(
            $this->field($resource, $definition, 'og_image'),
            $definition?->mappedImage($resource, $profile),
            $resourceDefaults?->defaultSocialImage,
            $profile->defaultOpenGraphImage,
            $profile->defaultSocialImage,
            config('aura-seo.fallbacks.image'),
        );

        $openGraph = $this->withoutEmpty([
            'og:title' => $this->firstString($this->field($resource, $definition, 'og_title'), $title),
            'og:description' => $this->firstString($this->field($resource, $definition, 'og_description'), $description),
            'og:image' => $image,
            'og:url' => $canonical,
            'og:locale' => $profile->locale,
            'og:type' => 'website',
        ]);

        $twitterImage = $this->firstImage(
            $this->field($resource, $definition, 'twitter_image'),
            $profile->defaultTwitterImage,
            $image,
        );
        $twitter = $this->withoutEmpty([
            'twitter:card' => $this->firstString($this->field($resource, $definition, 'twitter_card'), $twitterImage ? 'summary_large_image' : 'summary'),
            'twitter:title' => $this->firstString($this->field($resource, $definition, 'twitter_title'), $title),
            'twitter:description' => $this->firstString($this->field($resource, $definition, 'twitter_description'), $description),
            'twitter:image' => $twitterImage,
        ]);

        return new ResolvedMetadata(
            canonical: $canonical,
            description: $description,
            openGraph: $openGraph,
            robots: [$index ? 'index' : 'noindex', $follow ? 'follow' : 'nofollow'],
            title: $title,
            twitter: $twitter,
            alternates: $public ? $this->alternates($resource, $profile, $definition) : [],
        );
    }

    private function alternates(Model $resource, SiteProfileData $profile, ?SeoResourceDefinition $definition): array
    {
        $alternates = [];

        foreach ($definition?->alternateUrls($resource, $profile) ?? [] as $locale => $url) {
            try {
                $alternates[(string) $locale] = $this->canonicals->normalize(
                    (string) $url,
                    $profile->baseUrl,
                    $definition?->externalCanonicalAllowed() || config('aura-seo.canonical.allow_external', false),
                );
            } catch (InvalidArgumentException) {
                // Invalid optional alternates are omitted and reported by diagnostics.
            }
        }

        return $alternates;
    }

    private function applyTitleTemplate(?string $title, string $pattern, SiteProfileData $profile): ?string
    {
        if ($title === null) {
            return null;
        }

        $template = trim($pattern);

        if ($template === '') {
            return $title;
        }

        return $this->titles->render($template, $title, $profile->separator, $profile->name);
    }

    private function canonical(Model $resource, SiteProfileData $profile, ?SeoResourceDefinition $definition): ?string
    {
        $value = $this->firstString(
            $this->field($resource, $definition, 'canonical_url'),
            $definition?->publicUrl($resource, $profile),
        );

        if ($value === null) {
            return null;
        }

        try {
            return $this->canonicals->normalize(
                $value,
                $profile->baseUrl,
                $definition?->externalCanonicalAllowed() || config('aura-seo.canonical.allow_external', false),
            );
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function field(Model $resource, ?SeoResourceDefinition $definition, string $name): mixed
    {
        if ($definition === null) {
            return null;
        }

        $slug = $definition->field($name);

        if (method_exists($resource, 'getFieldsAttribute')) {
            return data_get($resource->getFieldsAttribute(), $slug);
        }

        return data_get($resource, $slug);
    }

    private function firstBool(mixed ...$values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return filter_var($value, FILTER_VALIDATE_BOOL);
            }
        }

        return false;
    }

    private function firstImage(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                $value = $value['url'] ?? reset($value);
            }

            $value = $this->string($value);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function firstString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $value = $this->string($value);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function string(mixed $value): ?string
    {
        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @param array<string, string|null> $values */
    private function withoutEmpty(array $values): array
    {
        return array_filter($values, fn (?string $value): bool => $value !== null && $value !== '');
    }
}
