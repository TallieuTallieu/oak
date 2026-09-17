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
     * @param T|callable(ContainerInterface): T|class-string<T>|string $mixed
     * @return void
     */
    public function set(string $contract, $mixed);

    /**
     * @template T of object
     * @param class-string<T>|string $contract
     * @return ($contract is class-string<T> ? T : object)
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
     * @param T|callable(ContainerInterface): T|class-string<T>|string $implementation
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
     * @param array<string, mixed> $arguments
     * @return ($contract is class-string<T> ? T : object)
     */
    public function getWith(string $contract, array $arguments);

    /**
     * Determine if the application is running in console/CLI mode
     *
     * @return bool True if running via CLI, false if running via web server
     */
    public function isRunningInConsole(): bool;

    /**
     * Get the path to the environment files directory
     *
     * @return string The absolute path to the env directory
     */
    public function getEnvPath(): string;

    /**
     * Get the path to the configuration files directory
     *
     * @return string The absolute path to the config directory
     */
    public function getConfigPath(): string;

    /**
     * Get the path to the cache storage directory
     *
     * @return string The absolute path to the cache directory
     */
    public function getCachePath(): string;
}
