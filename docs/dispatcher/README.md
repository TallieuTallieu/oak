# Dispatcher

Register listeners and dispatch events to them. An event is either an event object, dispatched under its class hierarchy, or a plain signal dispatched by name. Listeners run synchronously, in the order they were registered, when the event is dispatched.

## Setup

Register the provider:

```php
$app->register([
    \Oak\Dispatcher\DispatcherServiceProvider::class,
    // ...
]);
```

It binds `Oak\Contracts\Dispatcher\DispatcherInterface` as a singleton, so every part of the application shares the same listeners. Use the facade, or resolve the interface through the container or constructor injection:

```php
use Oak\Contracts\Dispatcher\DispatcherInterface;
use Oak\Dispatcher\Facade\Dispatcher;

Dispatcher::dispatch(new InvoiceSend($invoice));

$app->get(DispatcherInterface::class)->dispatch(new InvoiceSend($invoice));
```

The examples below use the facade. Register listeners in a service provider's `boot()` method, so they are in place before the application starts dispatching.

## Event objects

An event object implements `Oak\Contracts\Dispatcher\EventInterface`. Extend `Oak\Dispatcher\Event`, which implements it, and add whatever data the listeners need:

```php
use Oak\Dispatcher\Event;

class InvoiceSend extends Event
{
    public function __construct(private Invoice $invoice) {}

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
```

Listen by class name and dispatch the object itself:

```php
use Oak\Dispatcher\Facade\Dispatcher;

Dispatcher::addListener(InvoiceSend::class, function (InvoiceSend $event) {
    $mailer->send($event->getInvoice());
});

Dispatcher::dispatch(new InvoiceSend($invoice));
```

The object's class is the event name, so the name and the event cannot disagree: a listener registered for `InvoiceSend::class` is always handed an `InvoiceSend`.

### Class hierarchy

A dispatched event object reaches the listeners of:

1. its own class,
2. then its parent classes, from the nearest up,
3. then the interfaces it implements, in no guaranteed order.

This lets a listener subscribe to a whole family of events. A marker interface works well for this:

```php
interface BillingEvent extends EventInterface {}

class InvoiceSend extends Event implements BillingEvent
{
    /* ... */
}
class InvoicePaid extends Event implements BillingEvent
{
    /* ... */
}

// Runs for InvoiceSend and InvoicePaid
Dispatcher::addListener(BillingEvent::class, function (BillingEvent $event) {
    $audit->record($event);
});

// Runs for every event object
Dispatcher::addListener(EventInterface::class, function (
    EventInterface $event,
) {
    $logger->debug($event::class);
});
```

Only the hierarchy of the dispatched object is walked. Dispatching `InvoicePaid` does not reach the listeners of its sibling `InvoiceSend`.

### Dispatching by class name

`dispatch()` also accepts a class name followed by an event, as in `Dispatcher::dispatch(InvoiceSend::class, $event)`. That reaches only the listeners of that exact name, not those of parent classes or interfaces. Static analysis requires `$event` to be an `InvoiceSend`, but cannot tell when it is left out altogether: `Dispatcher::dispatch(InvoiceSend::class)` passes analysis and hands the listeners `null`. Dispatch the object instead.

An event object is always dispatched on its own. Passing a second event next to it, as in `Dispatcher::dispatch(new InvoiceSend($invoice), $other)`, throws an `InvalidArgumentException`.

## Signals

Any name that is not a class is a plain signal. Nothing is known about its event, so its listeners are handed whatever is dispatched, which may be nothing at all:

```php
Dispatcher::addListener('app.booted', function () {
    echo 'Booted!';
});

Dispatcher::dispatch('app.booted');
Dispatcher::dispatch('app.booted', new Event());
```

A signal listener that uses its event should type it as `?EventInterface` and handle `null`. Prefer an event class once listeners need data from the event.

## Stopping propagation

A listener can stop the listeners after it from running:

```php
Dispatcher::addListener(InvoiceSend::class, function (InvoiceSend $event) {
    if ($event->getInvoice()->isDraft()) {
        $event->stopPropagation();
    }
});
```

For an event object this spans the whole hierarchy: stopping propagation in a listener of `InvoiceSend` also skips the listeners of its parent classes and interfaces. A signal dispatched without an event cannot be stopped.

## Failing listeners

By default a listener that throws takes down every listener that would run after it, and the throwable surfaces at the `dispatch()` call. This quietly makes registration order load-bearing. Isolate a listener to prevent that:

```php
use Oak\Dispatcher\Facade\Dispatcher;
use Oak\Logger\Facade\Logger;

// Where throwables from isolated listeners go
Dispatcher::setExceptionHandler(function (
    Throwable $throwable,
    string $eventName,
    callable $listener,
) {
    Logger::log($eventName . ' listener failed: ' . $throwable->getMessage());
});

// A single listener that must not be able to break the event
Dispatcher::addListener(InvoicePaid::class, $sendConfirmationMail, true);

// ...or isolate every listener of one dispatch
Dispatcher::dispatchIsolated(new InvoicePaid($invoice));
```

A throwable from an isolated listener is handed to the exception handler and the remaining listeners still run. Nothing is swallowed: without an exception handler, the first throwable is re-thrown once every listener has had its turn.

The handler receives:

- `$throwable`: what the listener threw.
- `$eventName`: the name the failing listener was registered under. For an event object this can be one of its parent classes or interfaces.
- `$listener`: the failing listener, for reporting. It is typed `callable(never): void` because the handler cannot know which event it expects, so it cannot be called from the handler without static analysis complaining.

There is one exception handler per dispatcher. Setting a new one replaces the previous one, and `setExceptionHandler(null)` removes it.

## Inspecting listeners

```php
Dispatcher::hasListeners(InvoiceSend::class); // bool
Dispatcher::getListeners(InvoiceSend::class); // array of callables
```

Both look at the exact name only, not at the hierarchy a dispatched object would walk: `hasListeners(InvoiceSend::class)` is `false` when the only listener is registered for `BillingEvent::class`. `getListeners()` returns the registered callables, whether or not they were isolated.

Listeners cannot be removed once added.

## Static analysis

The dispatcher is generic over the event class, so PHPStan checks listeners and dispatches:

- A listener registered for an event class sees that class, even with an untyped parameter. Calling `$event->getInvoice()` needs no `instanceof` guard.
- Dispatching by an event class name requires an instance of that class as the event.
- Passing a second event next to an event object is rejected.
- A listener for a signal is typed `EventInterface`, the bound of the generic, even though it may be handed `null`. PHPStan cannot follow a conditional type into a closure's parameter, so type it `?EventInterface` yourself.

## API

| Method                                                                       | Description                                                                       |
| ---------------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| `addListener(string $eventName, callable $listener, bool $isolated = false)` | Registers a listener for an event class, interface or signal name.                |
| `dispatch($eventName, $event = null)`                                        | Dispatches an event object under its hierarchy, or a name with an optional event. |
| `dispatchIsolated($eventName, $event = null)`                                | Same as `dispatch()`, isolating every listener.                                   |
| `setExceptionHandler(?callable $handler)`                                    | Sets the handler for throwables raised by isolated listeners.                     |
| `getListeners(string $eventName): array`                                     | Returns the listeners registered under exactly this name.                         |
| `hasListeners(string $eventName): bool`                                      | Checks whether any listener is registered under exactly this name.                |

`EventInterface` requires `isPropagationStopped(): bool` and `stopPropagation()`.
