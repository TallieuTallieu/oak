<?php

namespace Oak\Migration\Console;

use Oak\Console\Command\Command;
use Oak\Console\Command\Signature;

class MigrationCommand extends Command
{
    protected function createSignature(Signature $signature): Signature
    {
        return $signature
            ->setName('migration')
            ->addSubCommand(ListCommand::class)
            ->addSubCommand(MigrateCommand::class)
            ->addSubCommand(ResetCommand::class)
            ->addSubCommand(ResetCountersCommand::class)
            ->addSubCommand(DowndateCommand::class)
            ->addSubCommand(UpdateCommand::class);
    }
}
