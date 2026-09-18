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

test(
    'a throwing listener takes down the rest of the event by default',
    function () {
        $dispatcher = new Dispatcher();
        $calls = [];

        $dispatcher->addListener('created', function () {
            throw new RuntimeException('listener failed');
        });
        $dispatcher->addListener('created', function () use (&$calls) {
            $calls[] = 'second';
        });

        expect(fn() => $dispatcher->dispatch('created'))->toThrow(
            RuntimeException::class,
            'listener failed',
        );
        expect($calls)->toBe([]);
    },
);

test('an isolated listener does not stop the listeners after it', function () {
    $dispatcher = new Dispatcher();
    $calls = [];

    $dispatcher->setExceptionHandler(function () {});
    $dispatcher->addListener(
        'created',
        function () {
            throw new RuntimeException('listener failed');
        },
        true,
    );
    $dispatcher->addListener('created', function () use (&$calls) {
        $calls[] = 'second';
    });

    $dispatcher->dispatch('created');

    expect($calls)->toBe(['second']);
});

test(
    'the exception handler receives the throwable, event name and listener',
    function () {
        $dispatcher = new Dispatcher();
        $received = [];

        $failing = function () {
            throw new RuntimeException('listener failed');
        };

        $dispatcher->setExceptionHandler(function (
            Throwable $throwable,
            string $eventName,
            callable $listener,
        ) use (&$received) {
            $received = [$throwable->getMessage(), $eventName, $listener];
        });
        $dispatcher->addListener('created', $failing, true);

        $dispatcher->dispatch('created');

        expect($received)->toBe(['listener failed', 'created', $failing]);
    },
);

test('dispatchIsolated isolates every listener of the event', function () {
    $dispatcher = new Dispatcher();
    $calls = [];

    $dispatcher->setExceptionHandler(function () {});
    $dispatcher->addListener('created', function () {
        throw new RuntimeException('first failed');
    });
    $dispatcher->addListener('created', function () use (&$calls) {
        $calls[] = 'second';
    });
    $dispatcher->addListener('created', function () {
        throw new RuntimeException('third failed');
    });
    $dispatcher->addListener('created', function () use (&$calls) {
        $calls[] = 'fourth';
    });

    $dispatcher->dispatchIsolated('created');

    expect($calls)->toBe(['second', 'fourth']);
});

test(
    'without an exception handler an isolated failure surfaces after the event',
    function () {
        $dispatcher = new Dispatcher();
        $calls = [];

        $dispatcher->addListener('created', function () {
            throw new RuntimeException('first failed');
        });
        $dispatcher->addListener('created', function () use (&$calls) {
            $calls[] = 'second';
        });

        expect(fn() => $dispatcher->dispatchIsolated('created'))->toThrow(
            RuntimeException::class,
            'first failed',
        );
        expect($calls)->toBe(['second']);
    },
);

test(
    'getListeners still returns plain callables for isolated listeners',
    function () {
        $dispatcher = new Dispatcher();
        $listener = function () {};

        $dispatcher->addListener('created', $listener, true);

        expect($dispatcher->getListeners('created'))->toBe([$listener]);
    },
);
