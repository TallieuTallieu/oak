<?php

use Oak\Config\Repository;

test('get returns a stored value', function () {
    $repository = new Repository(['app' => ['name' => 'oak']]);

    expect($repository->get('app'))->toBe(['name' => 'oak']);
});

test('get resolves dot notation', function () {
    $repository = new Repository(['app' => ['name' => 'oak']]);

    expect($repository->get('app.name'))->toBe('oak');
});

test('get returns the default for a missing key', function () {
    $repository = new Repository([]);

    expect($repository->get('missing', 'fallback'))->toBe('fallback');
    expect($repository->get('missing.nested', 'fallback'))->toBe('fallback');
});

test('get returns the default when dot notation hits a non-array', function () {
    $repository = new Repository(['app' => 'scalar']);

    expect($repository->get('app.name', 'fallback'))->toBe('fallback');
});

test('set stores a value', function () {
    $repository = new Repository();
    $repository->set('key', 'value');

    expect($repository->get('key'))->toBe('value');
    expect($repository->has('key'))->toBeTrue();
});
