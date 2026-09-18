<?php

namespace Aura\Seo\Http\Controllers;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Services\SitemapGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class SitemapPageController extends Controller
{
    public function __invoke(Request $request, string $source, int $page, SitemapGenerator $sitemaps, SiteProfileResolver $profiles): Response
    {
        $profile = $profiles->resolve($request->getHost());
        abort_if($profile === null || ! $profile->enabled, 404);

        $xml = $sitemaps->renderPage($profile, $source, $page);
        abort_if($xml === null, 404);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
