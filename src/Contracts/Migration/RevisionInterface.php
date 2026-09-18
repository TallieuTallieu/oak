<?php

namespace Oak\Contracts\Migration;

interface RevisionInterface
{
    /**
     * @return void
     */
    public function up();

    /**
     * @return void
     */
    public function down();

    public function describeUp(): string;
    public function describeDown(): string;
}
