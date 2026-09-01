<?php

namespace Aura\Seo\Http\Controllers;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\DiagnosticIssue;
use Aura\Seo\Services\SeoDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DiagnosticsController extends Controller
{
    public function __invoke(Request $request, SeoDiagnostics $diagnostics, SiteProfileResolver $profiles): View
    {
        abort_unless(Gate::any(['aura-seo.manage', 'aura-seo.diagnose']), 403);

        $host = trim((string) ($request->query('host') ?: $request->getHost()));
        $profile = $host === '' ? null : $profiles->resolve($host);
        $issues = $profile?->toSeoData() ? $diagnostics->scan($profile->toSeoData()) : [];

        if ($profile === null) {
            $issues = [
                new DiagnosticIssue(
                    level: 'error',
                    message: "No enabled Aura SEO SiteProfile is mapped to [{$host}].",
                ),
            ];
        }

        return view('aura-seo::diagnostics.index', [
            'availableHosts' => array_keys((array) config('aura-seo.sites', [])),
            'host' => $host,
            'issues' => $issues,
            'profile' => $profile,
        ]);
    }
}
