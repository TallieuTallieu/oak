<?php

use Oak\Console\ConsoleServiceProvider;
use Oak\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Oak\Console\Input\ConsoleInput;
use Oak\Container\Container;
use Oak\Contracts\Console\KernelInterface;
use Oak\Contracts\Console\OutputInterface;
use Oak\Contracts\Migration\MigrationLoggerInterface;
use Oak\Contracts\Migration\RevisionInterface;
use Oak\Contracts\Migration\VersionStorageInterface;
use Oak\Contracts\Seeding\SeederInterface;
use Oak\Migration\Migrator;
use Oak\Seeding\Exception\UnknownSeederException;
use Oak\Seeding\SeederManager;
use Oak\Seeding\SeederRevision;
use Oak\Seeding\SeedingServiceProvider;

class SeedingTestState
{
    public int $constructions = 0;

    /** @var list<string> */
    public array $events = [];
}

class SeedingTestSeeder implements SeederInterface
{
    public function __construct(private SeedingTestState $state)
    {
        $this->state->constructions++;
    }

    public function seed(): void
    {
        $this->state->events[] = 'seed';
    }
}

class SeedingTestVersionStorage implements VersionStorageInterface
{
    public int $version = 0;

    public function get(Migrator $migrator): int
    {
        return $this->version;
    }

    public function store(Migrator $migrator, int $version): void
    {
        $this->version = $version;
    }
}

class SeedingTestOutput implements OutputInterface
{
    /** @var list<array{string, int}> */
    public array $lines = [];

    public function writeLine(
        string $message,
        int $type = self::TYPE_PLAIN,
    ): void {
        $this->lines[] = [$message, $type];
    }

    public function write(
        string $message,
        int $type = self::TYPE_PLAIN,
    ): void {}

    public function newline(): void {}
}

class SeedingTestMigrationLogger implements MigrationLoggerInterface
{
    /** @var list<RevisionInterface> */
    public array $updates = [];

    public function logUpdate(RevisionInterface $revision): void
    {
        $this->updates[] = $revision;
    }

    public function logDowndate(RevisionInterface $revision): void {}
}

/** @return array{Container, SeederManager, SeedingTestState} */
function seedingTestServices(): array
{
    $app = new Container();
    $state = new SeedingTestState();
    $app->instance(SeedingTestState::class, $state);
    new ConsoleServiceProvider()->register($app);
    $provider = new SeedingServiceProvider();
    $provider->register($app);
    $provider->boot($app);

    return [$app, $app->get(SeederManager::class), $state];
}

function seedingTestInput(string ...$arguments): ConsoleInput
{
    return new class (array_values($arguments)) extends ConsoleInput {
        /** @param list<string> $arguments */
        public function __construct(array $arguments)
        {
            $this->rawArguments = ['oak', ...$arguments];
        }
    };
}

test('registration is lazy and the provider shares one manager', function () {
    [$app, $manager, $state] = seedingTestServices();
    $manager->register('pages', SeedingTestSeeder::class);

    expect($app->get(SeederManager::class))->toBe($manager);
    expect($state->constructions)->toBe(0);
    expect($state->events)->toBe([]);

    $manager->run('pages');

    expect($state->constructions)->toBe(1);
    expect($state->events)->toBe(['seed']);
});

test(
    'only the selected seeder runs and manual execution is repeatable',
    function () {
        [, $manager, $state] = seedingTestServices();
        $otherState = new SeedingTestState();
        $manager->register('pages', SeedingTestSeeder::class);
        $manager->register('demo', new SeedingTestSeeder($otherState));

        $manager->run('pages');
        $manager->run('pages');

        expect($state->events)->toBe(['seed', 'seed']);
        expect($otherState->events)->toBe([]);

        $manager->run('demo');
        expect($otherState->events)->toBe(['seed']);
    },
);

