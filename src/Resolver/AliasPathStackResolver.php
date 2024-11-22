<?php

namespace AssetManager\Resolver;

use Assetic\Factory\Resource\DirectoryResource;
use AssetManager\Asset\FileAsset;
use AssetManager\Exception;
use AssetManager\Service\MimeResolver;
use Laminas\Stdlib\SplStack;
use Override;
use RuntimeException;
use SplFileInfo;

/**
 * This resolver allows you to resolve from a stack of aliases to a path.
 */
class AliasPathStackResolver implements ResolverInterface, MimeResolverAwareInterface
{
    protected array $aliases = [];

    /**
     * Flag indicating whether or not LFI protection for rendering view scripts is enabled
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
     *
     * Populate the array stack with a list of aliases and their corresponding paths
     *
     * @param array $aliases
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $aliases)
    {
        foreach ($aliases as $alias => $path) {
            $this->addAlias(alias: $alias, path: $path);
        }
    }

    /**
     * Add a single alias to the stack
     *
     * @param string $alias
     * @param string $path
     * @throws Exception\InvalidArgumentException
     */
    private function addAlias(string $alias, string $path): void
    {
        $this->aliases[$alias] = $this->normalizePath(path: $path);
    }

    /**
     * Normalize a path for insertion in the stack
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return rtrim(string: $path, characters: '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Set the mime resolver
     */
    #[Override]
    public function setMimeResolver(MimeResolver $mimeResolver): void
    {
        $this->mimeResolver = $mimeResolver;
    }

    /**
     * Get the mime resolver
     */
    #[Override]
    public function getMimeResolver(): MimeResolver
    {
        return $this->mimeResolver;
    }

    /**
     * Set LFI protection flag
     *
     * @param bool $flag
     * @return self
     */
    public function setLfiProtection(bool $flag): AliasPathStackResolver
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

        foreach ($this->aliases as $alias => $path) {
            if (!str_contains(haystack: $fileName, needle: $alias)) {
                continue;
            }

            $correctedFilename = substr_replace(string: $fileName, replace: '', offset: 0, length: strlen(string: $alias));

            $file = new SplFileInfo(filename: $path . $correctedFilename);

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

        foreach ($this->aliases as $alias => $path) {
            $locations = new SplStack();
            $pathInfo  = new SplFileInfo(filename: $path);
            $locations->push(value: $pathInfo);
            $basePath = $this->normalizePath(path: $pathInfo->getRealPath());

            while (!$locations->isEmpty()) {
                /** @var SplFileInfo $pathInfo */
                $pathInfo = $locations->pop();
                if (!$pathInfo->isReadable()) {
                    throw new RuntimeException(sprintf('%s is not readable.', $pathInfo->getPath()));
                }

                if ($pathInfo->isDir()) {
                    foreach (new DirectoryResource(path: $pathInfo->getRealPath()) as $resource) {
                        $locations->push(value: new SplFileInfo(filename: $resource));
                    }
                } else {
                    $collection[] = $alias . substr(string: $pathInfo->getRealPath(), offset: strlen(string: $basePath));
                }
            }
        }

        return array_unique(array: $collection);
    }
}
