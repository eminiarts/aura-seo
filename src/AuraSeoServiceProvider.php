<?php

namespace Aura\Seo;

use Aura\Base\Aura;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Resources\SiteProfile;
use Aura\Seo\Services\ConfiguredSiteProfileResolver;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Support\CanonicalUrlNormalizer;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuraSeoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('aura-seo')
            ->hasConfigFile()
            ->hasViews('aura-seo');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CanonicalUrlNormalizer::class);
        $this->app->singleton(SeoRegistry::class);
        $this->app->singleton(SiteProfileResolver::class, ConfiguredSiteProfileResolver::class);

        $this->callAfterResolving(Aura::class, function (Aura $aura): void {
            $aura->registerResources([SiteProfile::class]);
        });
    }

    public function packageBooted(): void
    {
        $this->app->booted(function (): void {
            $registry = $this->app->make(SeoRegistry::class);
            $configured = config('aura-seo.resources', []);
            $configured = is_callable($configured) ? $this->app->call($configured) : $configured;

            foreach ((array) $configured as $definition) {
                if (is_callable($definition) && ! $definition instanceof SeoResourceDefinition) {
                    $definition = $this->app->call($definition);
                }

                if ($definition instanceof SeoResourceDefinition) {
                    $registry->register($definition);
                }
            }
        });
    }
}
