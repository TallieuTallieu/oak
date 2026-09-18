<?php

use Oak\Contracts\Dispatcher\EventInterface;
use Oak\Dispatcher\Dispatcher;

class DispatcherTestEvent implements EventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}

test('dispatch calls every listener with the event', function () {
    $dispatcher = new Dispatcher();
    $received = [];

    $dispatcher->addListener('created', function ($event) use (&$received) {
        $received[] = $event;
    });
    $dispatcher->addListener('created', function ($event) use (&$received) {
        $received[] = $event;
    });

    $event = new DispatcherTestEvent();
    $dispatcher->dispatch('created', $event);

    expect($received)->toBe([$event, $event]);
});

test('dispatch without an event does not crash listeners', function () {
    $dispatcher = new Dispatcher();
    $calls = 0;

    $dispatcher->addListener('created', function () use (&$calls) {
        $calls++;
    });
    $dispatcher->addListener('created', function () use (&$calls) {
        $calls++;
    });

    $dispatcher->dispatch('created');

    expect($calls)->toBe(2);
});

test('stopping propagation halts later listeners', function () {
    $dispatcher = new Dispatcher();
    $calls = [];

    $dispatcher->addListener('created', function (?EventInterface $event) use (
        &$calls,
    ) {
        $calls[] = 'first';
        $event?->stopPropagation();
    });
    $dispatcher->addListener('created', function () use (&$calls) {
        $calls[] = 'second';
    });

    $dispatcher->dispatch('created', new DispatcherTestEvent());

    expect($calls)->toBe(['first']);
});

test('hasListeners reflects registered listeners', function () {
    $dispatcher = new Dispatcher();

    expect($dispatcher->hasListeners('created'))->toBeFalse();

    $dispatcher->addListener('created', function () {});

    expect($dispatcher->hasListeners('created'))->toBeTrue();
});
