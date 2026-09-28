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
     * Naming an event class binds the listener to that class, so it can be
     * typed for it. Dispatch the event object itself to guarantee the listener
     * is handed an instance of it. Any other name is a plain signal, for which
     * no event type is known.
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
     * Dispatches an event object under its class, parent classes and
     * interfaces, or a signal by name
     *
     * Dispatching by the name of an event class takes an instance of that
     * class as the event, as that is what its listeners are typed for.
     * Static analysis cannot tell when that event is left out altogether,
     * so its listeners would be handed null: dispatch the object instead.
     *
     * @template TEvent of EventInterface
     * @param EventInterface|class-string<TEvent>|literal-string $eventName
     * @param ($eventName is EventInterface ? null : ($eventName is class-string<TEvent> ? TEvent : EventInterface|null)) $event
     * @return mixed
     * @throws \InvalidArgumentException When an event object is combined with
     *                                   a second event
     */
    public function dispatch(
        string|EventInterface $eventName,
        ?EventInterface $event = null,
    );

    /**
     * Same as dispatch(), isolating every listener of the event
     *
     * @template TEvent of EventInterface
     * @param EventInterface|class-string<TEvent>|literal-string $eventName
     * @param ($eventName is EventInterface ? null : ($eventName is class-string<TEvent> ? TEvent : EventInterface|null)) $event
     * @return mixed
     * @throws \InvalidArgumentException When an event object is combined with
     *                                   a second event
     */
    public function dispatchIsolated(
        string|EventInterface $eventName,
        ?EventInterface $event = null,
    );
}
