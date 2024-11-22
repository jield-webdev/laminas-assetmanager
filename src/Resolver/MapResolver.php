<?php

namespace AssetManager\Resolver;

use AssetManager\Asset\AssetInterface;
use AssetManager\Asset\FileAsset;
use AssetManager\Asset\HttpAsset;
use AssetManager\Exception;
use AssetManager\Service\MimeResolver;
use Laminas\Stdlib\ArrayUtils;
use Override;
use Traversable;

/**
 * This resolver allows you to resolve using a 1 on 1 mapping to a file.
 */
class MapResolver implements ResolverInterface, MimeResolverAwareInterface
{
    /**
     * @var array
     */
    protected array $map = [];

    /**
     * @var MimeResolver The mime resolver.
     */
    protected MimeResolver $mimeResolver;

    /**
     * Constructor
     *
     * Instantiate and optionally populate map.
     *
     * @param iterable $map
     */
    public function __construct(iterable $map = [])
    {
        $this->setMap(map: $map);
    }

    /**
     * Retrieve the map
     *
     * @return array
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * Set (overwrite) map
     *
     * Maps should be arrays or Traversable objects with name => path pairs
     *
     * @param iterable $map
     * @throws Exception\InvalidArgumentException
     */
    public function setMap(iterable $map): void
    {
        if (!is_array(value: $map) && !$map instanceof Traversable) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                '%s: expects an array or Traversable, received "%s"',
                __METHOD__,
                (get_debug_type(value: $map))
            ));
        }

        if ($map instanceof Traversable) {
            $map = ArrayUtils::iteratorToArray(iterator: $map);
        }

        $this->map = $map;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function resolve(string $fileName): AssetInterface|null
    {
        if (!isset($this->map[$fileName])) {
            return null;
        }

        $file     = $this->map[$fileName];
        $mimeType = $this->getMimeResolver()->getMimeType(filename: $file);

        if (false === filter_var(value: $file, filter: FILTER_VALIDATE_URL)) {
            $asset = new FileAsset(source: $file);
        } else {
            $asset = new HttpAsset(sourceUrl: $file);
        }

        $asset->setMimetype($mimeType);

        return $asset;
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
     * Set the mime resolver
     *
     * @param MimeResolver $mimeResolver
     */
    #[Override]
    public function setMimeResolver(MimeResolver $mimeResolver): void
    {
        $this->mimeResolver = $mimeResolver;
    }

    public function collect(): array
    {
        return array_keys(array: $this->map);
    }
}
