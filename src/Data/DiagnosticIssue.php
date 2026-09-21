<?php

namespace Aura\Seo\Data;

final readonly class DiagnosticIssue
{
    public function __construct(
        public string $level,
        public string $message,
        public ?string $source = null,
        public ?string $record = null,
        public ?string $canonical = null,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {}

    public function isError(): bool
    {
        return $this->level === 'error';
    }
}
