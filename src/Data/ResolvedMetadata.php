<?php

namespace Aura\Seo\Data;

final readonly class ResolvedMetadata
{
    /**
     * @param  array<string, string>  $alternates
     * @param  array<string, string>  $openGraph
     * @param  array<int, string>  $robots
     * @param  array<string, string>  $twitter
     */
    public function __construct(
        public ?string $canonical,
        public ?string $description,
        public array $openGraph,
        public array $robots,
        public ?string $title,
        public array $twitter,
        public array $alternates = [],
    ) {}

    public function isIndexable(): bool
    {
        return in_array('index', $this->robots, true) && ! in_array('noindex', $this->robots, true);
    }
}
