<?php

namespace Oak\Logger\Facade;

use Oak\Contracts\Logger\LoggerInterface;
use Oak\Facade;

/**
 * Logger Facade providing static access to logging functionality
 * 
 * @method static void log(string $text) Log a message
 */
class Logger extends Facade
{
    /**
     * Get the service contract that this facade represents
     * 
     * @return class-string<LoggerInterface>
     */
    protected static function getContract(): string
    {
        return LoggerInterface::class;
    }
}