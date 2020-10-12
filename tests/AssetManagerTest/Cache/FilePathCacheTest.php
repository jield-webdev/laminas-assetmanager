<?php

namespace AssetManagerTest\Cache;

use Assetic\Cache\CacheInterface;
use AssetManager\Cache\FilePathCache;
use AssetManager\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FilePathCacheTest extends TestCase
{
    public function testConstruct()
    {
        $cache = new FilePathCache('/imagination', 'bacon.porn');
        $this->assertTrue($cache instanceof CacheInterface);
    }

    public function testHas()
    {
        // Check fail
        $cache = new FilePathCache('/imagination', 'bacon.porn');
        $this->assertFalse($cache->has('bacon'));

        // Check success
        $cache = new FilePathCache('', __FILE__);
        $this->assertTrue($cache->has('bacon'));
    }

    public function testGetException()
    {
        $this->expectException(RuntimeException::class);
        $cache = new FilePathCache('/imagination', 'bacon.porn');
        $cache->get('bacon');
    }

    public function testGet()
    {
        $cache = new FilePathCache('', __FILE__);
        $this->assertEquals(file_get_contents(__FILE__), $cache->get('bacon'));
    }

    public function testSetMayNotWriteFile()
    {
        $this->expectException(RuntimeException::class);
        restore_error_handler(); // Previous test fails, so doesn't unset.
        $time = time();
        $sentence = 'I am, what I am. Cached data, please don\'t hate, '
            . 'for we are all equals. Except you, you\'re a dick.';
        $base = '/tmp/_cachetest.' . $time . '/';
        mkdir($base, 0777);
        mkdir($base . 'readonly', 0400, true);

        $cache = new FilePathCache($base . 'readonly', 'bacon.' . $time . '.hammertime');
        $cache->set('bacon', $sentence);
    }

    public function testSetMayNotWriteDir()
    {
        $this->expectException(RuntimeException::class);
        restore_error_handler(); // Previous test fails, so doesn't unset.
        $time = time() + 1;
        $sentence = 'I am, what I am. Cached data, please don\'t hate, '
            . 'for we are all equals. Except you, you\'re a dick.';
        $base = '/tmp/_cachetest.' . $time . '/';
        mkdir($base, 0400, true);

        $cache = new FilePathCache($base . 'readonly', 'bacon.' . $time . '.hammertime');

        $cache->set('bacon', $sentence);

    }

    public function testSetCanNotWriteToFileThatExists()
    {
        $this->expectException(RuntimeException::class);
        restore_error_handler(); // Previous test fails, so doesn't unset.
        $time = time() + 333;
        $sentence = 'I am, what I am. Cached data, please don\'t hate, '
            . 'for we are all equals. Except you, you\'re a dick.';
        $base = '/tmp/_cachetest.' . $time . '/';
        mkdir($base, 0777);

        $fileName = 'sausage.' . $time . '.iceicebaby';

        touch($base . 'AssetManagerFilePathCache_' . $fileName);
        chmod($base . 'AssetManagerFilePathCache_' . $fileName, 0400);

        $cache = new FilePathCache($base, $fileName);

        $cache->set('bacon', $sentence);
    }

    public function testSetSuccess()
    {
        $time = time();
        $sentence = 'I am, what I am. Cached data, please don\'t hate, '
            . 'for we are all equals. Except you, you\'re a dick.';
        $base = '/tmp/_cachetest.' . $time . '/';
        $cache = new FilePathCache($base, 'bacon.' . $time);

        $cache->set('bacon', $sentence);
        $this->assertEquals($sentence, file_get_contents($base . 'bacon.' . $time));
    }

    public function testRemoveFails()
    {
        $this->expectException(RuntimeException::class);
        $cache = new FilePathCache('/dev', 'null');

        $cache->remove('bacon');
    }

    public function testRemoveSuccess()
    {
        $time = time();
        $sentence = 'I am, what I am. Cached data, please don\'t hate, '
            . 'for we are all equals. Except you, you\'re a dick.';
        $base = '/tmp/_cachetest.' . $time . '/';
        $cache = new FilePathCache($base, 'bacon.' . $time);

        $cache->set('bacon', $sentence);

        $this->assertTrue($cache->remove('bacon'));
    }

    public function testCachedFile()
    {
        $method = new ReflectionMethod(FilePathCache::class, 'cachedFile');

        $method->setAccessible(true);

        $this->assertEquals(
            '/' . ltrim(__FILE__, '/'),
            $method->invoke(new FilePathCache('', __FILE__))
        );
    }
}
