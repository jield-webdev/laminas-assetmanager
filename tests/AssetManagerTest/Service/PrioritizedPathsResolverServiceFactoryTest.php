<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\PrioritizedPathsResolver;
use AssetManager\Service\PrioritizedPathsResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class PrioritizedPathsResolverServiceFactoryTest extends TestCase
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
                        'prioritized_paths' => [
                            [
                                'path'     => 'dir3',
                                'priority' => 750,
                            ],
                            [
                                'path'     => 'dir2',
                                'priority' => 1000,
                            ],
                            [
                                'path'     => 'dir1',
                                'priority' => 500,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $factory = new PrioritizedPathsResolverServiceFactory();
        /* @var $resolver PrioritizedPathsResolver */
        $resolver = $factory->createService($serviceManager);

        $fetched = [];

        foreach ($resolver->getPaths() as $path) {
            $fetched[] = $path;
        }

        $this->assertSame(
            ['dir2' . DIRECTORY_SEPARATOR, 'dir3' . DIRECTORY_SEPARATOR, 'dir1' . DIRECTORY_SEPARATOR],
            $fetched
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', []);

        $factory = new PrioritizedPathsResolverServiceFactory();
        /* @var $resolver PrioritizedPathsResolver */
        $resolver = $factory->createService($serviceManager);
        $this->assertEmpty($resolver->getPaths()->toArray());
    }
}
