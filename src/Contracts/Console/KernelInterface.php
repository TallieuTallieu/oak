<?php

namespace Oak\Contracts\Console;

/**
 * Interface KernelInterface
 * @package Oak\Contracts\Console
 */
interface KernelInterface
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    public function handle(InputInterface $input, OutputInterface $output);

    /**
     * Register a command
     *
     * @param \Oak\Console\Command\Command|class-string<\Oak\Console\Command\Command> $command
     * @return void
     */
    public function registerCommand($command);
}
