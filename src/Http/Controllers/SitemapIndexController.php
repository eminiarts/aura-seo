<?php

namespace Aura\Seo\Http\Controllers;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Services\SitemapGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class SitemapIndexController extends Controller
{
    public function __invoke(Request $request, SitemapGenerator $sitemaps, SiteProfileResolver $profiles): Response
    {
        $profile = $profiles->resolve($request->getHost())?->toSeoData();
        abort_if($profile === null || ! $profile->enabled, 404);

        return response($sitemaps->renderIndex($profile), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
