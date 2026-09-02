<?php

namespace Oak\Migration;

use Oak\Contracts\Container\ContainerInterface;

class MigrationManager
{
    /**
     * @var ContainerInterface $app
     */
    private $app;

    /**
     * @var array $migrators
     */
    private $migrators = [];

    /**
     * Migrators that run when no specific migrator is targeted
     *
     * @var array $autoRunMigrators
     */
    private $autoRunMigrators = [];

    /**
     * MigrationManager constructor.
     * @param ContainerInterface $app
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Adds a migrator. Pass $autoRun false for a migrator that should only
     * move when targeted explicitly or driven by a MigratorRevision.
     *
     * @param $migrator
     * @param bool $autoRun
     */
    public function addMigrator($migrator, bool $autoRun = true)
    {
        if (is_string($migrator)) {
            $migrator = $this->app->get($migrator);
        }

        $this->migrators[] = $migrator;

        if ($autoRun) {
            $this->autoRunMigrators[] = $migrator;
        }
    }

    /**
     * @return array
     */
    public function getMigrators(): array
    {
        return $this->migrators;
    }

    /**
     * @return array
     */
    public function getAutoRunMigrators(): array
    {
        return $this->autoRunMigrators;
    }

    /**
     * Gets a registered migrator by its name
     *
     * @param string $name
     * @return Migrator|null
     */
    public function getMigrator(string $name): ?Migrator
    {
        foreach ($this->migrators as $migrator) {
            if ($migrator->getName() === $name) {
                return $migrator;
            }
        }

        return null;
    }
}
