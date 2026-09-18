<?php

namespace Oak\Migration\Storage;

use Oak\Contracts\Filesystem\FilesystemInterface;
use Oak\Contracts\Migration\VersionStorageInterface;
use Oak\Migration\Migrator;

class JsonVersionStorage implements VersionStorageInterface
{
    /**
     * @var FilesystemInterface $filesystem
     */
    private $filesystem;

    /**
     * @var string $filename
     */
    private $filename;

    /**
     * JsonVersionStorage constructor.
     * @param FilesystemInterface $filesystem
     */
    public function __construct(
        FilesystemInterface $filesystem,
        string $filename,
    ) {
        $this->filesystem = $filesystem;
        $this->filename = $filename;
    }

    /**
     * @param Migrator $migrator
     * @return int
     */
    public function get(Migrator $migrator): int
    {
        if (!$this->filesystem->exists($this->filename)) {
            $this->filesystem->put($this->filename, '{}');
        }

        $versionData = $this->readVersionData();
        $version = $versionData[$migrator->getName()] ?? 0;

        return is_numeric($version) ? (int) $version : 0;
    }

    /**
     * @param Migrator $migrator
     * @param int $version
     */
    public function store(Migrator $migrator, int $version)
    {
        $versionData = $this->readVersionData();
        $versionData[$migrator->getName()] = $version;

        $encoded = json_encode($versionData);

        $this->filesystem->put(
            $this->filename,
            $encoded === false ? '{}' : $encoded,
        );
    }

    /**
     * Reads the stored version data, an array keyed by migrator name
     *
     * @return array<string, mixed>
     */
    private function readVersionData(): array
    {
        $contents = $this->filesystem->get($this->filename);
        $versionData = json_decode(
            $contents === false ? '{}' : $contents,
            true,
        );

        if (!is_array($versionData)) {
            return [];
        }

        $data = [];

        foreach ($versionData as $name => $version) {
            $data[(string) $name] = $version;
        }

        return $data;
    }
}
