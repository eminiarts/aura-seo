<?php

use Aura\Seo\Support\CanonicalUrlNormalizer;

test('canonical URLs are absolute normalized deterministic and fragment free', function () {
    config()->set('aura-seo.canonical.trailing_slash', false);

    $url = app(CanonicalUrlNormalizer::class)->normalize(
        '/news//one/../two/?z=2&a=1#ignored',
        'HTTPS://Example.Test:443/base',
    );

    expect($url)->toBe('https://example.test/news/two?a=1&z=2');
});

test('external canonicals and unsafe schemes are denied by default', function () {
    expect(fn () => app(CanonicalUrlNormalizer::class)->normalize('https://other.test/post', 'https://example.test'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(CanonicalUrlNormalizer::class)->normalize('javascript:alert(1)', 'https://example.test'))
        ->toThrow(InvalidArgumentException::class);
});
