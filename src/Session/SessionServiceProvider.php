<?php

namespace Oak\Session;

use Oak\ServiceProvider;
use Oak\Contracts\Config\RepositoryInterface;
use Oak\Contracts\Console\KernelInterface;
use Oak\Contracts\Session\SessionIdentifierInterface;
use Oak\Contracts\Container\ContainerInterface;

class SessionServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $app)
    {
        $config = $app->get(RepositoryInterface::class);

        $handler = $config->get('session.handler', FileSessionHandler::class);

        $app->singleton(
            \SessionHandlerInterface::class,
            is_string($handler) ? $handler : FileSessionHandler::class,
        );
        $app->singleton(Session::class, Session::class);
        $app->singleton(
            SessionIdentifierInterface::class,
            SessionIdentifier::class,
        );

        $name = $config->get('session.name', 'app');
        $cookiePrefix = $config->get('session.cookie_prefix', 'session');
        $identifierLength = $config->get('session.identifier_length', 40);

        $app->whenAsksGive(
            FileSessionHandler::class,
            'path',
            SessionPath::resolve($app, $config),
        );
        $app->whenAsksGive(
            Session::class,
            'name',
            is_string($name) ? $name : 'app',
        );
        $app->whenAsksGive(
            Session::class,
            'cookiePrefix',
            is_string($cookiePrefix) ? $cookiePrefix : 'session',
        );
        $app->whenAsksGive(
            Session::class,
            'identifierLength',
            is_numeric($identifierLength) ? (int) $identifierLength : 40,
        );
    }

    public function boot(ContainerInterface $app)
    {
        // Register console command
        if ($app->isRunningInConsole()) {
            $app->get(KernelInterface::class)->registerCommand(
                \Oak\Session\Console\Session::class,
            );
        }
    }
}
