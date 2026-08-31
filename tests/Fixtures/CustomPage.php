<?php

namespace Aura\Seo\Tests\Fixtures;

use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Seo\Fields\SeoFieldGroup;

class CustomPage extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'seo-test-page';

    public static string $type = 'SeoTestPage';

    public static bool $usesMeta = false;

    protected $guarded = [];

    protected $table = 'seo_test_pages';

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
