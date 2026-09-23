# Seeding

Define reusable project seeders and run each explicitly, either from the console or at a chosen point in a migration sequence. Oak does not depend on a database library or dry.

## Define a seeder

Implement `Oak\Contracts\Seeding\SeederInterface` with a `seed(): void` method. Constructor dependencies are resolved through Oak's container when the seeder runs.

For example, a project with a `country` table and a configured `PDO` binding could define `app/seeders/CountriesSeeder.php`:

```php
<?php

namespace app\seeders;

use Oak\Contracts\Seeding\SeederInterface;
use PDO;

class CountriesSeeder implements SeederInterface
{
    public function __construct(private PDO $db) {}

    public function seed(): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO country (code, name)
             SELECT ?, ?
             WHERE NOT EXISTS (SELECT 1 FROM country WHERE code = ?)',
        );
        $statement->execute(['BE', 'Belgium', 'BE']);
    }
}
```

Bind the project's database connection with `$app->instance(PDO::class, $connection)` and configure it to throw on errors (`PDO::ERRMODE_EXCEPTION`). The example preserves an existing row; adapt the SQL and conflict handling to your database. Seeders can just as well use project repositories, dry models, or another storage library.

## Register seeders

Register the console provider and the seeding provider before your project's provider:

```php
$app->register([
    \Oak\Console\ConsoleServiceProvider::class,
    \Oak\Seeding\SeedingServiceProvider::class,
    \app\provider\AppServiceProvider::class,
]);
```

Add named seeders in your project provider's `boot()` method:

```php
use app\seeders\CountriesSeeder;
use app\seeders\DemoCatalogSeeder;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Seeding\SeederManager;

public function boot(ContainerInterface $app): void
{
    $seeders = $app->get(SeederManager::class);
    $seeders->register('countries', CountriesSeeder::class);
    $seeders->register('demo-catalog', DemoCatalogSeeder::class);
}
```

`DemoCatalogSeeder` represents another project-defined implementation. `register()` also accepts a `SeederInterface` instance instead of a class name. Names must be non-empty and unique. Registering a class does not construct or execute it; the container resolves it at execution time, respecting your normal bindings and singleton configuration.

There is no automatic directory discovery, run-all operation, or automatic execution during bootstrap or deployment. Registration alone never seeds data. No seeding config file or migration provider is required for manual execution.

## Execute manually

```bash
php oak seed countries
php oak seed demo-catalog
php oak seed --help
```

The name is required. Unknown names throw `UnknownSeederException`. A seeder failure propagates to the caller; the command only reports completion after `seed()` returns successfully.

For programmatic execution:

```php
$app->get(\Oak\Seeding\SeederManager::class)->run('countries');
```

Every manual call executes the seeder again. It neither reads nor writes migration history and does not suppress execution by a later revision. The project decides whether repeated execution skips, updates, duplicates, or rejects existing data.

## Execute from a revision

Add a `SeederRevision` exactly where you want seeding to happen, after any required schema revisions:

```php
use Oak\Seeding\SeederManager;
use Oak\Seeding\SeederRevision;

$migrator->setRevisions([
    // Project schema revisions go here.
    new SeederRevision($app->get(SeederManager::class), 'countries'),
]);
```

For the [file-based revision pattern](../migration/file-based-revisions.md), append a new file to `app/revisions/`. The `$app` variable is available from the project loader's `getMigrations(ContainerInterface $app)` scope:

```php
<?php

use Oak\Seeding\SeederManager;
use Oak\Seeding\SeederRevision;

return new SeederRevision($app->get(SeederManager::class), 'countries');
```

Seeder lookup happens in `up()`, not while loading the revision file. This allows your project provider to register seeders later in bootstrap, before migrations execute.

The normal migration counter tracks this revision. Subsequent migrations skip an already applied seeding revision. If seeding throws, the counter does not advance. Oak adds no separate seeding history or transaction management; partially written data and safe retries are the project's responsibility.

You can also call `SeederManager::run()` inside an existing project revision's `up()` when schema changes and seeding belong in the same revision.

## Rollback and data changes

`SeederRevision::down()` does nothing by default. The migration counter still moves back, so migrating forward again executes the seeder again.

A project can optionally supply its own rollback closure:

```php
return new SeederRevision(
    $app->get(SeederManager::class),
    'countries',
    rollback: function () use ($app): void {
        // Perform only the cleanup this project explicitly chooses.
    },
);
```

Alternatively, use your own revision and implement its `down()` however the project requires. There is no requirement for a seeder to be reversible, and Oak never automatically deletes seeded data. A rollback closure that throws leaves the migration counter unchanged.

Seeder evolution, dataset changes, preservation of editor changes, and the choice of production versus demo data are all project concerns. Oak imposes no immutability, snapshotting, or dataset versioning policy.

## See also

- [Migration system](../migration/README.md)
- [File-based revisions](../migration/file-based-revisions.md)
