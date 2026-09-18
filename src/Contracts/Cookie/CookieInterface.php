<?php

namespace Oak\Contracts\Cookie;

interface CookieInterface
{
    /**
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @return mixed
     */
    public function set(string $name, $value, int $expire = 0);

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name);

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Removes a cookie
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name): void;
}
