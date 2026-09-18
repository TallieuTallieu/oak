<?php

use Oak\Cookie\Cookie;

test('the default SameSite is Lax', function () {
    $cookie = new Cookie('/', false, true);

    expect($cookie->getSameSite())->toBe('Lax');
});

test(
    'a SameSite value is normalized to the casing browsers expect',
    function () {
        expect(new Cookie('/', false, true, 'strict')->getSameSite())->toBe(
            'Strict',
        );
        expect(new Cookie('/', true, true, 'none')->getSameSite())->toBe(
            'None',
        );
    },
);

test('an unknown SameSite value is refused', function () {
    expect(fn() => new Cookie('/', false, true, 'whenever'))->toThrow(
        InvalidArgumentException::class,
    );
});

test('SameSite None on an insecure cookie is refused', function () {
    expect(fn() => new Cookie('/', false, true, 'None'))->toThrow(
        InvalidArgumentException::class,
    );
});

test('set stores the JSON encoded value', function () {
    $cookie = new Cookie('/', false, true);

    $cookie->set('cart', ['items' => 2]);

    expect($_COOKIE['cart'])->toBe('{"items":2}');
    expect($cookie->has('cart'))->toBeTrue();
    expect($cookie->get('cart'))->toEqual((object) ['items' => 2]);

    unset($_COOKIE['cart']);
});

test('a value that cannot be JSON encoded is refused', function () {
    $cookie = new Cookie('/', false, true);

    expect(fn() => $cookie->set('broken', NAN))->toThrow(
        InvalidArgumentException::class,
    );
});

test('delete forgets the cookie', function () {
    $cookie = new Cookie('/', false, true);
    $_COOKIE['cart'] = '{"items":2}';

    $cookie->delete('cart');

    expect($cookie->has('cart'))->toBeFalse();
    expect($_COOKIE)->not->toHaveKey('cart');
});
