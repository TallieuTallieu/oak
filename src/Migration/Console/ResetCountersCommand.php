<?php

namespace Oak\Migration\Console;

use Oak\Console\Command\Command;
use Oak\Console\Command\Option;
use Oak\Console\Command\Signature;
use Oak\Contracts\Console\InputInterface;
use Oak\Contracts\Console\OutputInterface;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Migration\MigrationManager;

class ResetCountersCommand extends Command
{
    /**
     * @var MigrationManager $manager
     */
    private $manager;

    /**
     * ResetCountersCommand constructor.
     * @param MigrationManager $manager
     * @param ContainerInterface $app
     */
    public function __construct(MigrationManager $manager, ContainerInterface $app)
    {
        $this->manager = $manager;
        parent::__construct($app);
    }

    protected function createSignature(Signature $signature): Signature
    {
        return $signature
            ->setName('reset-counters')
            ->setDescription('Reset migration counters to 0 without running any migrations')
            ->addOption(
                Option::create('migrator', 'm')
                    ->setDescription('Specify a specific migrator')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! count($this->manager->getMigrators())) {
            $output->writeLine('No migrators registered', OutputInterface::TYPE_ERROR);
            return;
        }

        $migratorName = $input->getOption('migrator');
        $resetCount = 0;

        foreach ($this->manager->getMigrators() as $migrator) {
            if (! $migratorName || $migrator->getName() === $migratorName) {
                // Reset the counter to 0 without running any migrations
                $migrator->resetCounter();
                
                $output->writeLine('Reset counter for migrator: ' . $migrator->getName());
                $resetCount++;

                if ($migratorName) {
                    break;
                }
            }
        }

        if ($resetCount === 0) {
            if ($migratorName) {
                $output->writeLine('Migrator "' . $migratorName . '" not found', OutputInterface::TYPE_ERROR);
            } else {
                $output->writeLine('No migrators found to reset', OutputInterface::TYPE_ERROR);
            }
        } else {
            $output->writeLine('Successfully reset ' . $resetCount . ' migrator counter(s) to 0');
        }
    }
}