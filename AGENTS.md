# AGENTS.md - Oak Framework Development Guide

## Build/Test Commands

- **Format**: `yarn format` or `make yarn-format` (PHP files via @prettier/plugin-php)
- **Docker**: `make docker` (starts development environment), `make docker-exec` (shell access)
- **Tests**: `vendor/bin/pest` or `make test` (Pest, tests live in `tests/`)
- **Static analysis**: `composer phpstan` or `make phpstan` (PHPStan level 9, zero errors, no baseline — keep it that way)
- **CI**: PRs to master run tests (PHP 8.4), PHPStan, prettier check and `composer audit`; merges to master auto-tag a release (see `.github/workflows/`)

## Code Style Guidelines

- **PHP**: Minimum PHP 8.4, PSR-4 autoloading (`Oak\` → `src/`)
- **Indentation**: 2 spaces (editorconfig), 4 spaces in Prettier for PHP
- **Namespacing**: Follow `Oak\ComponentName\` pattern (e.g., `Oak\Console\`, `Oak\Http\`)
- **Classes**: PascalCase, prefer composition over inheritance
- **Properties**: camelCase with explicit visibility (`private`, `protected`, `public`)
- **Docblocks**: Required for classes and public methods with `@param`, `@return`, `@var`
- **Imports**: Use fully qualified names, group by vendor/local
- **Service Providers**: End with `ServiceProvider.php`, implement registration pattern
- **Facades**: Use static proxy pattern in `Facade/` subdirectories
- **Interfaces**: End with `Interface.php`, place in `Contracts/` namespace
- **Commands**: Extend abstract `Command` class, use signature-based CLI parsing
- **Error Handling**: Use specific exceptions in `Exception/` subdirectories

## Architecture Patterns

- **Container**: PSR-11 compatible dependency injection
- **HTTP**: PSR-7 messages, PSR-15 middleware
- **Components**: Modular service providers, optional registration
- **Configuration**: Dot-notation access (`config.key.subkey`)

## Shortcut Integration

- **Epic ID**: 8331 ("Oak" epic)
- **Team**: webdev (@webdev)
- **Default Workflow**: Website (ID: 500000005)
- When creating stories, assign to epic 8331 and use team "webdev"
- Prefix the title by 'oak: '
- Keep the title concise, the branch name will be inferred by the title

## Workflow

- **NEVER commit without asking** - Always request permission before committing changes
- **NEVER change story states** - Story state transitions happen automatically via git hooks
- **FORMAT before commit** - Format files before committing them, use prettier.
