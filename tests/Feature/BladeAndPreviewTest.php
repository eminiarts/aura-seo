<?php

use Aura\Seo\Data\ResolvedMetadata;
use Aura\Seo\Services\MetadataRenderer;
use Aura\Seo\Services\PreviewFactory;

test('the Blade renderer emits complete metadata without controlling the host layout', function () {
    $metadata = new ResolvedMetadata(
        canonical: 'https://example.test/post',
        description: 'Description',
        openGraph: [
            'og:title' => 'OG title',
            'og:description' => 'OG description',
            'og:image' => 'https://example.test/image.jpg',
            'og:url' => 'https://example.test/post',
        ],
        robots: ['index', 'follow'],
        title: 'Page title',
        twitter: [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => 'Twitter title',
        ],
        alternates: ['de' => 'https://example.test/de/post'],
    );

    $html = app(MetadataRenderer::class)->render($metadata)->toHtml();

    expect($html)->toContain('<title>Page title</title>')
        ->toContain('rel="canonical" href="https://example.test/post"')
        ->toContain('name="robots" content="index, follow"')
        ->toContain('property="og:image" content="https://example.test/image.jpg"')
        ->toContain('name="twitter:card" content="summary_large_image"')
        ->toContain('hreflang="de" href="https://example.test/de/post"')
        ->not->toContain('<html')
        ->not->toContain('<head');
});

test('search and social previews reflect the same resolved and inherited metadata', function () {
    $metadata = new ResolvedMetadata(
        canonical: 'https://example.test/inherited',
        description: 'Inherited description',
        openGraph: [
            'og:title' => 'Social override',
            'og:image' => 'https://example.test/default.jpg',
        ],
        robots: ['index', 'follow'],
        title: 'Inherited title',
        twitter: [],
    );

    $preview = app(PreviewFactory::class)->make($metadata);
    $html = view('aura-seo::components.preview', compact('metadata'))->render();

    expect($preview->searchTitle)->toBe('Inherited title')
        ->and($preview->searchDescription)->toBe('Inherited description')
        ->and($preview->socialTitle)->toBe('Social override')
        ->and($preview->socialDescription)->toBe('Inherited description')
        ->and($html)->toContain('Search preview')
        ->toContain('Social preview')
        ->toContain('Inherited title')
        ->toContain('Social override')
        ->toContain('Preview only.');
});

test('previews escape content and omit absent values', function () {
    $metadata = new ResolvedMetadata(
        canonical: null,
        description: '<script>alert(1)</script>',
        openGraph: [],
        robots: ['noindex', 'nofollow'],
        title: null,
        twitter: [],
    );

    $html = view('aura-seo::components.preview', compact('metadata'))->render();

    expect($html)->not->toContain('<script>')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<img');
});
