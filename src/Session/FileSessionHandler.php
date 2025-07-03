<?php

namespace Oak\Session;

use Oak\Contracts\Filesystem\FilesystemInterface;
use SessionHandlerInterface;

/**
 * Class FileSessionHandler
 * @package Oak\Session
 */
class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * Handles working with files
     *
     * @var FilesystemInterface $filesystem
     */
    private $filesystem;

    /**
     * Path to store the sessions
     *
     * @var string
     */
    private $path;

    /**
     * FileSessionHandler constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param string $path
     */
    public function __construct(string $path, FilesystemInterface $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Destroy the session with given session id
     *
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $this->filesystem->delete($this->path.'/'.$sessionId);

        return true;
    }

    /**
     * Garbage collection
     *
     * @param int $max_lifetime
     * @return int|false Number of deleted sessions or false on failure
     */
    public function gc(int $max_lifetime): int|false
    {
        $now = time();
        $files = $this->filesystem->files($this->path);

        foreach ($files as $filePath) {
            if ($this->filesystem->modificationTime($filePath) + $max_lifetime < $now) {
                $this->filesystem->delete($filePath);
            }
        }

        return true;
    }

    /**
     * Session start
     *
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Read session data by given session id
     *
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId): string
    {
        if ($this->filesystem->exists($this->path.'/'.$sessionId)) {
            return $this->filesystem->get($this->path.'/'.$sessionId);
        }

        return '';
    }

    /**
     * Write data to session
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     */
    public function write($sessionId, $sessionData): bool
    {
        $this->filesystem->put($this->path.'/'.$sessionId, $sessionData);

        return true;
    }
}
