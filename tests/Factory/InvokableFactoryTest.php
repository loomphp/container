<?php

declare(strict_types=1);

namespace LoomTest\Container\Factory;

use Loom\Container\Factory\InvokableFactory;
use LoomTest\Container\TestAsset\InvokableObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class InvokableFactoryTest extends TestCase
{
    public function testCanCreateObject()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $factory   = new InvokableFactory();

        $object = $factory($container, InvokableObject::class, ['foo' => 'bar']);

        $this->assertInstanceOf(InvokableObject::class, $object);
        $this->assertEquals(['foo' => 'bar'], $object->options);
    }
}
