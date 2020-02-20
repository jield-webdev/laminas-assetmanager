<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\MapResolver;
use AssetManager\Service\MapResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class MapResolverServiceFactoryTest extends TestCase
{
    /**
     * Mainly to avoid regressions
     */
    public function testCreateService()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                'asset_manager' => [
                    'resolver_configs' => [
                        'map' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ],
                    ],
                ],
            ]
        );

        $factory = new MapResolverServiceFactory();
        /* @var MapResolver */
        $mapResolver = $factory->createService($serviceManager);
        $this->assertSame(
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            $mapResolver->getMap()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', []);

        $factory = new MapResolverServiceFactory();
        /* @var MapResolver */
        $mapResolver = $factory->createService($serviceManager);
        $this->assertEmpty($mapResolver->getMap());
    }
}
