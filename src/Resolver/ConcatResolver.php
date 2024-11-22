<?php

namespace AssetManager\Resolver;

use AssetManager\Asset\AggregateAsset;
use AssetManager\Exception;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetFilterManagerAwareInterface;
use AssetManager\Service\MimeResolver;
use Laminas\Stdlib\ArrayUtils;
use Override;

/**
 * This resolver allows the resolving of concatenated files.
 * Concatted files are added as an StringAsset and filters get applied to concatenated string.
 */
class ConcatResolver implements
    ResolverInterface,
    AggregateResolverAwareInterface,
    AssetFilterManagerAwareInterface,
    MimeResolverAwareInterface
{
    /**
     * @var null|ResolverInterface
     */
    protected ?ResolverInterface $aggregateResolver;

    /**
     * @var null|AssetFilterManager The filterManager service.
     */
    protected ?AssetFilterManager $filterManager;

    /**
     * @var array the concats
     */
    protected array $concats = [];

    /**
     * @var MimeResolver The mime resolver.
     */
    protected MimeResolver $mimeResolver;

    /**
     * Constructor
     *
     * Instantiate and optionally populate concats.
     *
     * @param iterable $concats
     */
    public function __construct(iterable $concats = [])
    {
        $this->setConcats(concats: $concats);
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
     * Set (overwrite) concats
     *
     * Concats should be arrays or Traversable objects with name => path pairs
     *
     * @param iterable $concats
     * @throws Exception\InvalidArgumentException
     */
    public function setConcats(iterable $concats): void
    {
        $this->concats = ArrayUtils::iteratorToArray(iterator: $concats);
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
     * Get the aggregate resolver.
     */
    #[Override]
    public function getAggregateResolver(): ResolverInterface
    {
        return $this->aggregateResolver;
    }

    /**
     * Retrieve the concats
     */
    public function getConcats(): array
    {
        return $this->concats;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function resolve(string $fileName): \AssetManager\Asset\AssetInterface|null
    {
        if (!isset($this->concats[$fileName])) {
            return null;
        }

        $resolvedAssets = [];

        foreach ((array)$this->concats[$fileName] as $assetName) {

            $resolvedAsset = $this->getAggregateResolver()->resolve(fileName: (string)$assetName);

            if (!$resolvedAsset instanceof \AssetManager\Asset\AssetInterface) {
                throw new Exception\RuntimeException(
                    message: sprintf(
                        'Asset "%s" from collection "%s" can\'t be resolved '
                        . 'to an Asset implementing \AssetManager\Asset\AssetInterface.',
                        $assetName,
                        $fileName
                    )
                );
            }

            $resolvedAsset->setMimeType($this->getMimeResolver()->getMimeType(
                filename: $resolvedAsset->getSourceRoot() . $resolvedAsset->getSourcePath()
            ));

            $this->getAssetFilterManager()->setFilters(path: $assetName, asset: $resolvedAsset);

            $resolvedAssets[] = $resolvedAsset;
        }

        $aggregateAsset = new AggregateAsset(content: $resolvedAssets);
        $this->getAssetFilterManager()->setFilters(path: $fileName, asset: $aggregateAsset);
        $aggregateAsset->setTargetPath(targetPath: $fileName);

        return $aggregateAsset;
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
     * Get the AssetFilterManager
     */
    #[Override]
    public function getAssetFilterManager(): AssetFilterManager
    {
        return $this->filterManager;
    }

    public function collect(): array
    {
        return array_keys(array: $this->concats);
    }
}
