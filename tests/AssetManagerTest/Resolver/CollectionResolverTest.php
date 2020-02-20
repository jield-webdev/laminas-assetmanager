<?php

namespace AssetManagerTest\Resolver;

use Assetic\Asset;
use Assetic\Asset\AssetCache;
use Assetic\Contracts\Cache\CacheInterface;
use AssetManager\Resolver\AggregateResolverAwareInterface;
use AssetManager\Resolver\CollectionResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\MimeResolver;
use PHPUnit\Framework\TestCase;

class CollectionsResolverTest extends TestCase
{
    public function getResolverMock()
    {
        $resolver = $this->getMock(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->with('bacon')
            ->will($this->returnValue(new Asset\FileAsset(__FILE__)));

        return $resolver;
    }

    public function testConstructor()
    {
        $resolver = new CollectionResolver();

        // Check if valid instance
        $this->assertTrue($resolver instanceof ResolverInterface);
        $this->assertTrue($resolver instanceof AggregateResolverAwareInterface);

        // Check if set to empty (null argument)
        $this->assertSame([], $resolver->getCollections());

        $resolver = new CollectionResolver([
            'key1' => ['value1'],
            'key2' => ['value2'],
        ]);
        $this->assertSame(
            [
                'key1' => ['value1'],
                'key2' => ['value2'],
            ],
            $resolver->getCollections()
        );
    }

    public function testSetCollections()
    {
        $resolver = new CollectionResolver();
        $collArr  = [
            'key1' => ['value1'],
            'key2' => ['value2'],
        ];

        $resolver->setCollections($collArr);

        $this->assertSame(
            $collArr,
            $resolver->getCollections()
        );

        // overwrite
        $collArr = [
            'key3' => ['value3'],
            'key4' => ['value4'],
        ];

        $resolver->setCollections($collArr);

        $this->assertSame(
            $collArr,
            $resolver->getCollections()
        );


        // Overwrite with traversable
        $resolver->setCollections(new CollectionsIterable());

        $collArr = [
            'collectionName1' => [
                'collection 1.1',
                'collection 1.2',
                'collection 1.3',
                'collection 1.4',
            ],
            'collectionName2' => [
                'collection 2.1',
                'collection 2.2',
                'collection 2.3',
                'collection 2.4',
            ],
            'collectionName3' => [
                'collection 3.1',
                'collection 3.2',
                'collection 3.3',
                'collection 3.4',
            ]
        ];

        $this->assertEquals($collArr, $resolver->getCollections());
    }

    /**
     * @expectedException \AssetManager\Exception\InvalidArgumentException
     */
    public function testSetCollectionFailsObject()
    {
        $resolver = new CollectionResolver();

        $resolver->setCollections(new \stdClass());
    }

    /**
     * @expectedException \AssetManager\Exception\InvalidArgumentException
     */
    public function testSetCollectionFailsString()
    {
        $resolver = new CollectionResolver();

        $resolver->setCollections('invalid');
    }

    public function testSetGetAggregateResolver()
    {
        $resolver = new CollectionResolver();

        $aggregateResolver = $this->getMock(ResolverInterface::class);
        $aggregateResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('say')
            ->will($this->returnValue('world'));

        $resolver->setAggregateResolver($aggregateResolver);

        $this->assertEquals('world', $resolver->getAggregateResolver()->resolve('say'));
    }

    public function testSetAggregateResolverFails()
    {
        $this->expectException('\TypeError');

        $resolver = new CollectionResolver();

        $resolver->setAggregateResolver(new \stdClass());
    }

    /**
     * Resolve
     */
    public function testResolveNoArgsEqualsNull()
    {
        $resolver = new CollectionResolver();

        $this->assertNull($resolver->resolve('bacon'));
    }

    /**
     * @expectedException \AssetManager\Exception\RuntimeException
     */
    public function testResolveNonArrayCollectionException()
    {
        $resolver = new CollectionResolver(['bacon' => 'bueno']);

        $resolver->resolve('bacon');
    }

    /**
     * @expectedException \AssetManager\Exception\RuntimeException
     */
    public function testCollectionItemNonString()
    {
        $resolver = new CollectionResolver([
            'bacon' => [new \stdClass()]
        ]);

        $resolver->resolve('bacon');

    }

    /**
     * @expectedException \AssetManager\Exception\RuntimeException
     */
    public function testCouldNotResolve()
    {
        $aggregateResolver = $this->getMock(ResolverInterface::class);
        $aggregateResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('bacon')
            ->will($this->returnValue(null));

        $resolver = new CollectionResolver([
            'myCollection' => ['bacon']
        ]);

        $resolver->setAggregateResolver($aggregateResolver);

        $resolver->resolve('myCollection');
    }

    /**
     * @expectedException \AssetManager\Exception\RuntimeException
     */
    public function testResolvesToNonAsset()
    {
        $aggregateResolver = $this->getMock(ResolverInterface::class);
        $aggregateResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('bacon')
            ->will($this->returnValue('invalid'));

        $resolver = new CollectionResolver([
            'myCollection' => ['bacon']
        ]);

        $resolver->setAggregateResolver($aggregateResolver);

        $resolver->resolve('myCollection');
    }

    /**
     * @expectedException \AssetManager\Exception\RuntimeException
     */
    public function testMimeTypesDontMatch()
    {
        $callbackInvocationCount = 0;
        $callback                = function () use (&$callbackInvocationCount) {

            $asset1 = new Asset\StringAsset('bacon');
            $asset2 = new Asset\StringAsset('eggs');
            $asset3 = new Asset\StringAsset('Mud');

            $asset1->mimetype = 'text/plain';
            $asset2->mimetype = 'text/css';
            $asset3->mimetype = 'text/javascript';

            $callbackInvocationCount += 1;
            $assetName               = "asset$callbackInvocationCount";
            return $$assetName;
        };

        $aggregateResolver = $this->getMock(ResolverInterface::class);
        $aggregateResolver
            ->expects($this->exactly(2))
            ->method('resolve')
            ->will($this->returnCallback($callback));

        $assetFilterManager = $this->getMock(AssetFilterManager::class);
        $assetFilterManager
            ->expects($this->once())
            ->method('setFilters')
            ->will($this->returnValue(null));

        $resolver = new CollectionResolver([
            'myCollection' => [
                'bacon',
                'eggs',
                'mud',
            ]
        ]);

        $resolver->setAggregateResolver($aggregateResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $resolver->resolve('myCollection');
    }

    public function testTwoCollectionsHasDifferentCacheKey()
    {
        $aggregateResolver = $this->getMock(ResolverInterface::class);

        //assets with same 'last modifled time'.
        $now   = time();
        $bacon = new Asset\StringAsset('bacon');
        $bacon->setLastModified($now);
        $bacon->mimetype = 'text/plain';

        $eggs = new Asset\StringAsset('eggs');
        $eggs->setLastModified($now);
        $eggs->mimetype = 'text/plain';

        $assets = [
            ['bacon', $bacon],
            ['eggs', $eggs],
        ];

        $aggregateResolver
            ->expects($this->any())
            ->method('resolve')
            ->will($this->returnValueMap($assets));

        $resolver = new CollectionResolver([
            'collection1' => [
                'bacon',
            ],
            'collection2' => [
                'eggs',
            ],
        ]);

        $mimeResolver       = new MimeResolver();
        $assetFilterManager = new AssetFilterManager();
        $assetFilterManager->setMimeResolver($mimeResolver);

        $resolver->setAggregateResolver($aggregateResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $collection1 = $resolver->resolve('collection1');
        $collection2 = $resolver->resolve('collection2');

        $cacheInterface = $this->getMock(CacheInterface::class);

        $cacheKeys = new \ArrayObject();
        $callback  = function ($key) use ($cacheKeys) {
            $cacheKeys[] = $key;
            return true;
        };

        $cacheInterface
            ->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnCallback($callback));

        $cacheInterface
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue('cached content'));

        $cache1 = new AssetCache($collection1, $cacheInterface);
        $cache1->load();

        $cache2 = new AssetCache($collection2, $cacheInterface);
        $cache2->load();

        $this->assertCount(2, $cacheKeys);
        $this->assertNotEquals($cacheKeys[0], $cacheKeys[1]);
    }

    public function testSuccessResolve()
    {
        $callbackInvocationCount = 0;
        $callback                = function () use (&$callbackInvocationCount) {

            $asset1 = new Asset\StringAsset('bacon');
            $asset2 = new Asset\StringAsset('eggs');
            $asset3 = new Asset\StringAsset('Mud');

            $asset1->mimetype = 'text/plain';
            $asset2->mimetype = 'text/plain';
            $asset3->mimetype = 'text/plain';

            $callbackInvocationCount += 1;
            $assetName               = "asset$callbackInvocationCount";
            return $$assetName;
        };

        $aggregateResolver = $this->getMock(ResolverInterface::class);
        $aggregateResolver
            ->expects($this->exactly(3))
            ->method('resolve')
            ->will($this->returnCallback($callback));

        $resolver = new CollectionResolver([
            'myCollection' => [
                'bacon',
                'eggs',
                'mud',
            ]
        ]);


        $mimeResolver       = new MimeResolver();
        $assetFilterManager = new AssetFilterManager();

        $assetFilterManager->setMimeResolver($mimeResolver);

        $resolver->setAggregateResolver($aggregateResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $collectionResolved = $resolver->resolve('myCollection');

        $this->assertEquals($collectionResolved->mimetype, 'text/plain');
        $this->assertTrue($collectionResolved instanceof Asset\AssetCollection);
    }

    /**
     * Test Collect returns valid list of assets
     *
     * @covers \AssetManager\Resolver\CollectionResolver::collect
     */
    public function testCollect()
    {
        $collections = [
            'myCollection'   => [
                'bacon',
                'eggs',
                'mud',
            ],
            'my/collect.ion' => [
                'bacon',
                'eggs',
                'mud',
            ],
        ];
        $resolver    = new CollectionResolver($collections);

        $this->assertEquals(array_keys($collections), $resolver->collect());
    }
}
