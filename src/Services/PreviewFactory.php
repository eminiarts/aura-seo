<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\PreviewData;
use Aura\Seo\Data\ResolvedMetadata;

final class PreviewFactory
{
    public function make(ResolvedMetadata $metadata): PreviewData
    {
        return new PreviewData(
            searchDescription: $metadata->description,
            searchTitle: $metadata->title,
            searchUrl: $metadata->canonical,
            socialDescription: $metadata->openGraph['og:description'] ?? $metadata->description,
            socialImage: $metadata->openGraph['og:image'] ?? null,
            socialTitle: $metadata->openGraph['og:title'] ?? $metadata->title,
        );
    }
}
