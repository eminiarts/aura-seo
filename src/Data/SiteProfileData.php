<?php

namespace Aura\Seo\Data;

final readonly class SiteProfileData
{
    public function __construct(
        public string $baseUrl,
        public bool $enabled,
        public bool $follow,
        public string $hostname,
        public ?int $id = null,
        public bool $index = false,
        public string $locale = 'en',
        public ?string $defaultDescription = null,
        public ?string $defaultSocialImage = null,
        public ?string $name = null,
        public ?string $robotsRules = null,
        public ?int $teamId = null,
        public string $titleTemplate = '%s',
    ) {}
}
