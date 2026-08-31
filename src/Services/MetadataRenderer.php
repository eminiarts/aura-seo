<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\ResolvedMetadata;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\HtmlString;

final readonly class MetadataRenderer
{
    public function __construct(private Factory $views) {}

    public function render(ResolvedMetadata $metadata): HtmlString
    {
        return new HtmlString($this->views->make('aura-seo::components.meta', compact('metadata'))->render());
    }
}
