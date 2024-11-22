<?php

namespace AssetManager\Resolver;

use AssetManager\Asset\AssetCollection;
use AssetManager\Exception;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetFilterManagerAwareInterface;
use Laminas\Stdlib\ArrayUtils;
use Override;
use Traversable;

/**
 * This resolver allows the resolving of collections.
 * Collections are strictly checked by mime-type,
 * and added to an AssetCollection when all checks passed.
 */
class CollectionResolver implements
    ResolverInterface,
    AggregateResolverAwareInterface,
    AssetFilterManagerAwareInterface
{
    protected ResolverInterface $aggregateResolver;

    protected AssetFilterManager $filterManager;

    protected array $collections = [];

    /**
     * Constructor
     *
     * Instantiate and optionally populate collections.
     *
     * @param iterable $collections
     */
    public function __construct(iterable $collections = [])
    {
        $this->setCollections(collections: $collections);
    }

    /**
     * Retrieve the collections
     *
     * @return array
     */
    public function getCollections(): array
    {
        return $this->collections;
    }

    /**
     * Set (overwrite) collections
     *
     * Collections should be arrays or Traversable objects with name => path pairs
     *
     * @param iterable $collections
     * @throws Exception\InvalidArgumentException
     */
    public function setCollections(iterable $collections): void
    {
        if (!is_array(value: $collections) && !$collections instanceof Traversable) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                '%s: expects an array or Traversable, received "%s"',
                __METHOD__,
                (get_debug_type(value: $collections))
            ));
        }

        if ($collections instanceof Traversable) {
            $collections = ArrayUtils::iteratorToArray(iterator: $collections);
        }

        $this->collections = $collections;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function resolve(string $fileName): \AssetManager\Asset\AssetInterface|null
    {
        if (!isset($this->collections[$fileName])) {
            return null;
        }

        if (!is_array(value: $this->collections[$fileName])) {
            throw new Exception\RuntimeException(
                message: sprintf('Collection with name %s is not an an array.', $fileName)
            );
        }

        //fix82 Extend from the local assetCollection with mimetype support
        $collection = new AssetCollection();
        $mimeType   = null;
        $collection->setTargetPath(targetPath: $fileName);
        foreach ($this->collections[$fileName] as $asset) {

            if (!is_string(value: $asset)) {
                throw new Exception\RuntimeException(
                    message: 'Asset should be of type string. got ' . gettype(value: $asset)
                );
            }

            if (null === ($res = $this->getAggregateResolver()->resolve(fileName: $asset))) {
                throw new Exception\RuntimeException(message: sprintf("Asset '%s' could not be found.", $asset));
            }

            if (!$res instanceof \AssetManager\Asset\AssetInterface) {
                throw new Exception\RuntimeException(
                    message: sprintf("Asset '%s' does not implement AssetManager\\Asset\\AssetInterface.", $asset)
                );
            }

            if (null !== $mimeType && $res->getMimeType() !== $mimeType) {
                throw new Exception\RuntimeException(message: sprintf(
                    'Asset "%s" from collection "%s" doesn\'t have the expected mime-type "%s".',
                    $asset,
                    $fileName,
                    $mimeType
                ));
            }

            $mimeType = $res->getMimeType();

            $this->getAssetFilterManager()->setFilters(path: $asset, asset: $res);

            $collection->add(asset: $res);
        }

        $collection->setMimetype($mimeType);

        return $collection;
    }

    /**
     * Get the aggregate resolver.
     *
     * @return ResolverInterface
     */
    #[Override]
    public function getAggregateResolver(): ResolverInterface
    {
        return $this->aggregateResolver;
    }

    /**
     * Set the aggregate resolver.
     *
     * @param ResolverInterface $aggregateResolver
     */
    #[Override]
    public function setAggregateResolver(ResolverInterface $aggregateResolver): void
    {
        $this->aggregateResolver = $aggregateResolver;
    }

    /**
     * Get the AssetFilterManager
     *
     * @return AssetFilterManager
     */
    #[Override]
    public function getAssetFilterManager(): AssetFilterManager
    {
        return $this->filterManager;
    }

    /**
     * Set the AssetFilterManager.
     *
     * @param AssetFilterManager $filterManager
     */
    #[Override]
    public function setAssetFilterManager(AssetFilterManager $filterManager): void
    {
        $this->filterManager = $filterManager;
    }

    /**
     * {}
     */
    public function collect(): array
    {
        return array_keys(array: $this->collections);
    }
}
