<?php

namespace Oak\Seeding;

use Closure;
use Oak\Contracts\Migration\RevisionInterface;

/**
 * Execute a named seeder at a chosen point in a migration sequence.
 */
class SeederRevision implements RevisionInterface
{
    /**
     * @param SeederManager $manager
     * @param string $name
     * @param (Closure(): void)|null $rollback Project-defined cleanup, if any.
     */
    public function __construct(
        private SeederManager $manager,
        private string $name,
        private ?Closure $rollback = null,
    ) {}

    /**
     * @return void
     */
    public function up(): void
    {
        $this->manager->run($this->name);
    }

    /**
     * Leave seeded data untouched unless the project supplies cleanup.
     *
     * @return void
     */
    public function down(): void
    {
        if ($this->rollback !== null) {
            ($this->rollback)();
        }
    }

    /**
     * @return string
     */
    public function describeUp(): string
    {
        return "Run seeder '$this->name'";
    }

    /**
     * @return string
     */
    public function describeDown(): string
    {
        return $this->rollback === null
            ? "Leave data seeded by '$this->name' unchanged"
            : "Roll back seeder '$this->name'";
    }
}
