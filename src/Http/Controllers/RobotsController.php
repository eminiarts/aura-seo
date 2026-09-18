<?php

namespace Aura\Seo\Http\Controllers;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Services\RobotsTxtGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class RobotsController extends Controller
{
    public function __invoke(Request $request, RobotsTxtGenerator $robots, SiteProfileResolver $profiles): Response
    {
        $profile = $profiles->resolve($request->getHost());

        return response($robots->render($profile), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
