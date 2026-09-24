<?php

namespace Aura\Seo\Tests;

use Aura\Seo\Tests\Fixtures\BootRegisteredSeoResourceServiceProvider;

class BootRegisteredResourceTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BootRegisteredSeoResourceServiceProvider::class,
        ];
    }
}
