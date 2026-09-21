<?php

namespace Aura\Seo\Data;

final readonly class SeoResourceDefaults
{
    public function __construct(
        public bool $enabled = true,
        public bool $sitemap = true,
        public ?string $titlePattern = null,
        public ?string $defaultDescription = null,
        public ?string $defaultSocialImage = null,
        public ?bool $index = null,
        public ?bool $follow = null,
    ) {}
}
