<?php

namespace AssetManagerTest\Service;

use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetFilterManagerServiceFactory;
use AssetManager\Service\MimeResolver;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class AssetFilterManagerServiceFactoryTest extends TestCase
{
    public function testConstruct()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                'asset_manager' => [
                    'filters' => [
                        'css' => [
                            'filter' => 'Lessphp',
                        ],
                    ],
                ],
            ]
        );

        $serviceManager->setService(MimeResolver::class, new MimeResolver);

        $t = new AssetFilterManagerServiceFactory();

        $service = $t->createService($serviceManager);

        $this->assertTrue($service instanceof AssetFilterManager);
    }
}
