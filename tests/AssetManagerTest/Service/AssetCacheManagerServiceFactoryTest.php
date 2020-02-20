<?php

namespace AssetManagerTest\Service;

use AssetManager\Service\AssetCacheManager;
use AssetManager\Service\AssetCacheManagerServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class AssetCacheManagerServiceFactoryTest extends TestCase
{
    public function testConstruct()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                'asset_manager' => [
                    'caching' => [
                        'default' => [
                            'cache' => 'Apc',
                        ],
                    ],
                ],
            ]
        );

        $assetManager = new AssetCacheManagerServiceFactory();
        $service = $assetManager->createService($serviceManager);

        $this->assertTrue($service instanceof AssetCacheManager);
    }
}
