<?php

namespace Aura\Seo\Data;

final readonly class PreviewData
{
    public function __construct(
        public ?string $searchDescription,
        public ?string $searchTitle,
        public ?string $searchUrl,
        public ?string $socialDescription,
        public ?string $socialImage,
        public ?string $socialTitle,
    ) {}
}
