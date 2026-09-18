<?php

namespace Oak\Cookie;

use Oak\Contracts\Config\RepositoryInterface;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Contracts\Cookie\CookieInterface;
use Oak\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{
    protected $isLazy = true;

    public function boot(ContainerInterface $app)
    {
        //
    }

    public function register(ContainerInterface $app)
    {
        $app->singleton(CookieInterface::class, function (
            ContainerInterface $app,
        ) {
            $config = $app->get(RepositoryInterface::class);

            $path = $config->get('cookie.path', '/');

            return new Cookie(
                is_string($path) ? $path : '/',
                (bool) $config->get('cookie.secure', false),
                (bool) $config->get('cookie.http_only', true),
            );
        });
    }

    public function provides(): array
    {
        return [CookieInterface::class];
    }
}
