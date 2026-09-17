<?php

namespace Oak\Session;

/**
 * Class Session
 * @package app\session
 */
class Session
{
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
     */
    public function __construct(string $name, \SessionHandlerInterface $handler)
    {
        $this->name = $name;
        $this->handler = $handler;
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
