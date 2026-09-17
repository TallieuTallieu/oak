<?php

namespace Oak\Cookie;

use Oak\Contracts\Cookie\CookieInterface;

class Cookie implements CookieInterface
{
    /**
     * @var string $path
     */
    private $path;

    /**
     * @var bool $secure
     */
    private $secure;

    /**
     * @var bool $httpOnly
     */
    private $httpOnly;

    /**
     * Cookie constructor.
     * @param string $path
     */
    public function __construct(string $path, bool $secure, bool $httpOnly)
    {
        $this->path = $path;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @return mixed|void
     */
    public function set(string $name, $value, int $expire = 0)
    {
        $value = json_encode($value);

        if ($value === false) {
            throw new \InvalidArgumentException(
                'Cookie value could not be encoded as JSON'
            );
        }

        setcookie(
            $name,
            $value,
            $expire,
            $this->path,
            '',
            $this->secure,
            $this->httpOnly
        );
        $_COOKIE[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        $value = $_COOKIE[$name] ?? null;

        return is_string($value) ? json_decode($value) : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }
}
