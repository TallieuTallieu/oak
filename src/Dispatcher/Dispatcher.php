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
     * @var (callable(\Throwable, string, callable(EventInterface|null): void): void)|null $exceptionHandler
     */
    private $exceptionHandler = null;

    /**
     * Add a listener to an event by name
     *
     * An isolated listener cannot take the rest of the event down with it: a
     * throwable it raises is handed to the configured exception handler, and
     * every later listener still runs. Without a handler the throwable is
     * re-thrown once the event is finished, so a failure is never swallowed.
     *
     * @param string $eventName
     * @param callable(EventInterface|null): void $listener
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

        $this->listeners[$eventName][] = [$listener, $isolated];
    }

    /**
     * Sets the handler that receives throwables raised by isolated listeners
     *
     * @param (callable(\Throwable, string, callable(EventInterface|null): void): void)|null $handler
     * @return void
     */
    public function setExceptionHandler(?callable $handler)
    {
        $this->exceptionHandler = $handler;
    }

    /**
     * Gets the listeners of an event by name
     *
     * @param string $eventName
     * @return array<int, callable(EventInterface|null): void>
     */
    public function getListeners(string $eventName): array
    {
        return array_map(
            /**
             * @param array{0: callable(EventInterface|null): void, 1: bool} $entry
             * @return callable(EventInterface|null): void
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
        return (bool) count($this->getListeners($eventName));
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
}
