<?php

namespace Oak\Seeding;

use Oak\Contracts\Console\KernelInterface;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Seeding\Console\SeedCommand;
use Oak\ServiceProvider;

/**
 * Register the seeder manager and its optional console entry point.
 */
class SeedingServiceProvider extends ServiceProvider
{
    /**
     * @param ContainerInterface $app
     * @return void
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(
            SeederManager::class,
            static fn(
                ContainerInterface $app,
            ): SeederManager => new SeederManager($app),
        );
    }

    /**
     * @param ContainerInterface $app
     * @return void
     */
    public function boot(ContainerInterface $app): void
    {
        if ($app->isRunningInConsole()) {
            $app->get(KernelInterface::class)->registerCommand(
                SeedCommand::class,
            );
        }
    }
}
