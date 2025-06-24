<?php

namespace Oak;

use Dotenv\Dotenv;
use Oak\Container\Container;

/**
 * Application class - The main framework bootstrap and service container
 * 
 * Acts as both the primary dependency injection container and application lifecycle manager.
 * Orchestrates service provider registration, environment loading, and application booting.
 * 
 * @package Oak
 */
class Application extends Container
{
    const VERSION = '1.1.9';

    /**
     * @var bool $isBooted
     */
    private $isBooted;

    /**
     * @var array $registeredProviders
     */
    private $registeredProviders = [];

    /**
     * @var array $lazyProviders
     */
    private $lazyProviders = [];

    /**
     * @var string $envPath
     */
    private $envPath;

    /**
     * @var string $configPath
     */
    private $configPath;

    /**
     * @var string $cachePath
     */
    private $cachePath;

    /**
     * Application constructor - Initialize paths and bootstrap core services
     * 
     * @param string $envPath Path to directory containing .env files
     * @param string $configPath Path to configuration files directory  
     * @param string $cachePath Path to cache storage directory
     */
    public function __construct(string $envPath, string $configPath, string $cachePath)
    {
        $this->envPath = $envPath;
        $this->configPath = $configPath;
        $this->cachePath = $cachePath;

        $this->loadEnv();

        // We set this application as the container for the facade
        Facade::setContainer($this);
    }

    /**
     * Register one or more service providers with the application
     * 
     * Supports multiple input formats:
     * - Array of providers (recursive registration)
     * - String class name (instantiated automatically)
     * - Provider instance (registered directly)
     * 
     * @param ServiceProvider|ServiceProvider[]|class-string<ServiceProvider> $provider
     * @return void
     * @throws \Exception When provider instantiation fails
     */
    public function register($provider): void
    {
        if (is_array($provider)) {
            foreach ($provider as $service) {
                $this->register($service);
            }
            return;
        }

        if (is_string($provider)) {
            $this->set($provider, $provider);
            $provider = $this->get($provider);
        }

        if ($provider->isLazy()) {
            foreach ($provider->provides() as $providing) {
                $this->lazyProviders[$providing] = $provider;
            }
            $provider->register($this);
        } else {
            $this->registeredProviders[] = $provider;
            $this->initServiceProvider($provider);
        }
    }

    /**
     * Initialize a service provider by calling its register method
     * 
     * If the application is already booted, immediately boots the provider as well.
     *
     * @param ServiceProvider $provider The provider to initialize
     * @return void
     */
    private function initServiceProvider(ServiceProvider $provider): void
    {
        $provider->register($this);

        // If the application is already booted, boot the provider right away
        if ($this->isBooted) {
            $this->bootServiceProvider($provider);
        }
    }

    /**
     * Boot a service provider by calling its boot method
     * 
     * Prevents double-booting by checking the provider's booted status.
     *
     * @param ServiceProvider $provider The provider to boot
     * @return void
     */
    private function bootServiceProvider(ServiceProvider $provider): void
    {
        if (! $provider->isBooted()) {
            $provider->setBooted();
            $provider->boot($this);
        }
    }

    /**
     * Resolve a service from the container with lazy provider support
     * 
     * If the requested service has a lazy provider, boots the provider first
     * before delegating to the parent container's get method.
     *
     * @template T of object
     * @param class-string<T>|string $key The service contract or key
     * @return T The resolved service instance
     * @throws \Exception When service cannot be resolved
     */
    public function get(string $key)
    {
        if (isset($this->lazyProviders[$key])) {
            $this->bootServiceProvider($this->lazyProviders[$key]);
            unset($this->lazyProviders[$key]);
        }

        return parent::get($key);
    }

    /**
     * Boot all registered (non-lazy) service providers
     * 
     * Prevents double-booting and marks the application as fully booted.
     * Lazy providers are booted on-demand when their services are first requested.
     *
     * @return void
     */
    private function boot(): void
    {
        // First check if the application is already booted
        if ($this->isBooted) {
            return;
        }

        // Boot all registered providers
        foreach ($this->registeredProviders as $provider) {
            $this->bootServiceProvider($provider);
        }

        $this->isBooted = true;
    }

    /**
     * Bootstrap the application by booting all registered service providers
     * 
     * This is typically called after all service providers have been registered
     * and the application is ready to handle requests.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // We boot all service providers
        $this->boot();
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

    /**
     * Get the path to the environment files directory
     *
     * @return string The absolute path to the env directory
     */
    public function getEnvPath(): string
    {
        return $this->envPath;
    }

    /**
     * Get the path to the configuration files directory
     *
     * @return string The absolute path to the config directory
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * Get the path to the cache storage directory
     *
     * @return string The absolute path to the cache directory
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * Load environment variables from .env files
     * 
     * Uses Dotenv to load environment variables from the configured env path.
     * Variables are loaded as immutable to prevent runtime modification.
     * 
     * @return void
     */
    private function loadEnv(): void
    {
        (Dotenv::createImmutable($this->getEnvPath()))
            ->load();
    }
}
