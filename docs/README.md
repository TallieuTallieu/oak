# Oak Documentation

Documentation for Oak, a simple PHP building block framework.

> [!info] Documentation Status
> These docs are being built up component by component. Sections without a link are not documented yet.

## Components

### 1. [Migration System](migration/README.md)

Versioned revisions per package or project, console commands, and cross-migrator ordering with `MigratorRevision`. Includes the [file-based revisions pattern](migration/file-based-revisions.md) used by dry projects.

### 2. Container

PSR-11 compatible dependency injection container.

### 3. Config

Configuration repository with dot-notation access.

### 4. Console

Command-line kernel with signature-based commands and sub-commands.

### 5. Http

PSR-7 messages and PSR-15 middleware.

### 6. Dispatcher

Event dispatching.

### 7. Filesystem

Filesystem abstraction.

### 8. Logger

Logging.

### 9. Scheduler

Cron-based task scheduling.

### 10. Session & Cookie

Session and cookie handling.

### 11. [Seeding](seeding/README.md)

Explicitly registered project seeders, manual execution, and integration with migration revisions. Projects control data handling and rollback.

## Using with Obsidian

This documentation is optimized for [Obsidian](https://obsidian.md/). To view it in your vault:

1. **Configure the sync path** in your `.env` file:

    ```bash
    OBSIDIAN_DOCS_PATH=/Users/username/Documents/Obsidian/MyVault/Oak
    ```

2. **Run the sync command**:

    ```bash
    make sync-docs
    ```

This copies all documentation files to your Obsidian vault, preserving the folder structure. The sync uses `rsync` with `--delete` to keep the vault in sync with the source documentation.

## Conventions

- **Service Providers**: components register through classes ending in `ServiceProvider`, implementing `register()` and `boot()`.
- **Contracts**: every component's interfaces live in `Oak\Contracts\<Component>`.
- **Facades**: static proxies live in `Facade/` subdirectories per component.
