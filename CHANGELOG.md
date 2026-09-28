# Changelog

All notable changes to this package are documented in this file. Versions
follow [Semantic Versioning](https://semver.org). New entries are generated from
commit messages by [dry-ci](https://github.com/TallieuTallieu/dry-ci); past
entries may be edited by hand.

## 4.2.1 - 2026-09-28

No notable changes.

## 4.2.0 - 2026-09-28

### Breaking changes

- **dispatcher:** `DispatcherInterface::dispatch()` and `dispatchIsolated()` now accept `string|EventInterface` as the event name; custom implementations must widen `$eventName`

### Features

- **dispatcher:** Dispatch event objects under their class hierarchy

### Fixes

- **dispatcher:** Make addListener generic over the event class

### Other changes

- **dispatcher:** Document the event system

## 4.1.0 - 2026-09-25

### Features

- **seeding:** Add explicit seeder execution ([sc-11568](https://app.shortcut.com/tallieu--tallieu/story/11568))

### Other changes

- **seeding:** Document dry model seeders and dry project registration

## 4.0.1 - 2026-09-22

### Fixes

- **session:** Tolerate missing files during session rotation ([sc-11527](https://app.shortcut.com/tallieu--tallieu/story/11527))

## 4.0.0 - 2026-09-18

### Breaking changes

- **cookie:** `CookieInterface` gains `delete()`; custom implementations must add it
- **dispatcher:** `DispatcherInterface` gains `setExceptionHandler()`, `dispatchIsolated()` and an `$isolated` parameter on `addListener()`; custom implementations must add them
- **session:** The `StartSession` middleware's constructor now takes only the `Session`; code that builds it by hand must drop the config, cookie and session identifier arguments

### Features

- **cookie:** Support SameSite and cookie deletion ([sc-11445](https://app.shortcut.com/tallieu--tallieu/story/11445))
- **dispatcher:** Add opt-in listener isolation ([sc-11445](https://app.shortcut.com/tallieu--tallieu/story/11445))

### Fixes

- **session:** Add id rotation and reject unsafe session ids ([sc-11445](https://app.shortcut.com/tallieu--tallieu/story/11445))

### Other changes

- Document session rotation, SameSite and listener isolation

## Earlier history

- **1.0.0** (2019-09-19): First tagged release of Oak, a set of simple PHP building blocks: a service container with facades and service providers, console commands, filesystem, logger, cookie, session and event dispatcher. Config followed in 1.0.2.
- **1.0.5–1.0.8** (2019-10/11): Cookie security options and a database migration system with its console commands.
- **1.0.9–1.0.13** (2020-01): Container contextual bindings (`whenAsksGive`), default values for class dependencies and better resolving of primitive dependencies.
- **1.1** (2020-05-04): HTTP component (kernel, router, middleware on routes, response emitter, base controller), `.env` support with configurable application paths, and session middleware for session handling and garbage collection.
- **1.1.4** (2020-07-09): Scheduler for managing crontab and repeating tasks.
- **1.1.5–1.1.8** (2021-10 to 2024-04): Dependency maintenance (`dragonmantank/cron-expression` pinned to 3.0.2, `nyholm/psr7-server` updated) and fixes for PHP 8.2, including the deprecated `ReflectionClass::getClass()`.
- **1.1.9–1.1.12** (2025-06-24): Improved PHPDoc, a complete `ContainerInterface` (now including `isRunningInConsole()`) and `@method` annotations on the facades for IDE support.
- **3.0.0** (2025-06-25): Tagged for DRY3 from the same code as 1.1.12; there is no 2.x. From here 3.x is the PHP 8 line, while 1.x continued as a legacy PHP 7 line up to 1.1.15 (2025-07-03).
- **3.0.1–3.0.4** (2025-07 to 2025-09): Console `KernelInterface` mirrors the kernel, `FileSessionHandler` implements `SessionHandlerInterface` properly, and implicit nullable parameters are removed. 3.0.3 exists only on the `php8.2` branch (session deprecation fix and a `vlucas/phpdotenv` upgrade).
- **3.0.5** (2025-09-23): Requires PHP 8.2 or newer and moves to `vlucas/phpdotenv` 5; 3.0.6 and 3.0.7 fix `getenv()` support and empty session data.
- **3.0.8** (2025-09-29): `migration reset-counters` command.
- **3.0.9–3.0.12** (2025-10 to 2026-05): Type fixes on `ContainerInterface`, the Dispatcher facade and `register()`, and session saves guarded against a missing id.
- **3.0.13** (2026-09-02): `MigratorRevision` to interleave module revisions, migration docs, and `FileMigrationLogger` moved into the `Oak` namespace so it autoloads.
- **3.0.14** (2026-09-18): Requires PHP 8.4, moves to stable current dependencies, and adds DX tooling and CI.

See the git tags before 4.0.0 for the full history.
