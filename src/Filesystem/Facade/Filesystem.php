<?php

namespace Oak\Filesystem\Facade;

use Oak\Contracts\Filesystem\FilesystemInterface;
use Oak\Facade;

/**
 * Filesystem Facade providing static access to filesystem operations
 * 
 * @method static bool exists(string $path) Check if a file or directory exists
 * @method static bool isWriteable(string $path) Check if a path is writeable
 * @method static bool isReadable(string $path) Check if a path is readable
 * @method static int size(string $path) Get the size of a file
 * @method static string mimetype(string $path) Get the MIME type of a file
 * @method static int modificationTime(string $path) Get the modification time of a file
 * @method static mixed get(string $path) Get the contents of a file
 * @method static mixed put(string $path, mixed $contents) Write contents to a file
 * @method static mixed prepend(string $path, mixed $contents) Prepend contents to a file
 * @method static mixed append(string $path, mixed $contents) Append contents to a file
 * @method static array files(string $path) Get all files in a directory
 * @method static array directories(string $path) Get all directories in a path
 * @method static mixed delete(string $path) Delete a file or directory
 * @method static mixed move(string $path, string $newPath) Move a file or directory
 * @method static mixed copy(string $path, string $newPath) Copy a file or directory
 */
class Filesystem extends Facade
{
    /**
     * Get the service contract that this facade represents
     * 
     * @return class-string<FilesystemInterface>
     */
    protected static function getContract(): string
    {
        return FilesystemInterface::class;
    }
}