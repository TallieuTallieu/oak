<?php

namespace Oak\Contracts\Migration;

use Oak\Migration\Migrator;

interface VersionStorageInterface
{
    public function get(Migrator $migrator): int;

    /**
     * @param Migrator $migrator
     * @param int $version
     * @return void
     */
    public function store(Migrator $migrator, int $version);
}
