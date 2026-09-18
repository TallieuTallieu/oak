<?php

namespace Oak\Console\Facade;

use Oak\Contracts\Console\KernelInterface;
use Oak\Facade;

/**
 * Console Facade providing static access to console kernel functionality
 *
 * @method static mixed handle(\Oak\Contracts\Console\InputInterface $input, \Oak\Contracts\Console\OutputInterface $output) Handle console input and output
 *
 * @extends Facade<KernelInterface>
 */
class Console extends Facade
{
    /**
     * Get the service contract that this facade represents
     *
     * @return class-string<KernelInterface>
     */
    protected static function getContract(): string
    {
        return KernelInterface::class;
    }
}
