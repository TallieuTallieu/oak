<?php

namespace Oak\Contracts\Dispatcher;

/**
 * Interface DispatcherInterface
 * @package Oak\Contracts\Dispatcher
 */
interface DispatcherInterface
{
    /**
     * @param string $eventName
     * @param callable(EventInterface|null): void $listener
     * @param bool $isolated Whether a throwable from this listener is kept from
     *                       taking down the rest of the event
     * @return mixed
     */
    public function addListener(
        string $eventName,
        callable $listener,
        bool $isolated = false
    );

    /**
     * @param (callable(\Throwable, string, callable(EventInterface|null): void): void)|null $handler
     * @return mixed
     */
    public function setExceptionHandler(?callable $handler);

    /**
     * @param string $eventName
     * @return array<int, callable(EventInterface|null): void>
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
        ?EventInterface $event = null
    );
}
