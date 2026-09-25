<?php

namespace Aura\Seo\Ai;

use Aura\Seo\Data\AiMetadataSuggestion;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

use function Laravel\Ai\agent;

final readonly class AiMetadataGenerator
{
    public function generate(
        ?string $title,
        ?string $content,
        ?string $resourceType = null,
        ?string $siteName = null,
        ?string $locale = null,
        ?string $currentTitle = null,
        ?string $currentDescription = null,
    ): AiMetadataSuggestion {
        $title = trim((string) $title);
        $content = Str::limit(trim(strip_tags((string) $content)), 12000, '');

        if ($title === '' && $content === '') {
            throw new RuntimeException('Add a title or content before generating SEO metadata.');
        }

        $context = array_filter([
            $resourceType ? 'Content type: '.Str::headline($resourceType) : null,
            $siteName ? 'Site name: '.$siteName : null,
            $locale ? 'Locale: '.$locale : null,
            $currentTitle ? 'Current meta title: '.$currentTitle : null,
            $currentDescription ? 'Current meta description: '.$currentDescription : null,
            "Title: {$title}",
            "Content:\n{$content}",
        ]);

        $response = agent(
            instructions: <<<'PROMPT'
Generate search metadata for the supplied content and locale. Improve existing metadata when it is provided. Be accurate and specific.
PROMPT,
            schema: static fn (JsonSchema $schema): array => [
                'meta_title' => $schema->string()
                    ->description('A specific search title with at most 60 characters.')
                    ->max(60)
                    ->required(),
                'meta_description' => $schema->string()
                    ->description('An accurate search description with at most 160 characters.')
                    ->max(160)
                    ->required(),
            ],
        )->prompt(implode("\n\n", $context));

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('The AI provider did not return structured SEO metadata.');
        }

        $payload = $response->structured;
        $metaTitle = trim((string) ($payload['meta_title'] ?? ''));
        $metaDescription = trim((string) ($payload['meta_description'] ?? ''));

        if ($metaTitle === '' || $metaDescription === '') {
            throw new RuntimeException('The AI provider did not return usable SEO metadata.');
        }

        return new AiMetadataSuggestion(
            title: Str::limit($metaTitle, 60, ''),
            description: Str::limit($metaDescription, 160, ''),
        );
    }
}
