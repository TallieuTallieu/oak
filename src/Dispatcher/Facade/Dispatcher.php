<?php

namespace Oak\Dispatcher\Facade;

use Oak\Contracts\Dispatcher\DispatcherInterface;
use Oak\Facade;

/**
 * Dispatcher Facade providing static access to event dispatching functionality
 *
 * @method static mixed addListener<TEvent of \Oak\Contracts\Dispatcher\EventInterface>(class-string<TEvent>|literal-string $eventName, callable(TEvent): void $listener, bool $isolated = false) Add an event listener
 * @method static mixed setExceptionHandler((callable(\Throwable, string, callable(never): void): void)|null $handler) Set the handler for throwables raised by isolated listeners
 * @method static array<int, callable(TEvent): void> getListeners<TEvent of \Oak\Contracts\Dispatcher\EventInterface>(class-string<TEvent>|literal-string $eventName) Get all listeners for an event
 * @method static bool hasListeners(string $eventName) Check if an event has listeners
 * @method static mixed dispatch(string $eventName, \Oak\Contracts\Dispatcher\EventInterface|null $event = null) Dispatch an event to listeners
 * @method static mixed dispatchIsolated(string $eventName, \Oak\Contracts\Dispatcher\EventInterface|null $event = null) Dispatch an event, isolating every listener
 *
 * @extends Facade<DispatcherInterface>
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
