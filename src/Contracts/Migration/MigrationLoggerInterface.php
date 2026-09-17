<?php

namespace Oak\Contracts\Migration;

interface MigrationLoggerInterface
{
    /**
     * @param RevisionInterface $revision
     * @return void
     */
    public function logUpdate(RevisionInterface $revision);

    /**
     * @param RevisionInterface $revision
     * @return void
     */
    public function logDowndate(RevisionInterface $revision);
}
