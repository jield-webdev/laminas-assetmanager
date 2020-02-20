<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\CollectionResolver;
use AssetManager\Resolver\ConcatResolver;
use AssetManager\Service\ConcatResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ConcatResolverServiceFactoryTest extends TestCase
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
                        'concat' => [
                            'key1' => __FILE__,
                            'key2' => __FILE__,
                        ],
                    ],
                ],
            ]
        );

        $factory = new ConcatResolverServiceFactory();
        /* @var CollectionResolver */
        $concatResolver = $factory->createService($serviceManager);
        $this->assertSame(
            [
                'key1' => __FILE__,
                'key2' => __FILE__,
            ],
            $concatResolver->getConcats()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', []);

        $factory = new ConcatResolverServiceFactory();
        /* @var ConcatResolver */
        $concatResolver = $factory->createService($serviceManager);
        $this->assertEmpty($concatResolver->getConcats());
    }
}
