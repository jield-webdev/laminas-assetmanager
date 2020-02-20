<?php

namespace AssetManagerTest\Cache;

use AssetManager\Cache\LaminasCacheAdapter;
use Laminas\Cache\Storage\Adapter\Memory;
use PHPUnit\Framework\TestCase;

/**
 * Test file for Laminas Cache Adapter
 *
 * @package AssetManager\Cache
 */
class LaminasCacheAdapterTest extends TestCase
{
    public function testConstructor()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapter = new LaminasCacheAdapter($mockLaminasCache);

        $this->assertInstanceOf(LaminasCacheAdapter::class, $adapter);
    }

    public function testHasMethodCallsLaminasCacheHasItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects($this->once())
            ->method('hasItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->has('SomeKey');
    }

    public function testGetMethodCallsLaminasCacheGetItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects($this->once())
            ->method('getItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->get('SomeKey');
    }

    public function testSetMethodCallsLaminasCacheSetItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects($this->once())
            ->method('setItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->set('SomeKey', []);
    }

    public function testRemoveMethodCallsLaminasCacheRemoveItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects($this->once())
            ->method('removeItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->remove('SomeKey');
    }
}
