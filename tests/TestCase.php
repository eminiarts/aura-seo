<?php

namespace Aura\Seo\Tests;

use Aura\Base\AuraServiceProvider;
use Aura\Base\Facades\Aura;
use Aura\Base\Providers\AuthServiceProvider;
use Aura\Seo\AuraSeoServiceProvider;
use Aura\Seo\Tests\Fixtures\Article;
use Aura\Seo\Tests\Fixtures\CustomPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Laravel\ServiceProvider as ImageServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Laravel\Ai\AiServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use InteractsWithViews;

    protected bool $teamsEnabled = false;

    protected function defineEnvironment($app): void
    {
        $this->useIsolatedFilesystemPaths($app);
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('s', 32)));
        $app['config']->set('aura.teams', $this->teamsEnabled);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('queue.default', 'sync');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->defineEnvironment($app);
        (require __DIR__.'/../vendor/eminiarts/aura-cms/database/migrations/create_aura_tables.php.stub')->up();

        Schema::create('seo_test_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('summary')->nullable();
            $table->string('seo_meta_title')->nullable();
            $table->string('seo_meta_description')->nullable();
            $table->string('seo_canonical_url')->nullable();
            $table->boolean('seo_index')->nullable();
            $table->boolean('seo_follow')->nullable();
            if ($this->teamsEnabled) {
                $table->unsignedBigInteger('team_id')->nullable()->index();
            }
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FortifyServiceProvider::class,
            AiServiceProvider::class,
            SanctumServiceProvider::class,
            AuthServiceProvider::class,
            AuraServiceProvider::class,
            AuraSeoServiceProvider::class,
            ImpersonateServiceProvider::class,
            ImageServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Factory::guessFactoryNamesUsing(fn (string $modelName) => 'Aura\\Base\\Database\\Factories\\'.class_basename($modelName).'Factory');
        Aura::registerResources([Article::class, CustomPage::class]);
    }

    private function useIsolatedFilesystemPaths($app): void
    {
        $basePath = sys_get_temp_dir().'/aura-seo-testbench-'.getmypid().($this->teamsEnabled ? '-teams' : '-single');

        foreach (['app/Aura/Resources', 'bootstrap/cache', 'config', 'database/migrations', 'public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/testing', 'storage/framework/views', 'storage/logs'] as $path) {
            if (! is_dir($basePath.'/'.$path)) {
                @mkdir($basePath.'/'.$path, 0755, true);
            }
        }

        $app->useAppPath($basePath.'/app');
        (function (): void {
            $this->namespace = 'App\\';
        })->call($app);
        $app->useBootstrapPath($basePath.'/bootstrap');
        $app->useConfigPath($basePath.'/config');
        $app->useDatabasePath($basePath.'/database');
        $app->usePublicPath($basePath.'/public');
        $app->useStoragePath($basePath.'/storage');
    }
}
