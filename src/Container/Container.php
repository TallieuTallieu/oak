<?php

namespace Oak\Container;

use Exception;
use ReflectionClass;
use Oak\Contracts\Container\ContainerInterface;

/**
 * Class Container
 * @package Oak\Container
 */
class Container implements ContainerInterface
{
    /**
     * All stored contracts and their implementations
     *
     * @var array
     */
    private $contracts = [];

    /**
     * Array of contracts we wish to use as singletons
     *
     * @var array
     */
    private $singletons = [];

    /**
     * Instances of contracts for singleton use
     *
     * @var array
     */
    private $instances = [];

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @template T of object
     * @param class-string<T>|string $contract
     * @param T|callable|string $implementation
     * @return void
     */
    public function set(string $contract, $implementation)
    {
        $this->contracts[$contract] = $implementation;
    }

    /**
     * Checks if the container has a value by a given contract
     *
     * @param string $contract
     * @return bool
     */
    public function has(string $contract): bool
    {
        return isset($this->contracts[$contract]);
    }

    /**
     * Stores a implementation in the container for the given key and also store it as a singleton
     *
     * @template T of object
     * @param class-string<T>|string $contract
     * @param T|callable|string $implementation
     * @return void
     */
    public function singleton(string $contract, $implementation)
    {
        $this->set($contract, $implementation);
        $this->singletons[] = $contract;
    }

    /**
     * Directly store an instance for a contract
     *
     * @template T of object
     * @param class-string<T>|string $contract
     * @param T $instance
     * @return void
     */
    public function instance(string $contract, $instance)
    {
        $this->set($contract, get_class($instance));
        $this->instances[$contract] = $instance;
    }

    /**
     * Retreives a value from the container by a given contract
     *
     * @template T of object
     * @param class-string<T>|string $contract
     * @return T
     * @throws \Exception
     */
    public function get(string $contract)
    {
        // Looks like we're getting a singleton instance,
        // So we should check if it was instantiated before
        // If so we retrieve it
        if (isset($this->instances[$contract])) {
            return $this->instances[$contract];
        }

        if (!in_array($contract, $this->singletons)) {
            return $this->create($contract);
        }

        // It wasn't instantiated before, so we create and save it now
        $this->instances[$contract] = $instance = $this->create($contract);

        // Give back the instance
        return $instance;
    }

    /**
     * @param class-string|string $contract
     * @param string $argument
     * @param mixed $value
     * @return void
     */
    public function whenAsksGive(string $contract, string $argument, $value)
    {
        if (!isset($this->arguments[$contract])) {
            $this->arguments[$contract] = [];
        }

        $this->arguments[$contract][$argument] = $value;
    }

    /**
     * @template T of object
     * @param class-string<T>|string $contract
     * @param array $arguments
     * @return T
     * @throws Exception
     */
    public function getWith(string $contract, array $arguments)
    {
        return $this->create($contract, $arguments);
    }

    /**
     * @param string $contract
     * @param array $arguments
     * @return mixed|object
     * @throws \ReflectionException
     */
    private function create(string $contract, array $arguments = [])
    {
        // First check if we can find an implementation for the requested contract
        if (!$this->has($contract)) {
            if (class_exists($contract)) {
                $implementation = $contract;
            } else {
                throw new Exception(
                    'Could not create dependency with contract: ' . $contract
                );
            }
        } else {
            $implementation = $this->contracts[$contract];
        }

        // Check if we have to give this class some stored arguments
        if (
            is_string($implementation) &&
            isset($this->arguments[$implementation])
        ) {
            $arguments = array_merge(
                $this->arguments[$implementation],
                $arguments
            );
        }

        // Is it callable? Call it right away and return the results
        if (is_callable($implementation)) {
            return call_user_func($implementation, $this);
        }

        $reflect = new ReflectionClass($implementation);
        $constructor = $reflect->getConstructor();

        if ($constructor === null) {
            return new $implementation();
        }

        $parameters = $constructor->getParameters();

        if (!count($parameters)) {
            return new $implementation();
        }

        $injections = [];

        foreach ($parameters as $parameter) {
            $class =
                $parameter->getType() && !$parameter->getType()->isBuiltin()
                    ? new ReflectionClass($parameter->getType()->getName())
                    : null;

            // Check if param is a class
            if ($class) {
                $className = $class->name;

                // Check if it was explicitely given as an argument
                if (isset($arguments[$className])) {
                    // Check if it's a string
                    if (is_string($arguments[$className])) {
                        // If so, get it from the container
                        $injections[] = $this->get($arguments[$className]);
                        continue;
                    } else {
                        // ...else inject it raw
                        $injections[] = $arguments[$className];
                        continue;
                    }
                }

                $argName = $parameter->getName();

                // Check if the argument was given by argument name instead of class
                if (isset($arguments[$argName])) {
                    // Check if it's a string
                    if (is_string($arguments[$argName])) {
                        // If so, get it from the container
                        $injections[] = $this->get($arguments[$argName]);
                        continue;
                    } else {
                        // ...else inject it raw
                        $injections[] = $arguments[$argName];
                        continue;
                    }
                }

                // Check if the container has the class
                if ($this->has($className)) {
                    // Get the class from the container and add it as injection
                    $injections[] = $this->get($className);
                    continue;
                }

                // Check if the argument has a default value
                if ($parameter->isDefaultValueAvailable()) {
                    // Inject the default value (most probably null)
                    $injections[] = $parameter->getDefaultValue();
                    continue;
                }

                // Try to inject it anyway
                $injections[] = $this->get($className);
                continue;
            } else {
                $argName = $parameter->getName();

                // Check if the argument was explicitely given
                if (isset($arguments[$argName])) {
                    $injections[] = $arguments[$argName];
                    continue;
                }

                // Check if the argument has a default value
                if ($parameter->isDefaultValueAvailable()) {
                    // Inject the default value
                    $injections[] = $parameter->getDefaultValue();
                    continue;
                }
            }

            throw new Exception(
                'Could not provide argument "' .
                    $parameter->getName() .
                    '" to ' .
                    $contract
            );
        }

        return $reflect->newInstanceArgs($injections);
    }

    /**
     * Determine if the application is running in console/CLI mode
     *
     * @return bool True if running via CLI, false if running via web server
     */
    public function isRunningInConsole(): bool
    {
        return php_sapi_name() === 'cli';
    }
}
