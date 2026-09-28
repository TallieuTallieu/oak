<?php

namespace Oak\Dispatcher;

use Oak\Contracts\Dispatcher\DispatcherInterface;
use Oak\Contracts\Dispatcher\EventInterface;

/**
 * Class Dispatcher
 * @package Oak\Dispatcher
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * Registered listeners, each paired with whether it is isolated
     *
     * @var array<string, array<int, array{0: callable(EventInterface|null): void, 1: bool}>> $listeners
     */
    private $listeners = [];

    /**
     * Receives throwables caught while isolating a listener
     *
     * @var (callable(\Throwable, string, callable(never): void): void)|null $exceptionHandler
     */
    private $exceptionHandler = null;

    /**
     * Add a listener to an event by name
     *
     * Naming an event class binds the listener to that class, so it can be
     * typed for it instead of for every event. Dispatching the event object
     * itself, see {@see dispatch()}, is what guarantees the listener is handed
     * an instance of it. Any other name is a plain signal, for which no event
     * type is known and the listener is handed whatever is dispatched.
     *
     * An isolated listener cannot take the rest of the event down with it: a
     * throwable it raises is handed to the configured exception handler, and
     * every later listener still runs. Without a handler the throwable is
     * re-thrown once the event is finished, so a failure is never swallowed.
     *
     * @template TEvent of EventInterface
     * @param class-string<TEvent>|literal-string $eventName
     * @param callable(TEvent): void $listener
     * @param bool $isolated
     * @return void
     */
    public function addListener(
        string $eventName,
        callable $listener,
        bool $isolated = false,
    ) {
        if (!$this->hasListeners($eventName)) {
            $this->listeners[$eventName] = [];
        }

        /** @phpstan-ignore assign.propertyType */
        $this->listeners[$eventName][] = [$listener, $isolated];
    }

    /**
     * Sets the handler that receives throwables raised by isolated listeners
     *
     * The listener handed to the handler is typed for the event it was
     * registered for, which the handler has no way of knowing, so it is passed
     * as a callable the handler can report on but not call.
     *
     * @param (callable(\Throwable, string, callable(never): void): void)|null $handler
     * @return void
     */
    public function setExceptionHandler(?callable $handler)
    {
        $this->exceptionHandler = $handler;
    }

    /**
     * Gets the listeners of an event by name
     *
     * @template TEvent of EventInterface
     * @param class-string<TEvent>|literal-string $eventName
     * @return array<int, callable(TEvent): void>
     */
    public function getListeners(string $eventName): array
    {
        return array_map(
            /**
             * @param array{0: callable(EventInterface|null): void, 1: bool} $entry
             * @return callable(TEvent): void
             */
            function (array $entry) {
                return $entry[0];
            },
            $this->listeners[$eventName] ?? [],
        );
    }

    /**
     * Checks if there are listeners for event by name
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool
    {
        return (bool) count($this->listeners[$eventName] ?? []);
    }

    /**
     * Dispatches an event
     *
     * Pass the event object on its own to dispatch it under its class: the
     * listeners of that class run first, then those of its parent classes and
     * of the interfaces it implements, so the name and the event cannot
     * disagree. Pass a name, optionally with an event, to dispatch a signal.
     *
     * Dispatching by the name of an event class takes an instance of that
     * class as the event, as that is what its listeners are typed for.
     * Static analysis cannot tell when that event is left out altogether,
     * so its listeners would be handed null: dispatch the object instead.
     *
     * A listener that throws takes the event down with it unless it was
     * registered as isolated. Use {@see dispatchIsolated()} to isolate every
     * listener of a single dispatch instead.
     *
     * @template TEvent of EventInterface
     * @param EventInterface|class-string<TEvent>|literal-string $eventName
     * @param ($eventName is EventInterface ? null : ($eventName is class-string<TEvent> ? TEvent : EventInterface|null)) $event
     * @throws \InvalidArgumentException When an event object is combined with
     *                                   a second event
     */
    public function dispatch(
        string|EventInterface $eventName,
        ?EventInterface $event = null,
    ) {
        $this->call($eventName, $event, false);
    }

    /**
     * Dispatches an event, isolating every one of its listeners
     *
     * Takes the same arguments as {@see dispatch()}.
     *
     * @template TEvent of EventInterface
     * @param EventInterface|class-string<TEvent>|literal-string $eventName
     * @param ($eventName is EventInterface ? null : ($eventName is class-string<TEvent> ? TEvent : EventInterface|null)) $event
     * @throws \InvalidArgumentException When an event object is combined with
     *                                   a second event
     */
    public function dispatchIsolated(
        string|EventInterface $eventName,
        ?EventInterface $event = null,
    ) {
        $this->call($eventName, $event, true);
    }

    /**
     * Calls every listener for an event
     *
     * @param string|EventInterface $eventName
     * @param ?EventInterface $event
     * @param bool $isolateAll
     * @return void
     * @throws \InvalidArgumentException When an event object is combined with
     *                                   a second event
     * @throws \Throwable The first throwable raised by an isolated listener,
     *                    when no exception handler is configured
     */
    private function call(
        string|EventInterface $eventName,
        ?EventInterface $event,
        bool $isolateAll,
    ) {
        if ($eventName instanceof EventInterface) {
            if ($event !== null) {
                throw new \InvalidArgumentException(
                    'An event object is dispatched under its own class, it cannot be combined with a second event',
                );
            }

            $event = $eventName;
            $eventNames = $this->getEventNames($event);
        } else {
            $eventNames = [$eventName];
        }

        $unhandled = null;

        foreach ($eventNames as $name) {
            foreach ($this->listeners[$name] ?? [] as [$listener, $isolated]) {
                if (!$isolateAll && !$isolated) {
                    $listener($event);
                } else {
                    try {
                        $listener($event);
                    } catch (\Throwable $throwable) {
                        if ($this->exceptionHandler !== null) {
                            ($this->exceptionHandler)(
                                $throwable,
                                $name,
                                $listener,
                            );
                        } elseif ($unhandled === null) {
                            // Nowhere to report this, so keep it and let it surface
                            // once the remaining listeners have had their turn
                            $unhandled = $throwable;
                        }
                    }
                }

                // Stop calling the upcoming listeners if the propagation was stopped
                if ($event !== null && $event->isPropagationStopped()) {
                    break 2;
                }
            }
        }

        if ($unhandled !== null) {
            throw $unhandled;
        }
    }

    /**
     * Gets the names an event object is dispatched under
     *
     * Its own class comes first, then its parent classes from the nearest up,
     * then the interfaces it implements.
     *
     * @param EventInterface $event
     * @return array<int, string>
     */
    private function getEventNames(EventInterface $event): array
    {
        return [
            $event::class,
            ...array_values(class_parents($event)),
            ...array_values(class_implements($event)),
        ];
    }
}
