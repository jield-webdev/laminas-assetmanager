<?php

namespace AssetManager\Asset;

use Assetic\Asset\BaseAsset;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\FilterInterface;
use AssetManager\Exception;

/**
 * Represents a concatented string asset.
 */
class AggregateAsset extends BaseAsset
{
    public $mimetype;
    /**
     * @var int Timestamp of last modified date from asset
     */
    private $lastModified;

    /**
     * Constructor.
     *
     * @param array $content The array of assets to be merged
     * @param array $filters Filters for the asset
     * @param string $sourceRoot The source asset root directory
     * @param string $sourcePath The source asset path
     */
    public function __construct(array $content = [], $filters = [], $sourceRoot = null, $sourcePath = null)
    {
        parent::__construct($filters, $sourceRoot, $sourcePath);
        $this->processContent($content);
    }

    /**
     * Loop through assets and merge content
     *
     * @param array $content
     *
     * @throws Exception\RuntimeException
     */
    private function processContent($content)
    {
        $this->mimetype = null;
        /** @var AssetInterface $asset */
        foreach ($content as $asset) {
            if (null === $this->mimetype) {
                $this->mimetype = $asset->mimetype;
            }

            if ($asset->mimetype !== $this->mimetype) {
                throw new Exception\RuntimeException(
                    sprintf(
                        'Asset "%s" doesn\'t have the expected mime-type "%s".',
                        $asset->getTargetPath(),
                        $this->mimetype
                    )
                );
            }

            $this->setLastModified(
                max(
                    $asset->getLastModified(),
                    $this->getLastModified()
                )
            );
            $this->setContent(
                $this->getContent() . $asset->dump()
            );
        }
    }

    /**
     * get last modified value from asset
     *
     * @return int|null
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * set last modified value of asset
     *
     * this is useful for cache mechanism detection id file has changed
     *
     * @param int $lastModified
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * load asset
     *
     * @param FilterInterface $additionalFilter
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        $this->doLoad($this->getContent(), $additionalFilter);
    }
}
