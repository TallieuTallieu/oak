<?php

namespace Oak;

use Oak\Contracts\Container\ContainerInterface;

/**
 * Base Facade class providing static access to services in the container
 * 
 * Facades act as static proxies to underlying service instances, providing
 * a clean, expressive interface while maintaining the benefits of dependency injection.
 * 
 * @template T of object
 */
abstract class Facade
{
    /**
     * The container from which to resolve the instances
     *
     * @var ContainerInterface $container
     */
    private static $container;

    /**
     * Sets the container to use for resolving service contracts
     *
     * @param ContainerInterface $container The container instance
     * @return void
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Get the service contract/class name that this facade represents
     * 
     * @return class-string<T> The fully qualified class name or interface
     */
    abstract protected static function getContract(): string;

    /**
     * Resolve and return the service instance from the container
     *
     * @return T The resolved service instance
     * @throws \Exception When no container is set or service cannot be resolved
     */
    final protected static function getInstance()
    {
        if (! self::$container) {
            throw new \Exception('No container set for facades');
        }

        return self::$container->get(static::getContract());
    }

    /**
     * Handle static method calls by forwarding them to the resolved service instance
     * 
     * This magic method enables static access to instance methods on the underlying service.
     * 
     * @param string $method The method name to call
     * @param array $arguments The arguments to pass to the method
     * @return mixed The result of the method call
     * @throws \Exception When the service cannot be resolved or method doesn't exist
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return static::getInstance()->$method(...$arguments);
    }
}