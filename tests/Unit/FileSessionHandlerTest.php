<?php

use Oak\Contracts\Filesystem\FilesystemInterface;
use Oak\Filesystem\LocalFilesystem;
use Oak\Session\FileSessionHandler;

/**
 * Records every path the handler asks for, without touching the disk
 */
class FileSessionHandlerTestFilesystem implements FilesystemInterface
{
    /** @var array<string, string> */
    public array $files = [];

    /** @var array<int, string> */
    public array $touched = [];

    public function exists(string $path): bool
    {
        $this->touched[] = $path;

        return isset($this->files[$path]);
    }

    public function isWriteable(string $path): bool
    {
        return true;
    }

    public function isReadable(string $path): bool
    {
        return true;
    }

    public function size(string $path): int
    {
        return strlen($this->files[$path] ?? '');
    }

    public function mimetype(string $path): string
    {
        return 'text/plain';
    }

    public function modificationTime(string $path): int
    {
        return 0;
    }

    public function get(string $path)
    {
        $this->touched[] = $path;

        return $this->files[$path] ?? false;
    }

    public function put(string $path, $contents)
    {
        $this->touched[] = $path;
        $this->files[$path] = $contents;
    }

    public function prepend(string $path, $contents)
    {
        //
    }

    public function append(string $path, $contents)
    {
        //
    }

    public function files(string $path): array
    {
        return array_keys($this->files);
    }

    public function directories(string $path): array
    {
        return [];
    }

    public function delete(string $path)
    {
        $this->touched[] = $path;
        unset($this->files[$path]);
    }

    public function move(string $path, string $newPath)
    {
        //
    }

    public function copy(string $path, string $newPath)
    {
        //
    }
}

test('a valid session id round-trips through the handler', function () {
    $filesystem = new FileSessionHandlerTestFilesystem();
    $handler = new FileSessionHandler('/sessions', $filesystem);

    expect($handler->write('abc123', 'payload'))->toBeTrue();
    expect($filesystem->files['/sessions/abc123'])->toBe('payload');
    expect($handler->read('abc123'))->toBe('payload');
    expect($handler->destroy('abc123'))->toBeTrue();
    expect($filesystem->files)->not->toHaveKey('/sessions/abc123');
});

test('reading an unknown session id yields an empty string', function () {
    $handler = new FileSessionHandler(
        '/sessions',
        new FileSessionHandlerTestFilesystem(),
    );

    expect($handler->read('unknown'))->toBe('');
});

test('an unsafe session id never reaches the filesystem', function () {
    $unsafe = [
        'parent traversal' => '../../etc/passwd',
        'absolute path' => '/etc/passwd',
        'nested path' => 'a/b',
        'null byte' => "abc\0.php",
        'dot' => '.',
        'empty' => '',
        'dash' => 'abc-123',
    ];

    foreach ($unsafe as $label => $sessionId) {
        $filesystem = new FileSessionHandlerTestFilesystem();
        $handler = new FileSessionHandler('/sessions', $filesystem);

        expect($handler->read($sessionId))->toBe('', $label);
        expect($handler->write($sessionId, 'payload'))->toBeFalse($label);
        expect($handler->destroy($sessionId))->toBeFalse($label);

        expect($filesystem->touched)->toBe([], $label);
        expect($filesystem->files)->toBe([], $label);
    }
});

test(
    'destroy tolerates a file disappearing between checking and deleting',
    function () {
        $path = sys_get_temp_dir() . '/oak-session-' . bin2hex(random_bytes(8));
        mkdir($path);
        $filesystem = new class extends LocalFilesystem {
            public function delete(string $path)
            {
                // Simulate a concurrent collector removing the checked file.
                unlink($path);
                parent::delete($path);
            }
        };
        $handler = new FileSessionHandler($path, $filesystem);
        $handler->write('abc123', 'payload');

        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line,
        ): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            expect($handler->destroy('abc123'))->toBeTrue();
            expect(file_exists($path . '/abc123'))->toBeFalse();
            expect($handler->destroy('abc123'))->toBeTrue();
        } finally {
            restore_error_handler();
            if (file_exists($path . '/abc123')) {
                unlink($path . '/abc123');
            }
            rmdir($path);
        }
    },
);

test('destroy preserves real filesystem deletion errors', function () {
    $path = sys_get_temp_dir() . '/oak-session-' . bin2hex(random_bytes(8));
    mkdir($path);
    // A directory cannot be unlinked, even when the test runs as root.
    mkdir($path . '/abc123');
    $handler = new FileSessionHandler($path, new LocalFilesystem());

    set_error_handler(static function (
        int $severity,
        string $message,
        string $file,
        int $line,
    ): never {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    try {
        expect(fn() => $handler->destroy('abc123'))->toThrow(
            ErrorException::class,
        );
        expect(is_dir($path . '/abc123'))->toBeTrue();
    } finally {
        restore_error_handler();
        rmdir($path . '/abc123');
        rmdir($path);
    }
});
