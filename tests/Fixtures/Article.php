<?php

namespace Aura\Seo\Tests\Fixtures;

use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Seo\Fields\SeoFieldGroup;

class Article extends Resource
{
    public static ?string $slug = 'seo-test-article';

    public static string $type = 'SeoTestArticle';

    public static function getFields(): array
    {
        return array_merge([
            [
                'conditional_logic' => [],
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
                'validation' => 'required|max:255',
            ],
            [
                'conditional_logic' => [],
                'name' => 'Summary',
                'slug' => 'summary',
                'type' => Text::class,
                'validation' => 'nullable|max:255',
            ],
        ], SeoFieldGroup::make());
    }
}
