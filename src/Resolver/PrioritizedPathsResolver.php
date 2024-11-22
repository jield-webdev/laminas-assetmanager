<?php

namespace AssetManager\Resolver;

use ArrayAccess;
use Assetic\Factory\Resource\DirectoryResource;
use AssetManager\Asset\FileAsset;
use AssetManager\Exception;
use AssetManager\Service\MimeResolver;
use Laminas\Stdlib\PriorityQueue;
use Laminas\Stdlib\SplStack;
use Override;
use SplFileInfo;
use Traversable;

/**
 * This resolver allows you to resolve from a multitude of prioritized paths.
 */
class PrioritizedPathsResolver implements ResolverInterface, MimeResolverAwareInterface
{
    /**
     * @var PriorityQueue|ResolverInterface[]
     */
    protected array|PriorityQueue $paths;

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
     * Constructor.
     * Construct object and set a new PriorityQueue.
     */
    public function __construct()
    {
        $this->paths = new PriorityQueue();
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
     * {}
     */
    public function addPath($path): void
    {
        if (is_string(value: $path)) {
            $this->paths->insert(data: $this->normalizePath(path: $path), priority: 1);

            return;
        }

        if (!is_array(value: $path) && !$path instanceof ArrayAccess) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                'Provided path must be an array or an instance of ArrayAccess, %s given',
                get_debug_type(value: $path)
            ));
        }

        if (isset($path['priority']) && isset($path['path'])) {
            $this->paths->insert(data: $this->normalizePath(path: $path['path']), priority: $path['priority']);

            return;
        }

        throw new Exception\InvalidArgumentException(message: 'Provided array must contain both keys "priority" and "path"');
    }

    /**
     * {}
     */
    public function getPaths(): PriorityQueue|array
    {
        return $this->paths;
    }

    /**
     * {}
     */
    public function clearPaths(): void
    {
        $this->paths = new PriorityQueue();
    }

    /**
     * Add many paths to the stack at once
     *
     * @param iterable $paths
     * @return self
     */
    public function addPaths(iterable $paths): PrioritizedPathsResolver
    {
        foreach ($paths as $path) {
            $this->addPath(path: $path);
        }

        return $this;
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
     * Set LFI protection flag
     *
     * @param bool $flag
     * @return self
     */
    public function setLfiProtection(bool $flag): PrioritizedPathsResolver
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
    public function resolve(string $fileName): FileAsset|null
    {
        if ($this->isLfiProtectionOn() && preg_match(pattern: '#\.\.[\\\/]#', subject: $fileName)) {
            return null;
        }

        foreach ($this->getPaths() as $fileName) {
            $file = new SplFileInfo(filename: $fileName . $fileName);

            if ($file->isReadable() && !$file->isDir()) {
                $filePath = $file->getRealPath();
                $mimeType = $this->getMimeResolver()->getMimeType(filename: $filePath);
                $asset    = new FileAsset(source: $filePath);
                $asset->setMimetype($mimeType);

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
                    throw new Exception\RuntimeException(message: sprintf('%s is not readable.', $pathInfo->getPath()));
                }

                if ($pathInfo->isDir()) {
                    foreach (new DirectoryResource(path: $pathInfo->getRealPath()) as $resource) {
                        $locations->push(value: new SplFileInfo(filename: $resource));
                    }
                } else {
                    $collection[] = substr(string: $pathInfo->getRealPath(), offset: strlen(string: $basePath));
                }
            }
        }

        return array_unique(array: $collection);
    }
}
