<?php

namespace Aura\Seo\Data;

final readonly class SiteProfileData
{
    public function __construct(
        public string $baseUrl,
        public bool $enabled,
        public bool $follow,
        public string $hostname,
        public bool $index = false,
        public string $locale = 'en',
        public ?string $defaultDescription = null,
        public ?string $defaultSocialImage = null,
        public ?string $defaultOpenGraphImage = null,
        public ?string $defaultTwitterImage = null,
        public ?string $name = null,
        public ?string $robotsRules = null,
        public string $separator = '|',
        public ?int $teamId = null,
        public string $titleTemplate = '[Post Title] [Separator] [Site Name]',
    ) {}

    public function __get(string $name): mixed
    {
        return match ($name) {
            'team_id' => $this->teamId,
            'title' => $this->name,
            default => null,
        };
    }
}
