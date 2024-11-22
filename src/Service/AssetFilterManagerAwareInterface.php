<?php

namespace AssetManager\Service;

interface AssetFilterManagerAwareInterface
{
    /**
     * Set the AssetFilterManager.
     */
    public function setAssetFilterManager(AssetFilterManager $filterManager);

    /**
     * Get the AssetFilterManager
     */
    public function getAssetFilterManager(): AssetFilterManager;
}
