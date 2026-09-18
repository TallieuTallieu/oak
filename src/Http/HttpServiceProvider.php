<?php

namespace Oak\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Contracts\Http\ResponseEmitterInterface;
use Oak\Contracts\Http\Routing\RouterInterface;
use Oak\Contracts\Http\KernelInterface;
use Oak\Http\Routing\Router;
use Oak\ServiceProvider;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpServiceProvider extends ServiceProvider
{
    public function boot(ContainerInterface $app)
    {
        //
    }

    public function register(ContainerInterface $app)
    {
        $app->singleton(RouterInterface::class, Router::class);
        $app->singleton(KernelInterface::class, Kernel::class);
        $app->set(ResponseEmitterInterface::class, ResponseEmitter::class);
        $app->set(ResponseFactoryInterface::class, Psr17Factory::class);
        $app->set(StreamFactoryInterface::class, Psr17Factory::class);
        $app->set(ServerRequestInterface::class, function (
            ContainerInterface $app,
        ) {
            $psr17Factory = $app->get(ResponseFactoryInterface::class);

            if (
                !($psr17Factory instanceof ServerRequestFactoryInterface) ||
                !($psr17Factory instanceof UriFactoryInterface) ||
                !($psr17Factory instanceof UploadedFileFactoryInterface) ||
                !($psr17Factory instanceof StreamFactoryInterface)
            ) {
                throw new \RuntimeException(
                    'The bound ResponseFactoryInterface must also implement the PSR-17 server request, uri, uploaded file and stream factory interfaces',
                );
            }

            return new ServerRequestCreator(
                $psr17Factory, // ServerRequestFactory
                $psr17Factory, // UriFactory
                $psr17Factory, // UploadedFileFactory
                $psr17Factory, // StreamFactory
            )->fromGlobals();
        });
    }
}
