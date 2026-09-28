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

class DispatcherTestInvoiceSend extends DispatcherTestEvent
{
    public function getInvoice(): string
    {
        return 'invoice';
    }
}

interface DispatcherTestBillingEvent extends EventInterface {}

class DispatcherTestInvoicePaid extends DispatcherTestEvent implements
    DispatcherTestBillingEvent {}

/**
 * Asserts, at analysis time, what a listener is handed
 *
 * Never called: PHPStan reads it, Pest does not run it. A listener registered
 * for an event class has to see that class, or the generic that promises it
 * has regressed and this stops being green.
 */
function dispatcherListenerTypeAssertions(Dispatcher $dispatcher): void
{
    $dispatcher->addListener(DispatcherTestInvoiceSend::class, function (
        $event,
    ) {
        \PHPStan\Testing\assertType('DispatcherTestInvoiceSend', $event);
        $event->getInvoice();
    });

    // A name that is not a class falls back to the bound of the generic, so
    // the listener is typed non-null even though a signal can be dispatched
    // without an event. PHPStan does not let a closure's parameter follow a
    // conditional type, so this cannot say EventInterface|null.
    $dispatcher->addListener('created', function ($event) {
        \PHPStan\Testing\assertType(
            'Oak\\Contracts\\Dispatcher\\EventInterface',
            $event,
        );
    });
}

/**
 * Asserts, at analysis time, which events a dispatch accepts
 *
 * Never called, like the listener assertions above. Each ignored error is one
 * PHPStan has to keep raising: once it stops, the ignore goes unmatched and
 * analysis fails.
 */
