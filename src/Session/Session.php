<?php

namespace Oak\Session;

use Oak\Contracts\Cookie\CookieInterface;
use Oak\Contracts\Session\SessionIdentifierInterface;

/**
 * Class Session
 * @package app\session
 */
class Session
{
    /**
     * Session ids this class is willing to accept from the cookie
     *
     * Matches what {@see SessionIdentifier} produces. An id that does not match
     * did not come from us, so it is replaced rather than trusted.
     */
    private const IDENTIFIER_PATTERN = '/^[A-Za-z0-9]+$/';

    /**
     * Handles sessions
     *
     * @var \SessionHandlerInterface
     */
    private $handler;

    /**
     * Name of the session
     *
     * @var string
     */
    private $name;

    /**
     * Mints session ids
     *
     * @var SessionIdentifierInterface|null
     */
    private $identifier;

    /**
     * Carries the session id to the client
     *
     * @var CookieInterface|null
     */
    private $cookie;

    /**
     * Prefix of the cookie the session id is stored in
     *
     * @var string
     */
    private $cookiePrefix;

    /**
     * Length of a freshly minted session id
     *
     * @var int
     */
    private $identifierLength;

    /**
     * The session id
     *
     * @var string|null
     */
    private $id;

    /**
     * Data of the session
     *
     * @var array<string, mixed>
     */
    private $data = [];

    /**
     * Stores if the session data has been loaded
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * Stores if the session has already been saved
     *
     * @var bool
     */
    private $saved = true;

    /**
     * Session constructor.
     *
     * @param string $name
     * @param \SessionHandlerInterface $handler
     * @param SessionIdentifierInterface|null $identifier
     * @param CookieInterface|null $cookie
     * @param string $cookiePrefix
     * @param int $identifierLength
     */
    public function __construct(
        string $name,
        \SessionHandlerInterface $handler,
        ?SessionIdentifierInterface $identifier = null,
        ?CookieInterface $cookie = null,
        string $cookiePrefix = 'session',
        int $identifierLength = 40,
    ) {
        $this->name = $name;
        $this->handler = $handler;
        $this->identifier = $identifier;
        $this->cookie = $cookie;
        $this->cookiePrefix = $cookiePrefix;
        $this->identifierLength = $identifierLength;
    }

    /**
     * Gets the session handler
     *
     * @return \SessionHandlerInterface
     */
    public function getHandler(): \SessionHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Loads the data
     *
     * @return void
     */
    private function loadData()
    {
        $id = $this->getId();

        if ($id === null) {
            $this->data = [];
            $this->loaded = true;
            return;
        }

        $contents = $this->handler->read($id);
        $data = is_string($contents) ? unserialize($contents) : false;

        $this->data = is_array($data) ? $data : [];
        $this->loaded = true;
    }

    /**
     * Gets the name of the session
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the name of the cookie the session id is carried in
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->cookiePrefix . '_' . $this->name;
    }

    /**
     * Gets the length a freshly minted session id is generated with
     *
     * @return int
     */
    public function getIdentifierLength(): int
    {
        return $this->identifierLength;
    }

    /**
     * Gets the session id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the session id
     *
     * @param string|null $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Picks up the session id from the cookie, minting one when there is none
     *
     * An id that is present but does not look like one we minted is discarded
     * rather than used, so a hand-crafted cookie cannot steer the handler.
     *
     * @return void
     * @throws \RuntimeException When the session was built without a cookie
     */
    public function start(): void
    {
        $cookie = $this->requireCookie();
        $cookieName = $this->getCookieName();

        $id = $cookie->has($cookieName) ? $cookie->get($cookieName) : null;

        if (
            !is_string($id) ||
            preg_match(self::IDENTIFIER_PATTERN, $id) !== 1
        ) {
            $id = $this->generateId();
            $cookie->set($cookieName, $id);
        }

        $this->setId($id);
    }

    /**
     * Rotates the session id, carrying the current data over to the new one
     *
     * Call this whenever the privilege level of the session changes — after a
     * login above all — so that an id an attacker planted before the login is
     * worthless after it.
     *
     * @param bool $destroyOld Whether the old entry is removed from the handler
     * @return void
     * @throws \RuntimeException When the session was built without an identifier
     *                           or a cookie
     */
    public function regenerate(bool $destroyOld = true): void
    {
        $cookie = $this->requireCookie();

        if (!$this->loaded) {
            $this->loadData();
        }

        $oldId = $this->getId();
        $newId = $this->generateId();

        $this->setId($newId);
        $this->saved = false;
        $this->save();

        $cookie->set($this->getCookieName(), $newId);

        if ($destroyOld && $oldId !== null && $oldId !== $newId) {
            $this->handler->destroy($oldId);
        }
    }

    /**
     * Clears the session, its handler entry and its cookie
     *
     * @return void
     * @throws \RuntimeException When the session was built without a cookie
     */
    public function destroy(): void
    {
        $cookie = $this->requireCookie();
        $id = $this->getId();

        if ($id !== null) {
            $this->handler->destroy($id);
        }

        $this->data = [];
        $this->loaded = true;
        $this->saved = true;
        $this->setId(null);

        $cookie->delete($this->getCookieName());
    }

    /**
     * Mints a new session id
     *
     * @return string
     * @throws \RuntimeException When the session was built without an identifier
     */
    private function generateId(): string
    {
        if ($this->identifier === null) {
            throw new \RuntimeException(
                'This session was constructed without a ' .
                    SessionIdentifierInterface::class .
                    ', so it cannot mint a session id.',
            );
        }

        return $this->identifier->generate($this->identifierLength);
    }

    /**
     * @return CookieInterface
     * @throws \RuntimeException When the session was built without a cookie
     */
    private function requireCookie(): CookieInterface
    {
        if ($this->cookie === null) {
            throw new \RuntimeException(
                'This session was constructed without a ' .
                    CookieInterface::class .
                    ', so it cannot manage its own cookie.',
            );
        }

        return $this->cookie;
    }

    /**
     * Stores data for a given key
     *
     * @param string $key
     * @param mixed $data
     * @return void
     */
    public function set($key, $data)
    {
        if (!$this->loaded) {
            $this->loadData();
        }
        $this->data[$key] = $data;
        $this->saved = false;
    }

    /**
     * Gets data for a given key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->loaded) {
            $this->loadData();
        }
        return $this->data[$key] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (!$this->loaded) {
            $this->loadData();
        }

        return isset($this->data[$key]);
    }

    /**
     * Saves the data in the session (using our handler)
     *
     * @return void
     */
    public function save()
    {
        if (!$this->saved && $this->getId() !== null) {
            $this->handler->write($this->getId(), serialize($this->data));
            $this->saved = true;
        }
    }
}
