<?php

use Oak\Container\Container;

class ContainerTestService
{
    //
}

class ContainerTestDependent
{
    public function __construct(public ContainerTestService $service) {}
}

test('set and get resolves an implementation', function () {
    $container = new Container();
    $container->set(ContainerTestService::class, ContainerTestService::class);

    expect($container->has(ContainerTestService::class))->toBeTrue();
    expect($container->get(ContainerTestService::class))->toBeInstanceOf(
        ContainerTestService::class,
    );
});

test('set resolves a fresh instance on every get', function () {
    $container = new Container();
    $container->set(ContainerTestService::class, ContainerTestService::class);

    expect($container->get(ContainerTestService::class))->not->toBe(
        $container->get(ContainerTestService::class),
    );
});

test('singleton resolves the same instance on every get', function () {
    $container = new Container();
    $container->singleton(
        ContainerTestService::class,
        ContainerTestService::class,
    );

    expect($container->get(ContainerTestService::class))->toBe(
        $container->get(ContainerTestService::class),
    );
});

test('instance returns the stored instance', function () {
    $container = new Container();
    $service = new ContainerTestService();
    $container->instance(ContainerTestService::class, $service);

    expect($container->get(ContainerTestService::class))->toBe($service);
});

test('constructor dependencies are autowired', function () {
    $container = new Container();
    $container->set(ContainerTestService::class, ContainerTestService::class);
    $container->set(
        ContainerTestDependent::class,
        ContainerTestDependent::class,
    );

    $dependent = $container->get(ContainerTestDependent::class);

    expect($dependent)->toBeInstanceOf(ContainerTestDependent::class);
    assert($dependent instanceof ContainerTestDependent);
    expect($dependent->service)->toBeInstanceOf(ContainerTestService::class);
});

test('a callable implementation receives the container', function () {
    $container = new Container();
    $container->set(ContainerTestService::class, function ($app) use (
        &$received,
    ) {
        $received = $app;
        return new ContainerTestService();
    });

    expect($container->get(ContainerTestService::class))->toBeInstanceOf(
        ContainerTestService::class,
    );
    expect($received)->toBe($container);
});

test('getting an unknown contract throws', function () {
    $container = new Container();

    expect(fn() => $container->get('some-unknown-contract'))->toThrow(
        Exception::class,
    );
});
