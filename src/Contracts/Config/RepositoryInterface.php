<?php

namespace Oak\Contracts\Config;

interface RepositoryInterface
{
    /**
     * RepositoryInterface constructor.
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = []);

    /**
     * @param array<string, mixed> $config
     * @return mixed
     */
    public function setAll(array $config = []);

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set(string $key, $value);

    /**
     * @return array<string, mixed>
     */
    public function all(): array;
}
