<?php

namespace Oak\Contracts\Console;

use Oak\Console\Command\Signature;

/**
 * Interface InputInterface
 * @package Oak\Contracts\Console
 */
interface InputInterface
{
    /**
     * @param Signature $signature
     * @return void
     */
    public function setSignature(Signature $signature);

    /**
     * @return Signature
     */
    public function getSignature(): Signature;

    /**
     * @return void
     */
    public function validate();

    /**
     * @return array<string, mixed>
     */
    public function getArguments();

    /**
     * @param string $name
     * @return mixed
     */
    public function getArgument(string $name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasArgument(string $name): bool;

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setArgument(string $name, $value);

    /**
     * @return array<string, mixed>
     */
    public function getOptions();

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption(string $name);

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setOption(string $name, $value);

    /**
     * @return bool
     */
    public function hasSubCommand(): bool;

    /**
     * @return string|null
     */
    public function getSubCommand();

    /**
     * @param string $name
     * @return void
     */
    public function setSubCommand(string $name);
}
