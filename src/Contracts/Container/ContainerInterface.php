<?php

namespace Oak\Contracts\Container;

/**
 * Interface ContainerInterface
 * @package Oak\Contracts\Container
 */
interface ContainerInterface
{
    /**
     * @template T of object
     * @param class-string<T>|string $contract
     * @param T|callable|string $mixed
     * @return void
     */
    public function set(string $contract, $mixed);

    /**
     * @template T of object
     * @param class-string<T>|string $contract
     * @return T
     */
    public function get(string $contract);

    /**
     * @param string $contract
     * @return bool
     */
    public function has(string $contract): bool;

    /**
     * Directly store an instance for a contract
     *
     * @template T of object
     * @param class-string<T>|string $contract
     * @param T|callable|string $implementation
     * @return void
     */
    public function singleton(string $contract, $implementation);

    /**
     * @template T of object
     * @param class-string<T>|string $contract
     * @param T $instance
     * @return void
     */
    public function instance(string $contract, $instance);

    /**
     * @param class-string|string $contract
     * @param string $argument
     * @param mixed $value
     * @return void
     */
    public function whenAsksGive(string $contract, string $argument, $value);

    /**
     * @template T of object
     * @param class-string<T>|string $contract
     * @param array $arguments
     * @return T
     */
    public function getWith(string $contract, array $arguments);

    /**
     * Determine if the application is running in console/CLI mode
     *
     * @return bool True if running via CLI, false if running via web server
     */
    public function isRunningInConsole(): bool
}
