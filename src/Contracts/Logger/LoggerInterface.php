<?php

namespace Oak\Contracts\Logger;

/**
 * Interface LoggerInterface
 * @package Oak\Contracts\Logger
 */
interface LoggerInterface
{
    /**
     * @param string $text
     * @return void
     */
    public function log(string $text);
}
