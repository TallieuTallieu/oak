<?php

namespace Oak\Cookie\Facade;

use Oak\Contracts\Cookie\CookieInterface;
use Oak\Facade;

/**
 * Cookie Facade providing static access to cookie functionality
 * 
 * @method static mixed set(string $name, mixed $value, int $expire = 0) Set a cookie value
 * @method static mixed get(string $name) Get a cookie value
 * @method static bool has(string $name) Check if a cookie exists
 */
class Cookie extends Facade
{
    /**
     * Get the service contract that this facade represents
     * 
     * @return class-string<CookieInterface>
     */
    protected static function getContract(): string
    {
        return CookieInterface::class;
    }
}