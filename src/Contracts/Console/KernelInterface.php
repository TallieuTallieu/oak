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
     * @throws \Exception
     */
    public function handle(InputInterface $input, OutputInterface $output);

    /**
     * Register a command
     *
     * @param $command
     */
    public function registerCommand($command);
}