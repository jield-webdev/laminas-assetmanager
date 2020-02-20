<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\AggregateResolver;
use AssetManager\Resolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class AggregateResolverTest extends TestCase
{
    public function testResolve()
    {
        $resolver = new AggregateResolver();

        $this->assertTrue($resolver instanceof ResolverInterface);

        $lowPriority = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $lowPriority
            ->expects($this->once())
            ->method('resolve')
            ->with('to-be-resolved')
            ->will($this->returnValue('first'));
        $resolver->attach($lowPriority);

        $this->assertSame('first', $resolver->resolve('to-be-resolved'));

        $highPriority = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $highPriority
            ->expects($this->exactly(2))
            ->method('resolve')
            ->with('to-be-resolved')
            ->will($this->returnValue('second'));
        $resolver->attach($highPriority, 1000);

        $this->assertSame('second', $resolver->resolve('to-be-resolved'));

        $averagePriority = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $averagePriority
            ->expects($this->never())
            ->method('resolve')
            ->will($this->returnValue('third'));
        $resolver->attach($averagePriority, 500);

        $this->assertSame('second', $resolver->resolve('to-be-resolved'));
    }

    public function testCollectWithCollectMethod()
    {
        $resolver    = new AggregateResolver();
        $lowPriority = $this->getMock(ResolverInterface::class, ['resolve', 'collect']);
        $lowPriority
            ->expects($this->exactly(2))
            ->method('collect')
            ->will($this->returnValue(['one', 'two']));
        $resolver->attach($lowPriority);

        $this->assertContains('one', $resolver->collect());

        $highPriority = $this->getMock(ResolverInterface::class, ['resolve', 'collect']);
        $highPriority
            ->expects($this->once())
            ->method('collect')
            ->will($this->returnValue(['three']));
        $resolver->attach($highPriority, 1000);

        $collection = $resolver->collect();
        $this->assertContains('one', $collection);
        $this->assertContains('three', $collection);

        $this->assertCount(3, $collection);
    }

    public function testCollectWithoutCollectMethod()
    {
        $resolver    = new AggregateResolver();
        $lowPriority = $this->getMock(ResolverInterface::class);

        $resolver->attach($lowPriority);

        $this->assertEquals([], $resolver->collect());

        $highPriority = $this->getMock(ResolverInterface::class);
        $resolver->attach($highPriority, 1000);

        $collection = $resolver->collect();
        $this->assertEquals([], $collection);

        $this->assertCount(0, $collection);
    }
}
