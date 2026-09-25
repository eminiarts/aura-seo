<?php

namespace Aura\Seo\Tests\Fixtures;

use Aura\Base\Fields\Text;
use Aura\Base\Fields\View;
use Aura\Base\Resource;

class AiArticle extends Resource
{
    public static ?string $slug = 'seo-test-ai-article';

    public static string $type = 'SeoTestAiArticle';

    public static function getFields(): array
    {
        return [
            [
                'conditional_logic' => [],
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
                'validation' => 'required|max:255',
            ],
            [
                'conditional_logic' => [],
                'name' => 'AI suggestion',
                'on_view' => false,
                'seo_prefix' => 'seo',
                'slug' => 'seo_ai_prefill',
                'type' => View::class,
                'validation' => '',
                'view' => 'aura-seo::fields.ai-prefill',
            ],
        ];
    }
}
