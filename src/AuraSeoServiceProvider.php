<?php

namespace Aura\Seo;

use Aura\Base\Aura;
use Aura\Base\Resources\Team;
use Aura\Seo\Ai\AiMetadataGenerator;
use Aura\Seo\Console\DiagnoseSeo;
use Aura\Seo\Console\SyncSeoPermissions;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SeoResourceDefinition;
use Aura\Seo\Services\ConfiguredSiteProfileResolver;
use Aura\Seo\Services\PreviewSiteProfileResolver;
use Aura\Seo\Services\RobotsTxtGenerator;
use Aura\Seo\Services\SeoCache;
use Aura\Seo\Services\SeoCacheInvalidationRegistrar;
use Aura\Seo\Services\SeoDefaults;
use Aura\Seo\Services\SeoDiagnostics;
use Aura\Seo\Services\SeoPermissionRegistrar;
use Aura\Seo\Services\SeoRegistry;
use Aura\Seo\Services\SitemapGenerator;
use Aura\Seo\Services\SitemapRegistry;
use Aura\Seo\Services\TitlePatternRenderer;
use Aura\Seo\Settings\SeoSettingsPage;
use Aura\Seo\Support\CanonicalUrlNormalizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuraSeoServiceProvider extends PackageServiceProvider
{
    private bool $configuredResourcesRegistered = false;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('aura-seo')
            ->hasConfigFile()
            ->hasViews('aura-seo')
            ->hasRoutes('web')
            ->hasCommand(DiagnoseSeo::class)
            ->hasCommand(SyncSeoPermissions::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CanonicalUrlNormalizer::class);
        $this->app->singleton(AiMetadataGenerator::class);
        $this->app->singleton(PreviewSiteProfileResolver::class);
        $this->app->singleton(RobotsTxtGenerator::class);
        $this->app->singleton(SeoCache::class);
        $this->app->singleton(SeoCacheInvalidationRegistrar::class);
        $this->app->singleton(SeoDiagnostics::class);
        $this->app->singleton(SeoDefaults::class);
        $this->app->singleton(SeoPermissionRegistrar::class);
        $this->app->singleton(SeoRegistry::class);
        $this->app->singleton(SitemapGenerator::class);
        $this->app->singleton(SitemapRegistry::class);
        $this->app->singleton(TitlePatternRenderer::class);
        $this->app->singleton(SiteProfileResolver::class, ConfiguredSiteProfileResolver::class);

        $this->callAfterResolving(Aura::class, function (Aura $aura): void {
            $this->registerConfiguredResources();
            $aura->registerSettingsPages('eminiarts/aura-seo', [
                SeoSettingsPage::make($this->app->make(SeoRegistry::class)),
            ]);
        });
    }

    public function packageBooted(): void
    {
        $this->registerConfiguredResources();
        $this->registerGates();
        $this->registerPermissionCatalog();

        $this->app->booted(function (): void {
            $this->app->make(SeoCacheInvalidationRegistrar::class)->register();
        });
    }

    private function hasAccess(Authenticatable $user, string $permission): bool
    {
        if ((method_exists($user, 'isAuraGlobalAdmin') && $user->isAuraGlobalAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        return $permission !== ''
            && method_exists($user, 'hasPermission')
            && $user->hasPermission($permission);
    }

    private function registerConfiguredResources(): void
    {
        if ($this->configuredResourcesRegistered) {
            return;
        }

        $this->configuredResourcesRegistered = true;
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
    }

    private function registerGates(): void
    {
        foreach ([
            'aura-seo.manage' => 'manage',
            'aura-seo.diagnose' => 'diagnose',
        ] as $ability => $permissionKey) {
            Gate::define($ability, fn (Authenticatable $user): bool => $this->hasAccess(
                $user,
                (string) config("aura-seo.permissions.{$permissionKey}"),
            ));
        }
    }

    private function registerPermissionCatalog(): void
    {
        $registrar = $this->app->make(SeoPermissionRegistrar::class);
        $registrar->synchronizeOnce();

        if (config('aura.teams') && class_exists(Team::class)) {
            Team::created(function (Team $team) use ($registrar): void {
                $registrar->synchronize((int) $team->getKey());
            });
        }
    }
}
