<?php

namespace Aura\Seo\Livewire;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\DiagnosticIssue;
use Aura\Seo\Services\SeoDiagnostics;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SeoDiagnosticsPanel extends Component
{
    public ?string $checkedAt = null;

    public bool $hasRun = false;

    /** @var list<array{level: string, message: string, source: ?string, actionLabel: ?string, actionUrl: ?string}> */
    public array $issues = [];

    public ?string $status = null;

    public bool $successful = false;

    public function render()
    {
        return view('aura-seo::livewire.seo-diagnostics-panel');
    }

    public function runChecks(SiteProfileResolver $profiles, SeoDiagnostics $diagnostics): void
    {
        abort_unless(Gate::allows('aura-seo.diagnose'), 403);

        $teamId = config('aura.teams') ? data_get(auth()->user(), 'current_team_id') : null;
        $profile = $profiles->resolveForTeam(is_numeric($teamId) ? (int) $teamId : null);

        $this->hasRun = true;
        $this->checkedAt = now()->format('H:i');

        if ($profile === null) {
            $this->issues = [];
            $this->status = 'Enable SEO and save a valid site URL before running checks.';
            $this->successful = false;

            return;
        }

        $this->issues = array_map(
            static fn (DiagnosticIssue $issue): array => [
                'level' => $issue->level,
                'message' => $issue->message,
                'source' => $issue->source,
                'actionLabel' => $issue->actionLabel,
                'actionUrl' => $issue->actionUrl,
            ],
            $diagnostics->scan($profile),
        );
        $this->status = $this->issues === [] ? 'No SEO issues were found.' : null;
        $this->successful = $this->issues === [];
    }
}
