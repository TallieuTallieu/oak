<?php

namespace Oak\Contracts\Seeding;

/**
 * A project-defined operation that populates initial or demo data.
 */
interface SeederInterface
{
    /**
     * @return void
     */
    public function seed(): void;
}
