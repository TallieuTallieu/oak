<?php

namespace Oak\Config;

use Oak\Contracts\Config\RepositoryInterface;

class Repository implements RepositoryInterface
{
    /**
     * @var array<string, mixed> $config
     */
    private $config;

    /**
     * Repository constructor.
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $config
     * @return mixed|void
     */
    public function setAll(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $arr = explode('.', $key);
        $config = $this->config[$arr[0]] ?? $default;
        array_shift($arr);

        foreach ($arr as $keyPart) {
            if (!is_array($config) || !isset($config[$keyPart])) {
                return $default;
            }
            $config = $config[$keyPart];
        }

        return $config;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }
}
