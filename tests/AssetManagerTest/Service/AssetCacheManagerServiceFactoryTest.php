<?php

namespace AssetManagerTest\Service;

use AssetManager\Service\AssetCacheManager;
use AssetManager\Service\AssetCacheManagerServiceFactory;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\ServiceManager;

class AssetCacheManagerServiceFactoryTest extends TestCase
{
    public function testConstruct()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'caching' => array(
                        'default' => array(
                            'cache' => 'Filesystem',
                        ),
                    ),
                ),
            )
        );

        $assetManager = new AssetCacheManagerServiceFactory($serviceManager);

        $service = $assetManager->createService($serviceManager);

        $this->assertTrue($service instanceof AssetCacheManager);
    }
}
