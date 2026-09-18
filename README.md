<img src="https://raw.githubusercontent.com/reinvanoyen/oak/master/oak-logo.png" />

### Simple PHP building blocks framework

* [Config](#config)
* [Console](#console)
* [Container](#container)
* [Dispatcher](#dispatcher)
* [Filesystem](#filesystem)
* [Logger](#logger)
* Scheduler
* [Session](#session)

#### Install
```ssh
composer require tallieutallieu/oak
```

#### Creating an application

```php
<?php

$app = new \Oak\Application(
    __DIR__.'/../', // The path to your .env file
    __DIR__.'/../config/', // The path to your config files
    __DIR__.'/../cache/' // The path where the application can write cache to 
);

$app->register([
    \Oak\Console\ConsoleServiceProvider::class,
]);

$app->bootstrap();
```

The example above only registers the Console component. This is an easy example since the Console component doesn't 
depend on any other components. To run the Console component, you'll have to get the Console\Kernel from your 
application handle the incoming Input:

```php
<?php

use Oak\Contracts\Console\InputInterface;
use Oak\Contracts\Console\OutputInterface;
use Oak\Contracts\Console\KernelInterface;

$app->get(KernelInterface::class)->handle(
    $app->get(InputInterface::class),
    $app->get(OutputInterface::class)
);
```

To use the HTTP component (PSR-7 & PSR-15 compliant) you'll also have to register the Config component...and since the Config component reads 
configuration values from the filesystem, you'll also have to register the Filesystem component:

```php
<?php

$app->register([
    \Oak\Console\ConsoleServiceProvider::class,
    \Oak\Http\HttpServiceProvider::class,
    \Oak\Config\ConfigServiceProvider::class,
    \Oak\Filesystem\FilesystemServiceProvider::class,
]);
```

Handling an incoming request with the Http\Kernel goes as follows:

```php
<?php

use Oak\Contracts\Http\KernelInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->get(KernelInterface::class)->handle(
    $app->get(ServerRequestInterface::class)
);
```

#### Config

```php
<?php

$config->set('package', [
  'client_id' => '123',
  'client_secret' => 'F1jK4s5mPs9s1_sd1wpalnbs5H1',
]);

echo $config->get('package.client_secret'); // F1jK4s5mPs9s1_sd1wpalnbs5H1
```

##### Config commands
```ssh
php oak config clear-cache
```
```ssh
php oak config cache
```

#### Console

Documentation coming soon

#### Container

Documentation coming soon

#### Cookie

##### Example usage

```php
<?php

use Oak\Cookie\Facade\Cookie;

Cookie::set('key', 'value');

echo Cookie::get('key'); // value

Cookie::delete('key');
```

##### Cookie config options

Name | Default
---- | -------
path | /
secure | false
http_only | true
same_site | Lax

`same_site` accepts `Lax`, `Strict` or `None`. `None` is only valid together
with `secure`; combining it with an insecure cookie throws instead of letting
the browser silently drop the cookie.

> **`secure` defaults to `false`.** Oak cannot know whether the host serves over
> TLS, so it does not assume it — which means a project that never writes a
> `config/cookie.php` ships its session cookie over plain HTTP. Set
> `cookie.secure` to `true` in every project that has TLS.

#### Dispatcher

```php
<?php

use Oak\Dispatcher\Facade\Dispatcher;

Dispatcher::addListener('created', function($event) {
  echo 'Creation happened!';
});

Dispatcher::dispatch('created', new Event());

```

##### Isolating listeners

By default a listener that throws takes down every listener registered after it
and the throwable surfaces at the `dispatch()` call, which quietly makes
registration order load-bearing. A listener can opt out of that:

```php
<?php

use Oak\Dispatcher\Facade\Dispatcher;
use Oak\Logger\Facade\Logger;

// Where throwables from isolated listeners go
Dispatcher::setExceptionHandler(function (Throwable $throwable, string $eventName, callable $listener) {
    Logger::log($eventName . ' listener failed: ' . $throwable->getMessage());
});

// A single listener that must not be able to break the event
Dispatcher::addListener('order.paid', $sendConfirmationMail, true);

// ...or isolate every listener of one dispatch
Dispatcher::dispatchIsolated('order.paid', new Event());
```

Nothing is swallowed: if no exception handler is configured, the remaining
listeners still run and the first throwable is re-thrown once the event is
finished.

#### Filesystem

Documentation coming soon

#### Logger

##### Example usage

```php
<?php

use Oak\Logger\Facade\Logger;

Logger::log('This message will be logged');
```

##### Logger config options

Name | Default
---- | -------
filename | logs/log.txt
date_format | d/m/Y H:i

#### Session

##### Example usage

```php
<?php

use Oak\Session\Facade\Session;

Session::set('key', 'value');
Session::save();

echo Session::get('key'); // value
```

##### Rotating and clearing a session

Rotate the session id whenever the privilege level of the session changes — on
login above all — so that an id an attacker planted beforehand is worthless
afterwards. `regenerate()` mints a new id, carries the data over, drops the old
handler entry and rewrites the cookie:

```php
<?php

use Oak\Session\Facade\Session;

// After authenticating, before writing the user onto the session
Session::regenerate();
Session::set('user_id', $user->id);
Session::save();

// On logout
Session::destroy();
```

##### Session config options

Name | Default
---- | -------
handler | \Oak\Session\FileSessionHandler
path | sessions
name | app
cookie_prefix | session
identifier_length | 40
lottery | 200
max_lifetime | 1000
