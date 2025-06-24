<?php

namespace Oak\Session\Facade;

use Oak\Facade;

/**
 * Session Facade providing static access to session functionality
 * 
 * @method static \SessionHandlerInterface getHandler() Get the session handler
 * @method static string getName() Get the session name
 * @method static mixed getId() Get the session ID
 * @method static void setId(mixed $id) Set the session ID
 * @method static void set(string $key, mixed $data) Set a session value
 * @method static mixed get(string $key) Get a session value
 * @method static bool has(string $key) Check if session has a key
 * @method static void save() Save the session data
 */
class Session extends Facade
{
    /**
     * Get the service contract that this facade represents
     * 
     * @return class-string<\Oak\Session\Session>
     */
    protected static function getContract(): string
    {
        return \Oak\Session\Session::class;
    }
}