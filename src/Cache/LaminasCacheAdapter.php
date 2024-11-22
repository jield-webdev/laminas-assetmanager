<?php

namespace AssetManager\Cache;

use Assetic\Contracts\Cache\CacheInterface;
use Laminas\Cache\Storage\StorageInterface;
use Override;

/**
 * Laminas Cache Storage Adapter for Assetic
 */
class LaminasCacheAdapter implements CacheInterface
{

    /** @var StorageInterface */
    protected StorageInterface $laminasCache;

    /**
     * Constructor
     *
     * @param StorageInterface $laminasCache Laminas Configured Cache Storage
     */
    public function __construct(StorageInterface $laminasCache)
    {
        $this->laminasCache = $laminasCache;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function has($key)
    {
        return $this->laminasCache->hasItem($key);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function get($key)
    {
        return $this->laminasCache->getItem($key);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function set($key, $value)
    {
        return $this->laminasCache->setItem($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function remove($key)
    {
        return $this->laminasCache->removeItem($key);
    }
}
