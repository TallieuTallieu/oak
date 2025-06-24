<?php

namespace Oak\Config\Facade;

use Oak\Contracts\Config\RepositoryInterface;
use Oak\Facade;

/**
 * Configuration Facade providing static access to configuration values
 * 
 * @method static mixed get(string $key, mixed $default = null) Get a configuration value by key with optional default
 * @method static bool has(string $key) Check if a configuration key exists
 * @method static mixed set(string $key, mixed $value) Set a configuration value
 * @method static mixed setAll(array $config = []) Set multiple configuration values at once
 * @method static array all() Get all configuration values
 */
class Config extends Facade
{
    /**
     * Get the service contract that this facade represents
     * 
     * @return class-string<RepositoryInterface>
     */
    protected static function getContract(): string
    {
        return RepositoryInterface::class;
    }
}