<?php

namespace Aura\Seo\Ai;

use Aura\Base\Contracts\AiConnector;
use Aura\Seo\Data\AiMetadataSuggestion;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class AiMetadataGenerator
{
    public function __construct(private AiConnector $ai) {}

    public function generate(?string $title, ?string $content): AiMetadataSuggestion
    {
        $title = trim((string) $title);
        $content = Str::limit(trim(strip_tags((string) $content)), 12000, '');

        if ($title === '' && $content === '') {
            throw new RuntimeException('Add a title or content before generating SEO metadata.');
        }

        $response = $this->ai->generate(
            prompt: "Title: {$title}\n\nContent:\n{$content}",
            systemPrompt: <<<'PROMPT'
Generate search metadata for the supplied content. Return only a JSON object with exactly two string keys: "meta_title" and "meta_description". Keep meta_title at 60 characters or fewer and meta_description at 160 characters or fewer. Be accurate, specific, and avoid quotation marks around the JSON response.
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
