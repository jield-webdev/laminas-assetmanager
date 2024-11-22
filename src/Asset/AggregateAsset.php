<?php

namespace AssetManager\Asset;

use Assetic\Asset\BaseAsset;
use Assetic\Contracts\Filter\FilterInterface;
use AssetManager\Exception;
use Override;

/**
 * Represents a concatented string asset.
 */
class AggregateAsset extends BaseAsset implements AssetInterface
{
    public ?string $mimetype = null;

    /**
     * @var int Timestamp of last modified date from asset
     */
    private int $lastModified;

    /**
     * Constructor.
     *
     * @param array $content The array of assets to be merged
     * @param array $filters Filters for the asset
     * @param string|null $sourceRoot The source asset root directory
     * @param string|null $sourcePath The source asset path
     */
    public function __construct(array $content = [], array $filters = [], ?string $sourceRoot = null, ?string $sourcePath = null)
    {
        parent::__construct(filters: $filters, sourceRoot: $sourceRoot, sourcePath: $sourcePath);
        $this->processContent(content: $content);
    }

    /**
     * Loop through assets and merge content
     *
     * @param array $content
     *
     * @throws Exception\RuntimeException
     */
    private function processContent(array $content): void
    {
        $this->mimetype = null;
        /** @var AggregateAsset $asset */
        foreach ($content as $asset) {
            if (null === $this->mimetype) {
                $this->mimetype = $asset->mimetype;
            }

            if ($asset->mimetype !== $this->mimetype) {
                throw new Exception\RuntimeException(
                    message: sprintf(
                        'Asset "%s" doesn\'t have the expected mime-type "%s".',
                        $asset->getTargetPath(),
                        $this->mimetype
                    )
                );
            }

            $this->setLastModified(
                lastModified: max(
                    $asset->getLastModified(),
                    $this->getLastModified()
                )
            );
            $this->setContent(
                content: $this->getContent() . $asset->dump()
            );
        }
    }

    /**
     * get last modified value from asset
     *
     * @return int|null
     */
    #[Override]
    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    /**
     * set last modified value of asset
     * this is useful for cache mechanism detection id file has changed
     */
    public function setLastModified(int $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    /**
     * load asset
     */
    #[Override]
    public function load(?FilterInterface $additionalFilter = null): void
    {
        $this->doLoad(content: $this->getContent(), additionalFilter: $additionalFilter);
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(?string $mimetype = null): AggregateAsset
    {
        $this->mimetype = $mimetype;
        return $this;
    }
}
