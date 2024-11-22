<?php

namespace AssetManager\Service;

use AssetManager\Resolver\AggregateResolver;
use Override;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Factory class for AssetManagerService
 *
 * @category   AssetManager
 * @package    AssetManager
 */
class AssetManagerServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AssetManager
    {
        $config             = $container->get('config');
        $assetManagerConfig = [];

        if (!empty($config['asset_manager'])) {
            $assetManagerConfig = $config['asset_manager'];
        }

        $assetManager = new AssetManager(
            resolver: $container->get(AggregateResolver::class),
            config: $assetManagerConfig
        );

        $assetManager->setAssetFilterManager(
            filterManager: $container->get(AssetFilterManager::class)
        );

        $assetManager->setAssetCacheManager(
            cacheManager: $container->get(AssetCacheManager::class)
        );

        return $assetManager;
    }
}