test('unknown seeders fail instead of silently doing nothing', function () {
    [, $manager, $state] = seedingTestServices();
    $manager->register('pages', SeedingTestSeeder::class);

    expect(fn() => $manager->run('missing'))->toThrow(
        UnknownSeederException::class,
        "No seeder named 'missing' is registered",
    );
    expect($state->events)->toBe([]);
});

test(
    'registration rejects empty names and duplicate registrations',
    function () {
        [, $manager] = seedingTestServices();

        expect(
            fn() => $manager->register(' ', SeedingTestSeeder::class),
        )->toThrow(
            InvalidArgumentException::class,
            'A seeder name must not be empty',
        );
        $manager->register('pages', SeedingTestSeeder::class);
        expect(
            fn() => $manager->register('pages', SeedingTestSeeder::class),
        )->toThrow(
            InvalidArgumentException::class,
            "Seeder 'pages' is already registered",
        );
    },
);

test('the console executes an explicitly named seeder', function () {
    [$app, $manager, $state] = seedingTestServices();
    $manager->register('pages', SeedingTestSeeder::class);
    $output = new SeedingTestOutput();

    $app->get(KernelInterface::class)->handle(
        seedingTestInput('seed', 'pages'),
        $output,
    );

    expect($state->events)->toBe(['seed']);
    expect($output->lines)->toBe([
        ["Seeder 'pages' completed", OutputInterface::TYPE_INFO],
    ]);
});

test(
    'the console requires a seeder name and never runs all seeders',
    function () {
        [$app, $manager, $state] = seedingTestServices();
        $manager->register('pages', SeedingTestSeeder::class);
        $output = new SeedingTestOutput();

        expect(
            fn() => $app
                ->get(KernelInterface::class)
                ->handle(seedingTestInput('seed'), $output),
        )->toThrow(
            ConsoleInvalidArgumentException::class,
            'Missing argument(s) name',
        );
        expect($output->lines)->toBe([]);
        expect($state->events)->toBe([]);
    },
);

test('seeding help does not instantiate or execute seeders', function () {
    [$app, $manager, $state] = seedingTestServices();
    $manager->register('pages', SeedingTestSeeder::class);

    $app->get(KernelInterface::class)->handle(
        seedingTestInput('seed', '--help'),
        new SeedingTestOutput(),
    );

    expect($state->constructions)->toBe(0);
    expect($state->events)->toBe([]);
});

test(
    'the console rejects unknown seeders without reporting success',
    function () {
        [$app] = seedingTestServices();
        $output = new SeedingTestOutput();

        expect(
            fn() => $app
                ->get(KernelInterface::class)
                ->handle(seedingTestInput('seed', 'missing'), $output),
        )->toThrow(UnknownSeederException::class);
        expect($output->lines)->toBe([]);
    },
);

test(
    'seeder failures propagate through the console without reporting success',
    function () {
        [$app, $manager] = seedingTestServices();
        $manager->register(
            'broken',
            new class implements SeederInterface {
                public function seed(): void
                {
                    throw new RuntimeException('Seeding failed');
                }
            },
        );
        $output = new SeedingTestOutput();

        expect(
            fn() => $app
                ->get(KernelInterface::class)
                ->handle(seedingTestInput('seed', 'broken'), $output),
        )->toThrow(RuntimeException::class, 'Seeding failed');
        expect($output->lines)->toBe([]);
    },
);

