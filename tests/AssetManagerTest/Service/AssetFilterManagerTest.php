<?php

namespace AssetManagerTest\Service;

use Assetic\Asset\StringAsset;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\FilterInterface;
use AssetManager\Service\AssetFilterManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class AssetFilterManagerTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        require_once __DIR__ . '/../../_files/CustomFilter.php';
    }

    public function testNulledValuesAreSkipped()
    {
        $assetFilterManager = new AssetFilterManager([
            'test/path.test' => [
                'null_filters' => null
            ]
        ]);

        $asset = new StringAsset('Herp Derp');

        $assetFilterManager->setFilters('test/path.test', $asset);

        $this->assertEquals('Herp Derp', $asset->dump());
    }

    public function testensureByService()
    {
        $assetFilterManager = new AssetFilterManager([
            'test/path.test' => [
                [
                    'service' => 'testFilter',
                ],
            ],
        ]);

        $serviceManager = new ServiceManager();
        $serviceManager->setService('testFilter', new \CustomFilter());
        $assetFilterManager->setServiceLocator($serviceManager);

        $asset = new StringAsset('Herp derp');

        $assetFilterManager->setFilters('test/path.test', $asset);

        $this->assertEquals('called', $asset->dump());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testensureByServiceInvalid()
    {
        $assetFilterManager = new AssetFilterManager([
            'test/path.test' => [
                [
                    'service' => 9,
                ],
            ],
        ]);

        $serviceManager = new ServiceManager();
        $serviceManager->setService('testFilter', new \CustomFilter());
        $assetFilterManager->setServiceLocator($serviceManager);

        $asset = new StringAsset('Herp derp');

        $assetFilterManager->setFilters('test/path.test', $asset);

        $this->assertEquals('called', $asset->dump());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testensureByInvalid()
    {
        $assetFilterManager = new AssetFilterManager([
            'test/path.test' => [
                [
                ],
            ],
        ]);

        $asset = new StringAsset('Herp derp');

        $assetFilterManager->setFilters('test/path.test', $asset);
    }

    public function testFiltersAreInstantiatedOnce()
    {
        $assetFilterManager = new AssetFilterManager([
            'test/path.test' => [
                [
                    'filter' => 'CustomFilter'
                ],
            ],
        ]);

        $filterInstance = null;

        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();
        $asset
            ->expects($this->any())
            ->method('ensureFilter')
            ->with($this->callback(function (FilterInterface $filter) use (&$filterInstance) {
                if ($filterInstance === null) {
                    $filterInstance = $filter;
                }
                return $filter === $filterInstance;
            }));

        $assetFilterManager->setFilters('test/path.test', $asset);
        $assetFilterManager->setFilters('test/path.test', $asset);
    }
}
