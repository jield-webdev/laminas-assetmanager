<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\CollectionResolver;
use AssetManager\Service\CollectionResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class CollectionResolverServiceFactoryTest extends TestCase
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
                        'collections' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ],
                    ],
                ],
            ]
        );

        $factory = new CollectionResolverServiceFactory();
        /* @var CollectionResolver */
        $collectionsResolver = $factory->createService($serviceManager);
        $this->assertSame(
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            $collectionsResolver->getCollections()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', []);

        $factory = new CollectionResolverServiceFactory();
        /* @var CollectionResolver */
        $collectionsResolver = $factory->createService($serviceManager);
        $this->assertEmpty($collectionsResolver->getCollections());
    }
}