function dispatcherDispatchTypeAssertions(
    Dispatcher $dispatcher,
    EventInterface $event,
): void {
    // Dispatching under an event class takes an instance of it
    $dispatcher->dispatch(
        DispatcherTestInvoiceSend::class,
        // @phpstan-ignore argument.type
        new DispatcherTestInvoicePaid(),
    );
    $dispatcher->dispatchIsolated(
        DispatcherTestInvoiceSend::class,
        // @phpstan-ignore argument.type
        new DispatcherTestInvoicePaid(),
    );
    $dispatcher->dispatch(
        DispatcherTestEvent::class,
        new DispatcherTestInvoiceSend(),
    );

    // An event object is dispatched on its own
    // @phpstan-ignore argument.type
    $dispatcher->dispatch(new DispatcherTestInvoiceSend(), $event);

    // A signal takes any event, or none
    $dispatcher->dispatch('app.booted', $event);
    $dispatcher->dispatch('app.booted');
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

test(
    'a listener registered for an event class receives that class',
    function () {
        $dispatcher = new Dispatcher();
        $received = null;

        $dispatcher->addListener(DispatcherTestInvoiceSend::class, function (
            DispatcherTestInvoiceSend $event,
        ) use (&$received) {
            $received = $event->getInvoice();
        });

        $dispatcher->dispatch(new DispatcherTestInvoiceSend());

        expect($received)->toBe('invoice');
    },
);

test(
    'a listener registered for an event class also receives a subclass',
    function () {
        $dispatcher = new Dispatcher();
        $received = null;

        $dispatcher->addListener(DispatcherTestEvent::class, function (
            DispatcherTestEvent $event,
        ) use (&$received) {
            $received = $event;
        });

        $event = new DispatcherTestInvoiceSend();
        $dispatcher->dispatch($event);

        expect($received)->toBe($event);
    },
);

test(
    'a signal name still receives whatever is dispatched under it',
    function () {
        $dispatcher = new Dispatcher();
        $received = [];

        $dispatcher->addListener('app.booted', function ($event) use (
            &$received,
        ) {
            $received[] = $event;
        });

        $dispatcher->dispatch('app.booted');
        $dispatcher->dispatch('app.booted', new DispatcherTestEvent());

        expect($received)->toHaveCount(2);
        expect($received[0])->toBeNull();
        expect($received[1])->toBeInstanceOf(DispatcherTestEvent::class);
    },
);

test('an interface name matches every event implementing it', function () {
    $dispatcher = new Dispatcher();
    $calls = 0;

    $dispatcher->addListener(EventInterface::class, function () use (&$calls) {
        $calls++;
    });

    $dispatcher->dispatch(EventInterface::class, new DispatcherTestEvent());

    expect($calls)->toBe(1);
});

test('dispatching an event object uses its class as the name', function () {
    $dispatcher = new Dispatcher();
    $received = null;

    $dispatcher->addListener(DispatcherTestInvoiceSend::class, function (
        DispatcherTestInvoiceSend $event,
    ) use (&$received) {
        $received = $event;
    });

    $event = new DispatcherTestInvoiceSend();
    $dispatcher->dispatch($event);

    expect($received)->toBe($event);
});

test(
    'dispatching an event object reaches its parent classes and interfaces',
    function () {
        $dispatcher = new Dispatcher();
        $calls = [];

        $dispatcher->addListener(EventInterface::class, function () use (
            &$calls,
        ) {
            $calls[] = 'event interface';
        });
        $dispatcher->addListener(
            DispatcherTestBillingEvent::class,
            function () use (&$calls) {
                $calls[] = 'billing interface';
            },
        );
        $dispatcher->addListener(DispatcherTestEvent::class, function () use (
            &$calls,
        ) {
            $calls[] = 'parent';
        });
        $dispatcher->addListener(
            DispatcherTestInvoicePaid::class,
            function () use (&$calls) {
                $calls[] = 'own class';
            },
        );

        $dispatcher->dispatch(new DispatcherTestInvoicePaid());

        expect($calls[0])->toBe('own class');
        expect($calls[1])->toBe('parent');
        expect(array_slice($calls, 2))->toEqualCanonicalizing([
            'event interface',
            'billing interface',
        ]);
    },
);

test('dispatching an event object leaves sibling classes alone', function () {
    $dispatcher = new Dispatcher();
    $calls = 0;

    $dispatcher->addListener(DispatcherTestInvoiceSend::class, function () use (
        &$calls,
    ) {
        $calls++;
    });

    $dispatcher->dispatch(new DispatcherTestInvoicePaid());

    expect($calls)->toBe(0);
});

test(
    'stopping propagation also halts the listeners of parent classes',
    function () {
        $dispatcher = new Dispatcher();
        $calls = 0;

        $dispatcher->addListener(DispatcherTestInvoiceSend::class, function (
            DispatcherTestInvoiceSend $event,
        ) {
            $event->stopPropagation();
        });
        $dispatcher->addListener(DispatcherTestEvent::class, function () use (
            &$calls,
        ) {
            $calls++;
        });

        $dispatcher->dispatch(new DispatcherTestInvoiceSend());

        expect($calls)->toBe(0);
    },
);

test(
    'the exception handler receives the name the listener was registered under',
    function () {
        $dispatcher = new Dispatcher();
        $names = [];

        $dispatcher->setExceptionHandler(function (
            \Throwable $throwable,
            string $eventName,
        ) use (&$names) {
            $names[] = $eventName;
        });
        $dispatcher->addListener(
            DispatcherTestEvent::class,
            function () {
                throw new \RuntimeException('failed');
            },
            true,
        );

        $dispatcher->dispatch(new DispatcherTestInvoiceSend());

        expect($names)->toBe([DispatcherTestEvent::class]);
    },
);

test('dispatchIsolated accepts an event object as well', function () {
    $dispatcher = new Dispatcher();
    $calls = 0;

    $dispatcher->addListener(DispatcherTestInvoiceSend::class, function () {
        throw new \RuntimeException('failed');
    });
    $dispatcher->addListener(DispatcherTestEvent::class, function () use (
        &$calls,
    ) {
        $calls++;
    });
    $dispatcher->setExceptionHandler(function () {});

    $dispatcher->dispatchIsolated(new DispatcherTestInvoiceSend());

    expect($calls)->toBe(1);
});

test('an event object cannot be combined with a second event', function () {
    $dispatcher = new Dispatcher();

    // Static analysis rejects this, the exception covers callers without it
    $dispatcher->dispatch(
        new DispatcherTestInvoiceSend(),
        // @phpstan-ignore argument.type
        new DispatcherTestEvent(),
    );
})->throws(\InvalidArgumentException::class);

test(
    'dispatching by name still reaches only the listeners of that name',
    function () {
        $dispatcher = new Dispatcher();
        $calls = 0;

        $dispatcher->addListener(DispatcherTestEvent::class, function () use (
            &$calls,
        ) {
            $calls++;
        });

        $dispatcher->dispatch(
            DispatcherTestInvoiceSend::class,
            new DispatcherTestInvoiceSend(),
        );

        expect($calls)->toBe(0);
    },
);