test(
    'a seeding revision runs at its position and follows migration history only',
    function () {
        [$app, $manager, $state] = seedingTestServices();
        $storage = new SeedingTestVersionStorage();
        $migrator = new Migrator(
            'app',
            $storage,
            new SeedingTestMigrationLogger(),
            $app,
        );
        $revision = new SeederRevision($manager, 'pages');
        $manager->register('pages', SeedingTestSeeder::class);
        $before = new class ($state) implements RevisionInterface {
            public function __construct(private SeedingTestState $state) {}

            public function up(): void
            {
                $this->state->events[] = 'schema';
            }

            public function down(): void {}

            public function describeUp(): string
            {
                return 'Create schema';
            }

            public function describeDown(): string
            {
                return 'Leave schema unchanged';
            }
        };
        $migrator->setRevisions([$before, $revision]);

        $app->get(KernelInterface::class)->handle(
            seedingTestInput('seed', 'pages'),
            new SeedingTestOutput(),
        );
        expect($storage->version)->toBe(0);

        $migrator->migrate();
        $migrator->migrate();

        expect($state->events)->toBe(['seed', 'schema', 'seed']);
        expect($storage->version)->toBe(2);
        expect($revision->describeUp())->toBe("Run seeder 'pages'");

        $migrator->downdate();
        expect($storage->version)->toBe(1);
        expect($state->events)->toBe(['seed', 'schema', 'seed']);
        expect($revision->describeDown())->toBe(
            "Leave data seeded by 'pages' unchanged",
        );

        $migrator->migrate();
        $manager->run('pages');
        expect($state->events)->toBe([
            'seed',
            'schema',
            'seed',
            'seed',
            'seed',
        ]);
        expect($storage->version)->toBe(2);
    },
);

test('projects can supply their own rollback behavior', function () {
    [$app, $manager, $state] = seedingTestServices();
    $manager->register('pages', SeedingTestSeeder::class);
    $storage = new SeedingTestVersionStorage();
    $migrator = new Migrator(
        'app',
        $storage,
        new SeedingTestMigrationLogger(),
        $app,
    );
    $revision = new SeederRevision($manager, 'pages', function () use (
        $state,
    ): void {
        $state->events[] = 'project cleanup';
    });
    $migrator->setRevisions([$revision]);

    $migrator->migrate();
    $migrator->downdate();

    expect($state->events)->toBe(['seed', 'project cleanup']);
    expect($storage->version)->toBe(0);
    expect($revision->describeDown())->toBe("Roll back seeder 'pages'");
});

test(
    'a failed seeding revision does not advance migration history',
    function () {
        [$app, $manager] = seedingTestServices();
        $manager->register(
            'broken',
            new class implements SeederInterface {
                public function seed(): void
                {
                    throw new RuntimeException('Seeding failed');
                }
            },
        );
        $storage = new SeedingTestVersionStorage();
        $logger = new SeedingTestMigrationLogger();
        $migrator = new Migrator('app', $storage, $logger, $app);
        $migrator->setRevisions([new SeederRevision($manager, 'broken')]);

        expect(fn() => $migrator->migrate())->toThrow(
            RuntimeException::class,
            'Seeding failed',
        );
        expect($storage->version)->toBe(0);
        expect($logger->updates)->toBe([]);
    },
);

test(
    'failed project cleanup does not roll back migration history',
    function () {
        [$app, $manager] = seedingTestServices();
        $manager->register('pages', SeedingTestSeeder::class);
        $storage = new SeedingTestVersionStorage();
        $migrator = new Migrator(
            'app',
            $storage,
            new SeedingTestMigrationLogger(),
            $app,
        );
        $migrator->setRevisions([
            new SeederRevision($manager, 'pages', function (): void {
                throw new RuntimeException('Cleanup failed');
            }),
        ]);
        $migrator->migrate();

        expect(fn() => $migrator->downdate())->toThrow(
            RuntimeException::class,
            'Cleanup failed',
        );
        expect($storage->version)->toBe(1);
    },
);

test(
    'the manager can be registered outside console without a console kernel',
    function () {
        $app = new class extends Container {
            public function isRunningInConsole(): bool
            {
                return false;
            }
        };
        $provider = new SeedingServiceProvider();
        $provider->register($app);
        $provider->boot($app);

        expect($app->has(KernelInterface::class))->toBeFalse();
        expect($app->get(SeederManager::class))->toBeInstanceOf(
            SeederManager::class,
        );
    },
);
