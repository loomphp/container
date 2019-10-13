<?php

declare(strict_types=1);

namespace LoomTest\Container\Factory;

use Loom\Container\Factory\InvokableFactory;
use LoomTest\Container\TestAsset\FlashMemory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class InvokableFactoryTest extends TestCase
{
    public function testCanCreateObject()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $factory   = new InvokableFactory();

        $object = $factory($container, FlashMemory::class, ['foo' => 'bar']);

        $this->assertInstanceOf(FlashMemory::class, $object);
        $this->assertEquals(['foo' => 'bar'], $object->options);
    }
}
