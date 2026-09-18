<?php

namespace Oak\Console\Input;

use Oak\Console\Command\Signature;
use Oak\Console\Exception\InvalidArgumentException;

/**
 * Class ConsoleInput
 * @package Oak\Console\Input
 */
class ConsoleInput extends Input
{
    /**
     * @param Signature $signature
     * @return void
     */
    public function setSignature(Signature $signature)
    {
        parent::setSignature($signature);
        $this->reset();
        $this->parse();
    }

    /**
     * Reset the state of the input
     *
     * @return void
     */
    private function reset()
    {
        $this->subCommand = null;
        $this->arguments = [];
        $this->missingArguments = [];
    }

    /**
     * Parse the input from the argv globals
     *
     * @return void
     */
    private function parse()
    {
        if (!$this->rawArguments) {
            $argv =
                isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])
                    ? $GLOBALS['argv']
                    : [];

            $this->rawArguments = array_values(
                array_filter($argv, static function ($argument) {
                    return is_string($argument);
                }),
            );
        }

        array_shift($this->rawArguments);

        $this->parseRawArguments();
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate()
    {
        if (count($this->missingArguments)) {
            throw new InvalidArgumentException(
                'Missing argument(s) ' . implode(', ', $this->missingArguments),
            );
        }
    }
}
