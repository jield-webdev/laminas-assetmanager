<?php

namespace AssetManager\Resolver;

use Assetic\Factory\Resource\DirectoryResource;
use AssetManager\Asset\FileAsset;
use AssetManager\Exception;
use AssetManager\Service\MimeResolver;
use Laminas\Stdlib\SplStack;
use Override;
use SplFileInfo;
use Traversable;

/**
 * This resolver allows you to resolve from a stack of paths.
 */
class PathStackResolver implements ResolverInterface, MimeResolverAwareInterface
{
    /**
     * @var SplStack
     */
    protected SplStack $paths;

    /**
     * Flag indicating whether or not LFI protection for rendering view scripts is enabled
     *
     * @var bool
     */
    protected bool $lfiProtectionOn = true;

    /**
     * The mime resolver.
     *
     * @var MimeResolver
     */
    protected MimeResolver $mimeResolver;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paths = new SplStack();
    }

    /**
     * Set the mime resolver
     *
     * @param MimeResolver $mimeResolver
     */
    #[Override]
    public function setMimeResolver(MimeResolver $mimeResolver): void
    {
        $this->mimeResolver = $mimeResolver;
    }

    /**
     * Get the mime resolver
     *
     * @return MimeResolver
     */
    #[Override]
    public function getMimeResolver(): MimeResolver
    {
        return $this->mimeResolver;
    }

    /**
     * Add many paths to the stack at once
     *
     * @param iterable $paths
     */
    public function addPaths(iterable $paths): void
    {
        foreach ($paths as $path) {
            $this->addPath(path: $path);
        }
    }

    /**
     * Rest the path stack to the paths provided
     *
     * @param iterable $paths
     * @throws Exception\InvalidArgumentException
     */
    public function setPaths(iterable $paths): void
    {
        if (!is_array(value: $paths) && !$paths instanceof Traversable) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                'Invalid argument provided for $paths, expecting either an array or Traversable object, "%s" given',
                get_debug_type(value: $paths)
            ));
        }

        $this->clearPaths();
        $this->addPaths(paths: $paths);
    }

    /**
     * Normalize a path for insertion in the stack
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        $path = rtrim(string: $path, characters: '/\\');
        $path .= DIRECTORY_SEPARATOR;

        return $path;
    }

    /**
     * Add a single path to the stack
     *
     * @param string $path
     * @throws Exception\InvalidArgumentException
     */
    public function addPath(string $path): void
    {
        if (!is_string(value: $path)) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                'Invalid path provided; must be a string, received %s',
                gettype(value: $path)
            ));
        }

        $this->paths[] = $this->normalizePath(path: $path);
    }

    /**
     * Clear all paths
     *
     * @return void
     */
    public function clearPaths(): void
    {
        $this->paths = new SplStack();
    }

    /**
     * Returns stack of paths
     *
     * @return SplStack
     */
    public function getPaths(): SplStack
    {
        return $this->paths;
    }

    /**
     * Set LFI protection flag
     *
     * @param bool $flag
     * @return self
     */
    public function setLfiProtection(bool $flag): PathStackResolver
    {
        $this->lfiProtectionOn = $flag;

        return $this;
    }

    /**
     * Return status of LFI protection flag
     *
     * @return bool
     */
    public function isLfiProtectionOn(): bool
    {
        return $this->lfiProtectionOn;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function resolve(string $fileName): FileAsset|\Assetic\Contracts\Asset\AssetInterface|null
    {
        if ($this->isLfiProtectionOn() && preg_match(pattern: '#\.\.[\\\/]#', subject: $fileName)) {
            return null;
        }

        foreach ($this->getPaths() as $path) {

            $file = new SplFileInfo(filename: $path . $fileName);

            if ($file->isReadable() && !$file->isDir()) {
                $filePath = $file->getRealPath();
                $mimeType = $this->getMimeResolver()->getMimeType(filename: $filePath);
                $asset    = new FileAsset(source: $filePath);

                $asset->mimetype = $mimeType;

                return $asset;
            }
        }

        return null;
    }

    public function collect(): array
    {
        $collection = [];
        foreach ($this->getPaths() as $path) {
            $locations = new SplStack();
            $pathInfo  = new SplFileInfo(filename: $path);
            $locations->push(value: $pathInfo);
            $basePath = $this->normalizePath(path: $pathInfo->getRealPath());

            while (!$locations->isEmpty()) {
                /** @var SplFileInfo $pathInfo */
                $pathInfo = $locations->pop();
                if (!$pathInfo->isReadable()) {
                    continue;
                }

                if ($pathInfo->isDir()) {
                    $dir = new DirectoryResource(path: $pathInfo->getRealPath());
                    foreach ($dir as $resource) {
                        $locations->push(value: new SplFileInfo(filename: $resource));
                    }
                } elseif (!isset($collection[$pathInfo->getPath()])) {
                    $collection[] = substr(string: $pathInfo->getRealPath(), offset: strlen(string: $basePath));
                }
            }
        }

        return $collection;
    }
}
