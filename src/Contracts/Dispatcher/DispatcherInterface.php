<?php

namespace Oak\Contracts\Dispatcher;

/**
 * Interface DispatcherInterface
 * @package Oak\Contracts\Dispatcher
 */
interface DispatcherInterface
{
    /**
     * Registers a listener for an event
     *
     * Naming an event class binds the listener to that class: it is called
     * with an instance of it and with nothing else, so it can be typed for it.
     * Any other name is a plain signal, for which no event type is known.
     *
     * @template TEvent of EventInterface
     * @param class-string<TEvent>|literal-string $eventName
     * @param callable(TEvent): void $listener
     * @param bool $isolated Whether a throwable from this listener is kept from
     *                       taking down the rest of the event
     * @return mixed
     */
    public function addListener(
        string $eventName,
        callable $listener,
        bool $isolated = false,
    );

    /**
     * @param (callable(\Throwable, string, callable(never): void): void)|null $handler
     * @return mixed
     */
    public function setExceptionHandler(?callable $handler);

    /**
     * @template TEvent of EventInterface
     * @param class-string<TEvent>|literal-string $eventName
     * @return array<int, callable(TEvent): void>
     */
    public function getListeners(string $eventName): array;

    /**
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool;

    /**
     * @param string $eventName
     * @param EventInterface|null $event
     * @return mixed
     */
    public function dispatch(string $eventName, ?EventInterface $event = null);

    /**
     * @param string $eventName
     * @param EventInterface|null $event
     * @return mixed
     */
    public function dispatchIsolated(
        string $eventName,
        ?EventInterface $event = null,
    );
}
