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
     * @return mixed
     */
    public function addListener(string $eventName, callable $listener);

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
}
