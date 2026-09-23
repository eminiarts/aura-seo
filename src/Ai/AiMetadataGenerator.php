<?php

namespace Aura\Seo\Ai;

use Aura\Base\Contracts\AiConnector;
use Aura\Seo\Data\AiMetadataSuggestion;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class AiMetadataGenerator
{
    public function __construct(private AiConnector $ai) {}

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

        $response = $this->ai->generate(
            prompt: implode("\n\n", $context),
            systemPrompt: <<<'PROMPT'
Generate search metadata for the supplied content and locale. Improve existing metadata when it is provided. Return only a JSON object with exactly two string keys: "meta_title" and "meta_description". Keep meta_title at 60 characters or fewer and meta_description at 160 characters or fewer. Be accurate and specific.
PROMPT,
        );

        $payload = $this->decode($response);
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

    /** @return array<string, mixed> */
    private function decode(string $response): array
    {
        $response = trim($response);
        $response = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $response) ?? $response;
        $payload = json_decode($response, true);

        if (! is_array($payload)) {
            throw new RuntimeException('The AI provider returned invalid metadata JSON.');
        }

        return $payload;
    }
}
