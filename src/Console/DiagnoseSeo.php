<?php

namespace Aura\Seo\Console;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\DiagnosticIssue;
use Aura\Seo\Services\SeoDiagnostics;
use Illuminate\Console\Command;

class DiagnoseSeo extends Command
{
    protected $signature = 'aura-seo:diagnose {--host= : Public hostname configured in Aura SEO settings}';

    protected $description = 'Scan the active Aura SEO settings profile for metadata and sitemap issues.';

    public function handle(SeoDiagnostics $diagnostics, SiteProfileResolver $profiles): int
    {
        $host = trim((string) ($this->option('host') ?: ($profiles->hosts()[0] ?? '')));

        if ($host === '') {
            $this->components->error('No enabled Aura SEO settings profile is configured.');

            return self::FAILURE;
        }

        $profile = $profiles->resolve($host);

        if (! $profile) {
            $this->components->error("No enabled Aura SEO settings profile matches [{$host}].");

            return self::FAILURE;
        }

        $issues = $diagnostics->scan($profile);
        $errors = count(array_filter($issues, fn (DiagnosticIssue $issue): bool => $issue->isError()));
        $warnings = count($issues) - $errors;

        if ($issues === []) {
            $this->components->info("Aura SEO diagnostics are clean for [{$host}].");

            return self::SUCCESS;
        }

        $this->components->warn("Aura SEO diagnostics for [{$host}] reported {$errors} error(s) and {$warnings} warning(s).");

        foreach ($issues as $issue) {
            $record = $issue->record === null ? '' : " {$issue->record}";
            $canonical = $issue->canonical === null ? '' : " [{$issue->canonical}]";
            $this->line(strtoupper($issue->level).": {$issue->message}{$record}{$canonical}");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
