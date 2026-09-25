<?php

namespace Oak\Seeding\Console;

use Oak\Console\Command\Argument;
use Oak\Console\Command\Command;
use Oak\Console\Command\Signature;
use Oak\Console\Exception\InvalidArgumentException;
use Oak\Contracts\Console\InputInterface;
use Oak\Contracts\Console\OutputInterface;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Seeding\SeederManager;

/**
 * Manually execute one explicitly selected seeder.
 */
class SeedCommand extends Command
{
    /**
     * @param SeederManager $manager
     * @param ContainerInterface $app
     */
    public function __construct(
        private SeederManager $manager,
        ContainerInterface $app,
    ) {
        parent::__construct($app);
    }

    protected function createSignature(Signature $signature): Signature
    {
        return $signature
            ->setName('seed')
            ->setDescription('Run a registered seeder by name')
            ->addArgument(
                Argument::create('name')->setDescription(
                    'The registered seeder name',
                ),
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $name = $input->getArgument('name');

        if (!is_string($name) || trim($name) === '') {
            throw new InvalidArgumentException('A seeder name is required');
        }

        $this->manager->run($name);
        $output->writeLine(
            "Seeder '$name' completed",
            OutputInterface::TYPE_INFO,
        );
    }
}
