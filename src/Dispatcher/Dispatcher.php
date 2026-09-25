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
     * Naming an event class binds the listener to that class: {@see dispatch()}
     * only hands it an instance of it, so it can be typed for that class
     * instead of for every event. Any other name is a plain signal, for which
     * no event type is known and the listener is handed whatever is dispatched.
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
     * Dispatches an event by name
     *
     * A listener that throws takes the event down with it unless it was
     * registered as isolated. Use {@see dispatchIsolated()} to isolate every
     * listener of a single dispatch instead.
     *
     * @param string $eventName
     * @param ?EventInterface $event
     */
    public function dispatch(string $eventName, ?EventInterface $event = null)
    {
        $this->call($eventName, $event, false);
    }

    /**
     * Dispatches an event by name, isolating every one of its listeners
     *
     * @param string $eventName
     * @param ?EventInterface $event
     */
    public function dispatchIsolated(
        string $eventName,
        ?EventInterface $event = null,
    ) {
        $this->call($eventName, $event, true);
    }

    /**
     * Calls every listener for an event
     *
     * @param string $eventName
     * @param ?EventInterface $event
     * @param bool $isolateAll
     * @return void
     * @throws \Throwable The first throwable raised by an isolated listener,
     *                    when no exception handler is configured
     */
    private function call(
        string $eventName,
        ?EventInterface $event,
        bool $isolateAll,
    ) {
        if (!$this->eventBelongsTo($eventName, $event)) {
            return;
        }

        $unhandled = null;

        foreach ($this->listeners[$eventName] ?? [] as [$listener, $isolated]) {
            if (!$isolateAll && !$isolated) {
                $listener($event);
            } else {
                try {
                    $listener($event);
                } catch (\Throwable $throwable) {
                    if ($this->exceptionHandler !== null) {
                        ($this->exceptionHandler)(
                            $throwable,
                            $eventName,
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
                break;
            }
        }

        if ($unhandled !== null) {
            throw $unhandled;
        }
    }

    /**
     * Whether a dispatched event is the one an event name stands for
     *
     * An event name that is a class or an interface is a promise to the
     * listeners registered under it: they are typed for that class, so an event
     * that is not an instance of it is not theirs to receive and the dispatch
     * passes them by. Every other name is a plain signal that promises nothing
     * about the event, so anything dispatched under it reaches its listeners.
     *
     * @param string $eventName
     * @param ?EventInterface $event
     * @return bool
     */
    private function eventBelongsTo(
        string $eventName,
        ?EventInterface $event,
    ): bool {
        if (!class_exists($eventName) && !interface_exists($eventName)) {
            return true;
        }

        return $event instanceof $eventName;
    }
}
