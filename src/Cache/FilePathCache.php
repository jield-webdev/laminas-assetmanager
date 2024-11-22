<?php

namespace AssetManager\Cache;

use Assetic\Contracts\Cache\CacheInterface;
use AssetManager\Exception\RuntimeException;
use Laminas\Stdlib\ErrorHandler;
use Override;
use const E_WARNING;

/**
 * A file path cache. Same as FilesystemCache, except for the fact that this will create the
 * directories recursively, in stead of using a hash.
 */
class FilePathCache implements CacheInterface
{
    /**
     * @var string Holds the cachedFile string
     */
    protected ?string $cachedFile = null;

    /**
     * Constructor
     *
     * @param string $dir The directory to cache in
     * @param string $filename The filename we'll be caching for.
     */
    public function __construct(protected string $dir, protected string $filename)
    {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function has($key): bool
    {
        return file_exists(filename: $this->cachedFile());
    }

    /**
     * Get the path-to-file.
     * @return string Cache path
     */
    protected function cachedFile(): string
    {
        if (null === $this->cachedFile) {
            $this->cachedFile = rtrim(string: $this->dir, characters: '/') . '/' . trim(string: $this->filename, characters: '/');
        }

        return $this->cachedFile;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function get($key): false|string|null
    {
        $path = $this->cachedFile();

        if (!file_exists(filename: $path)) {
            throw new RuntimeException(message: 'There is no cached value for ' . $this->filename);
        }

        return file_get_contents(filename: $path);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function set($key, $value): void
    {
        $pathInfo = pathInfo(path: $this->cachedFile());
        $cacheDir = $pathInfo['dirname'];
        $fileName = $pathInfo['basename'];

        ErrorHandler::start();

        if (!is_dir(filename: $cacheDir)) {
            $umask = umask(mask: 0);
            if (!mkdir(directory: $cacheDir, permissions: 0777, recursive: true) && !is_dir(filename: $cacheDir)) {
                throw new \RuntimeException(message: sprintf('Directory "%s" was not created', $cacheDir));
            }

            umask(mask: $umask);

            // @codeCoverageIgnoreStart
        }

        // @codeCoverageIgnoreEnd

        ErrorHandler::stop();

        if (!is_writable(filename: $cacheDir)) {
            throw new RuntimeException(message: 'Unable to write file ' . $this->cachedFile());
        }

        // Use "rename" to achieve atomic writes
        $tmpFilePath = $cacheDir . '/AssetManagerFilePathCache_' . $fileName;

        if (@file_put_contents(filename: $tmpFilePath, data: $value, flags: LOCK_EX) === false) {
            throw new RuntimeException(message: 'Unable to write file ' . $this->cachedFile());
        }

        rename(from: $tmpFilePath, to: $this->cachedFile());
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function remove($key): bool
    {
        ErrorHandler::start(errorLevel: E_WARNING);

        $success = unlink(filename: $this->cachedFile());

        ErrorHandler::stop();

        if (false === $success) {
            throw new RuntimeException(message: sprintf('Could not remove key "%s"', $this->cachedFile()));
        }

        return $success;
    }
}
