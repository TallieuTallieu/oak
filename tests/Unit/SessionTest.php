<?php

use Oak\Contracts\Cookie\CookieInterface;
use Oak\Contracts\Session\SessionIdentifierInterface;
use Oak\Session\Session;

/**
 * Keeps session data in memory so the tests never touch the filesystem
 */
class SessionTestHandler implements SessionHandlerInterface
{
    /** @var array<string, string> */
    public array $storage = [];

    /** @var array<int, string> */
    public array $destroyed = [];

    public function close(): bool
    {
        return true;
    }

    public function destroy($id): bool
    {
        $this->destroyed[] = $id;
        unset($this->storage[$id]);

        return true;
    }

    public function gc(int $max_lifetime): int
    {
        return 0;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read($id): string
    {
        return $this->storage[$id] ?? '';
    }

    public function write($id, $data): bool
    {
        $this->storage[$id] = $data;

        return true;
    }
}

/**
 * Keeps cookies in an array instead of sending headers
 */
class SessionTestCookie implements CookieInterface
{
    /** @var array<string, mixed> */
    public array $jar = [];

    public function set(string $name, $value, int $expire = 0)
    {
        $this->jar[$name] = $value;
    }

    public function get(string $name)
    {
        return $this->jar[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->jar[$name]);
    }

    public function delete(string $name): void
    {
        unset($this->jar[$name]);
    }
}

/**
 * Hands out predictable ids so the tests can assert on them
 */
class SessionTestIdentifier implements SessionIdentifierInterface
{
    private int $calls = 0;

    public function generate(int $length): string
    {
        $this->calls++;

        return 'generated' . $this->calls;
    }
}

/**
 * @return array{0: Session, 1: SessionTestHandler, 2: SessionTestCookie}
 */
function makeSession(): array
{
    $handler = new SessionTestHandler();
    $cookie = new SessionTestCookie();

    $session = new Session(
        'app',
        $handler,
        new SessionTestIdentifier(),
        $cookie,
        'session',
        40
    );

    return [$session, $handler, $cookie];
}

test('the cookie name is the prefix and the session name', function () {
    [$session] = makeSession();

    expect($session->getCookieName())->toBe('session_app');
});

test('start mints an id and stores it in the cookie', function () {
    [$session, , $cookie] = makeSession();

    $session->start();

    expect($session->getId())->toBe('generated1');
    expect($cookie->jar['session_app'])->toBe('generated1');
});

test('start reuses the id already in the cookie', function () {
    [$session, , $cookie] = makeSession();
    $cookie->jar['session_app'] = 'existingid';

    $session->start();

    expect($session->getId())->toBe('existingid');
});

test('start replaces a cookie id that is not a valid identifier', function () {
    [$session, , $cookie] = makeSession();
    $cookie->jar['session_app'] = '../../etc/passwd';

    $session->start();

    expect($session->getId())->toBe('generated1');
    expect($cookie->jar['session_app'])->toBe('generated1');
});

test('regenerate mints a new id and carries the data over', function () {
    [$session, $handler, $cookie] = makeSession();

    $session->start();
    $session->set('user', 42);
    $session->save();

    $oldId = $session->getId();

    $session->regenerate();

    expect($session->getId())->not->toBe($oldId);
    expect($session->get('user'))->toBe(42);
    expect($handler->storage[(string) $session->getId()])->toBe(
        serialize(['user' => 42])
    );
});

test(
    'regenerate destroys the old handler entry and rewrites the cookie',
    function () {
        [$session, $handler, $cookie] = makeSession();

        $session->start();
        $session->set('user', 42);
        $session->save();

        $oldId = (string) $session->getId();

        $session->regenerate();

        expect($handler->destroyed)->toBe([$oldId]);
        expect($handler->storage)->not->toHaveKey($oldId);
        expect($cookie->jar['session_app'])->toBe($session->getId());
    }
);

test('regenerate can keep the old handler entry', function () {
    [$session, $handler] = makeSession();

    $session->start();
    $session->set('user', 42);
    $session->save();

    $oldId = (string) $session->getId();

    $session->regenerate(false);

    expect($handler->destroyed)->toBe([]);
    expect($handler->storage)->toHaveKey($oldId);
});

test('destroy clears the data, the handler entry and the cookie', function () {
    [$session, $handler, $cookie] = makeSession();

    $session->start();
    $session->set('user', 42);
    $session->save();

    $id = (string) $session->getId();

    $session->destroy();

    expect($handler->destroyed)->toBe([$id]);
    expect($session->getId())->toBeNull();
    expect($session->has('user'))->toBeFalse();
    expect($cookie->has('session_app'))->toBeFalse();
});

test('a session without a cookie refuses to manage its own id', function () {
    $session = new Session('app', new SessionTestHandler());

    expect(fn() => $session->start())->toThrow(RuntimeException::class);
    expect(fn() => $session->regenerate())->toThrow(RuntimeException::class);
    expect(fn() => $session->destroy())->toThrow(RuntimeException::class);
});

test('a session without an identifier cannot mint an id', function () {
    $session = new Session(
        'app',
        new SessionTestHandler(),
        null,
        new SessionTestCookie()
    );

    expect(fn() => $session->start())->toThrow(RuntimeException::class);
});
