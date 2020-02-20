<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\PathStackResolver;
use AssetManager\Service\PathStackResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class PathStackResolverServiceFactoryTest extends TestCase
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
                        'paths' => [
                            'path1/',
                            'path2/',
                        ],
                    ],
                ],
            ]
        );

        $factory = new PathStackResolverServiceFactory();
        /* @var $resolver PathStackResolver */
        $resolver = $factory->createService($serviceManager);
        $this->assertSame(
            [
                'path2/',
                'path1/',
            ],
            $resolver->getPaths()->toArray()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', []);

        $factory = new PathStackResolverServiceFactory();
        /* @var $resolver PathStackResolver */
        $resolver = $factory->createService($serviceManager);
        $this->assertEmpty($resolver->getPaths()->toArray());
    }
}
