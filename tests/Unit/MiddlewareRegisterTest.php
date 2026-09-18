<?php

use Oak\Http\Middleware\MiddlewareRegisterTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareRegisterTestMiddlewareA implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        return $handler->handle($request);
    }
}

class MiddlewareRegisterTestMiddlewareB extends
    MiddlewareRegisterTestMiddlewareA {}

class MiddlewareRegisterTestRegister
{
    use MiddlewareRegisterTrait;
}

test('middleware group can be registered and read back', function () {
    $register = new MiddlewareRegisterTestRegister();
    $register->middleware('web', [MiddlewareRegisterTestMiddlewareA::class]);

    expect($register->getMiddleware('web'))->toBe([
        MiddlewareRegisterTestMiddlewareA::class,
    ]);
});

test('registering the same group again merges the middleware', function () {
    $register = new MiddlewareRegisterTestRegister();
    $register->middleware('web', [MiddlewareRegisterTestMiddlewareA::class]);
    $register->middleware('web', [MiddlewareRegisterTestMiddlewareB::class]);

    expect($register->getMiddleware('web'))->toBe([
        MiddlewareRegisterTestMiddlewareA::class,
        MiddlewareRegisterTestMiddlewareB::class,
    ]);
});

test('an unknown group returns an empty list', function () {
    $register = new MiddlewareRegisterTestRegister();

    expect($register->getMiddleware('unknown'))->toBe([]);
});
