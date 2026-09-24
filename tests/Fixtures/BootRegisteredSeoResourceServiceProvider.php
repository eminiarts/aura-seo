<?php

namespace Aura\Seo\Tests\Fixtures;

use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Services\SeoRegistry;
use Illuminate\Support\ServiceProvider;

class BootRegisteredSeoResourceServiceProvider extends ServiceProvider
{
    public function boot(SeoRegistry $registry): void
    {
        $registry->register(
            SeoResourceDefinition::make('boot-registered-articles', Article::class)->title('title'),
        );
    }
}
