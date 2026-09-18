<?php

namespace Aura\Seo\Data;

final readonly class AiMetadataSuggestion
{
    public function __construct(
        public string $title,
        public string $description,
    ) {}

    /** @return array{meta_title: string, meta_description: string} */
    public function toArray(): array
    {
        return [
            'meta_title' => $this->title,
            'meta_description' => $this->description,
        ];
    }
}
