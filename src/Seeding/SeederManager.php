<?php

namespace Oak\Seeding;

use InvalidArgumentException;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Contracts\Seeding\SeederInterface;
use Oak\Seeding\Exception\UnknownSeederException;

/**
 * Explicitly registered seeders, resolved only when selected for execution.
 */
class SeederManager
{
    /**
     * @var array<string, SeederInterface|class-string<SeederInterface>>
     */
    private array $seeders = [];

    /**
     * @param ContainerInterface $app
     */
    public function __construct(private ContainerInterface $app) {}

    /**
     * @param string $name
     * @param SeederInterface|class-string<SeederInterface> $seeder
     * @return void
     */
    public function register(string $name, SeederInterface|string $seeder): void
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException(
                'A seeder name must not be empty',
            );
        }

        if (isset($this->seeders[$name])) {
            throw new InvalidArgumentException(
                "Seeder '$name' is already registered",
            );
        }

        if (
            is_string($seeder) &&
            !is_a($seeder, SeederInterface::class, true)
        ) {
            throw new InvalidArgumentException(
                'A seeder must implement SeederInterface',
            );
        }

        $this->seeders[$name] = $seeder;
    }

    /**
     * Execute the selected seeder every time; no execution history is kept.
     *
     * @param string $name
     * @return void
     * @throws UnknownSeederException
     */
    public function run(string $name): void
    {
        $seeder = $this->seeders[$name] ?? null;

        if ($seeder === null) {
            throw new UnknownSeederException(
                "No seeder named '$name' is registered",
            );
        }

        if (is_string($seeder)) {
            $seeder = $this->app->get($seeder);
        }

        $seeder->seed();
    }
}
