<?php

namespace Oak\Migration;

use InvalidArgumentException;
use Oak\Contracts\Migration\RevisionInterface;
use RuntimeException;

class MigratorRevision implements RevisionInterface
{
    /**
     * The migrator whose revisions this revision delegates to
     *
     * @var Migrator|null
     */
    private $migrator;

    /**
     * Name of the migrator, resolved through the manager at run time
     *
     * @var string|null
     */
    private $migratorName;

    /**
     * Manager used to resolve the migrator by name
     *
     * @var MigrationManager|null
     */
    private $manager;

    /**
     * The version the wrapped migrator is rolled to on up()
     *
     * @var int
     */
    private $toVersion;

    /**
     * The version the wrapped migrator is rolled back to on down()
     *
     * @var int
     */
    private $fromVersion;

    /**
     * MigratorRevision constructor. Takes either a Migrator instance, or a
     * migrator name together with the manager to resolve it from when the
     * revision runs.
     *
     * @param Migrator|string $migrator
     * @param int $toVersion
     * @param int $fromVersion
     * @param MigrationManager|null $manager
     */
    public function __construct(
        Migrator|string $migrator,
        int $toVersion,
        int $fromVersion = 0,
        ?MigrationManager $manager = null
    ) {
        if (is_string($migrator)) {
            if (!$manager) {
                throw new InvalidArgumentException(
                    'A migrator name requires a MigrationManager to resolve it from'
                );
            }

            $this->migratorName = $migrator;
            $this->manager = $manager;
        } else {
            $this->migrator = $migrator;
        }

        $this->toVersion = $toVersion;
        $this->fromVersion = $fromVersion;
    }

    /**
     * Creates a revision that resolves the migrator by name through the
     * manager when it runs, for migrators registered by other service
     * providers that may not have booted yet.
     *
     * @param MigrationManager $manager
     * @param string $name
     * @param int $toVersion
     * @param int $fromVersion
     * @return self
     */
    public static function inManager(
        MigrationManager $manager,
        string $name,
        int $toVersion,
        int $fromVersion = 0
    ): self {
        return new self($name, $toVersion, $fromVersion, $manager);
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->getMigrator()->rollTo($this->toVersion);
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->getMigrator()->rollTo($this->fromVersion);
    }

    public function describeUp(): string
    {
        return 'Migrate \'' .
            $this->getMigratorName() .
            '\' to version ' .
            $this->toVersion;
    }

    public function describeDown(): string
    {
        return 'Roll \'' .
            $this->getMigratorName() .
            '\' back to version ' .
            $this->fromVersion;
    }

    /**
     * @return string
     */
    private function getMigratorName(): string
    {
        if ($this->migratorName !== null) {
            return $this->migratorName;
        }

        return $this->getMigrator()->getName();
    }

    /**
     * @return Migrator
     */
    private function getMigrator(): Migrator
    {
        if (!$this->migrator) {
            if ($this->migratorName === null || $this->manager === null) {
                throw new RuntimeException(
                    'No migrator or migrator name was provided'
                );
            }

            $this->migrator = $this->manager->getMigrator($this->migratorName);

            if (!$this->migrator) {
                throw new RuntimeException(
                    'No migrator named \'' .
                        $this->migratorName .
                        '\' is registered'
                );
            }
        }

        return $this->migrator;
    }
}
