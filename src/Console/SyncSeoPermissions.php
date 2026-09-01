<?php

namespace Aura\Seo\Console;

use Aura\Seo\Services\SeoPermissionRegistrar;
use Illuminate\Console\Command;

class SyncSeoPermissions extends Command
{
    protected $signature = 'aura-seo:sync-permissions {--team= : Limit synchronization to one Team id}';

    protected $description = 'Register Aura SEO permissions in the Aura role editor.';

    public function handle(SeoPermissionRegistrar $permissions): int
    {
        $team = $this->option('team');
        $created = $permissions->synchronize(is_numeric($team) ? (int) $team : null);

        $this->components->info("Aura SEO permissions synchronized ({$created} created).");

        return self::SUCCESS;
    }
}
