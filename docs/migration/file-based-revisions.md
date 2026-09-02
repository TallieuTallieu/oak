# File-Based Revisions in Projects

How dry projects organize their revisions on top of Oak's migration system, and how to interleave package migrations with `MigratorRevision`. This pattern is used across multiple projects (delhaize-nederename and others).

## Table of Contents

1. [The Pattern](#the-pattern)
2. [A Revision File](#a-revision-file)
3. [Ordering Rules](#ordering-rules)
4. [Package Migrators](#package-migrators)
5. [Pinning a Package Version](#pinning-a-package-version)
6. [Caveats](#caveats)

## The Pattern

Instead of listing revision classes in a service provider, the project keeps one file per revision in `app/revisions/`, prefixed with a date and timestamp so that an alphabetical sort is the execution order:

```
app/revisions/
    DatabaseRevision.php                                  <- shared base class, skipped by the loader
    0000_create_page_table.php
    2026_07_28_1785230332_create_product_category_table.php
    2026_08_03_1785745000_alter_product_vat_percentage_to_int.php
    ...
```

A project-level `MigrationServiceProvider` globs the directory (glob returns files sorted) and registers a single `app` migrator:

```php
namespace app\provider;

use Oak\Contracts\Container\ContainerInterface;
use Oak\Migration\MigrationManager;
use Oak\Migration\Migrator;
use Oak\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    public function boot(ContainerInterface $app)
    {
        if ($app->isRunningInConsole()) {
            $migrator = $app->getWith(Migrator::class, ['name' => 'app']);
            $migrator->setRevisions($this->getMigrations($app));

            $app->get(MigrationManager::class)->addMigrator($migrator);
        }
    }

    public function register(ContainerInterface $app) {}

    /** @return array<int, object> */
    public function getMigrations(ContainerInterface $app): array
    {
        // $manager is in scope for the required revision files
        $manager = $app->get(MigrationManager::class);

        $revisions = [];

        foreach (glob(\dry\Dry::$root . '/app/revisions/*.php') as $file) {
            if (basename($file) === 'DatabaseRevision.php') {
                continue;
            }

            $revisionInstance = require $file;

            if (is_object($revisionInstance)) {
                $revisions[] = $revisionInstance;
            }
        }

        return $revisions;
    }
}
```

## A Revision File

Each file **returns** an anonymous class implementing `RevisionInterface`, usually extending a shared `DatabaseRevision` base that wires up the query builder:

```php
<?php

namespace app\revisions;

use Oak\Contracts\Migration\RevisionInterface;
use Tnt\Dbi\TableBuilder;

return new class extends DatabaseRevision implements RevisionInterface {
    public function up(): void
    {
        $this->queryBuilder
            ->table('block')
            ->alter(function (TableBuilder $table): void {
                $table
                    ->addColumn('full_width', 'int')
                    ->length(1)
                    ->null()
                    ->default(0);
            });

        $this->execute();
    }

    public function down(): void
    {
        $this->queryBuilder
            ->table('block')
            ->alter(function (TableBuilder $table): void {
                $table->dropColumn('full_width');
            });

        $this->execute();
    }

    public function describeUp(): string
    {
        return 'Add full_width column to block table';
    }

    public function describeDown(): string
    {
        return 'Drop full_width column from block table';
    }
};
```

Because `require` runs inside `getMigrations()`, any variable defined there (like `$manager` above) is available inside the revision file.

## Ordering Rules

The filename **is** the ordering. The migrator's version counter is positional, so the same rules apply as with a hand-written list:

- **Append only.** New revisions get a timestamp later than every existing file.
- **Never rename** a revision file that has run somewhere — its position must not change.
- **Never delete** a revision file that has run somewhere; neutralize it in place if it must go.

## Package Migrators

Packages (dry-ecommerce, dry-accounts, ...) register their **own** migrators from their service providers, each with its own name (`ecommerce`, `account`) and version counter. Provider registration order determines migrator order, and in the typical `providers.inc.php` the project's migration provider comes **before** the package providers:

```php
$app->register([
    // ...
    provider\MigrationServiceProvider::class, // registers 'app'
    // ...
    \Tnt\Account\AccountServiceProvider::class, // registers 'account'
    \Tnt\Ecommerce\EcommerceServiceProvider::class, // registers 'ecommerce'
]);
```

> [!warning] Fresh-install pitfall
> With this order, a plain `migrate` runs **all** `app` revisions before any package revisions. An `app` revision that alters a package's table (say, adding a column to `ecommerce_order_item`) works fine on environments that migrated incrementally, but **fails on a fresh install** — the package table doesn't exist yet. This is exactly what `MigratorRevision` pins solve.

## Pinning a Package Version

The package's `Migrator` instance is created inside the vendor provider and is not available when the project's revision list is built (the vendor provider hasn't even booted yet). Use the **name-based** form, which resolves the migrator through the `MigrationManager` at the moment the revision runs:

```php
<?php
// app/revisions/2026_09_02_1788350000_ecommerce_to_v14.php

use Oak\Migration\MigratorRevision;

// Ecommerce tables must exist before the app revisions that follow this file.
return MigratorRevision::inManager($manager, 'ecommerce', 14);
```

The `$manager` variable comes from the provider's `getMigrations()` scope shown above. When the package later gains revisions the project depends on, add a new pin with the previous pin as its `fromVersion`:

```php
return MigratorRevision::inManager($manager, 'ecommerce', 16, 14);
```

On `migrate`, the pin fast-forwards `ecommerce` to the pinned version at exactly that point in the `app` sequence; the package's own auto-run afterwards picks up any newer, unpinned revisions. On `downdate`/`reset` of the `app` migrator, the pin rolls `ecommerce` back to its `fromVersion` at the same point in reverse.

> [!tip] Which version number to pin
> The pinned version is a position in the package's revision list — count the entries in the package's `setRevisions([...])` call, or run `migration list` on an up-to-date environment to read the package migrator's current version.

## Caveats

- **A pin never reruns.** Once applied, a `MigratorRevision` is behind the version counter like any revision. New package revisions need a new pin (or they run at the end via the package's own auto-run).
- **`reset` order.** Without `-m`, `reset` unwinds migrators in reverse registration order: package migrators reset **fully, first**, then the `app` migrator. An `app` revision whose `down()` touches a package table will then hit a table that's already gone. Prefer `migration reset -m app` when that matters, or accept that full `reset` is a destructive, dev-only operation.
- **Version storage keys on the migrator name.** All migrators (`app`, `ecommerce`, ...) share the JSON version file, one entry each.
