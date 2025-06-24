<?php

namespace Oak\Dispatcher\Facade;

use Oak\Contracts\Dispatcher\DispatcherInterface;
use Oak\Facade;

/**
 * Dispatcher Facade providing static access to event dispatching functionality
 * 
 * @method static mixed addListener(string $eventName, callable $listener) Add an event listener
 * @method static array getListeners(string $eventName) Get all listeners for an event
 * @method static bool hasListeners(string $eventName) Check if an event has listeners
 * @method static mixed dispatch(string $eventName, EventInterface|null $event = null) Dispatch an event to listeners
 */
class Dispatcher extends Facade
{
    /**
     * Get the service contract that this facade represents
     * 
     * @return class-string<DispatcherInterface>
     */
    protected static function getContract(): string
    {
        return DispatcherInterface::class;
    }
}