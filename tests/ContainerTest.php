<?php

declare(strict_types=1);

namespace LoomTest\Container;

use Loom\Container\Container;
use Loom\Container\Exception\CyclicAliasException;
use Loom\Container\Exception\NotCreatedException;
use Loom\Container\Factory\FactoryInterface;
use Loom\Container\Factory\InvokableFactory;
use LoomTest\Container\TestAsset\Contact;
use LoomTest\Container\TestAsset\FailingExceptionWithStringAsCodeFactory;
use LoomTest\Container\TestAsset\FailingFactory;
use LoomTest\Container\TestAsset\FlashMemory;
use LoomTest\Container\TestAsset\Phone;
use LoomTest\Container\TestAsset\PhoneFactory;
use LoomTest\Container\TestAsset\SimCard;
use LoomTest\Container\TestAsset\SimCardFactory;
use LoomTest\Container\TestAsset\SimCardInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerTest extends TestCase
{

    protected $container;

    public function createContainer(array $config = [])
    {
        $this->container = new Container($config);
        return $this->container;
    }

    public function testGetReturnsSharedInstance()
    {
        $container = $this->createContainer([
            'invokables' => [
                Contact::class => Contact::class
            ]
        ]);

        $object1 = $container->get(Contact::class);
        $object2 = $container->get(Contact::class);

        $this->assertSame($object1, $object2);
    }

    public function testBuildNeverSharesInstances()
    {
        $container = $this->createContainer([
            'invokables' => [
                Contact::class => Contact::class
            ]
        ]);

        $object1 = $container->build(Contact::class);
        $object2 = $container->build(Contact::class);

        $this->assertNotSame($object1, $object2);
    }

    public function testCanBuildObjectWithInvokableFactory()
    {
        $container = $this->createContainer([
            'factories' => [
                Contact::class => InvokableFactory::class
            ]
        ]);

        $object = $container->build(Contact::class);

        $this->assertInstanceOf(Contact::class, $object);
    }

    public function testCanBuildObjectWithOptionsInvokableFactory()
    {
        $container = $this->createContainer([
            'factories' => [
                FlashMemory::class => InvokableFactory::class
            ]
        ]);

        $object = $container->build(FlashMemory::class, ['foo' => 'bar']);

        $this->assertInstanceOf(FlashMemory::class, $object);
        $this->assertEquals(['foo' => 'bar'], $object->options);
    }

    public function testCanCreateObjectWithClosureFactory()
    {
        $container = $this->createContainer([
            'factories' => [
                Contact::class => function (ContainerInterface $container, $className) {
                    $this->assertEquals(Contact::class, $className);
                    return new Contact();
                }
            ]
        ]);

        $object = $container->get(Contact::class);
        $this->assertInstanceOf(Contact::class, $object);
    }

    public function testCanCreateServiceWithStringAlias()
    {
        $container = $this->createContainer([
            'aliases' => [
                'foo' => FlashMemory::class,
                'bar' => 'foo'
            ],
            'factories' => [
                FlashMemory::class => InvokableFactory::class
            ],
        ]);

        $object = $container->get('bar');

        $this->assertInstanceOf(FlashMemory::class, $object);
        $this->assertTrue($container->has('bar'));
        $this->assertFalse($container->has('baz'));
    }

    public function testCanCreateServiceWithInterfaceAlias()
    {
        $container = $this->createContainer([
            'aliases' => [
                SimCardInterface::class => SimCard::class,
            ],
            'factories' => [
                SimCard::class => SimCardFactory::class
            ],
        ]);

        $object = $container->get(SimCardInterface::class);

        $this->assertInstanceOf(SimCard::class, $object);
        $this->assertTrue($container->has(SimCard::class));
    }

    public function testThrowsExceptionIfServiceCannotBeCreated()
    {
        $container = $this->createContainer([
            'factories' => [
                Contact::class => FailingFactory::class
            ]
        ]);

        $this->expectException(NotCreatedException::class);

        $container->get(Contact::class);
    }

    public function testThrowExceptionWithStringAsCodeIfServiceCannotBeCreated()
    {
        $container = $this->createContainer([
            'factories' => [
                Contact::class => FailingExceptionWithStringAsCodeFactory::class
            ]
        ]);

        $this->expectException(NotCreatedException::class);

        $container->get(Contact::class);
    }

    public function testConfigureCanAddNewServices()
    {
        $container = $this->createContainer([
            'factories' => [
                FlashMemory::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($container->has(FlashMemory::class));
        $this->assertFalse($container->has(Contact::class));

        $newContainer = $container->add([
            'factories' => [
                Contact::class => InvokableFactory::class
            ]
        ]);

        $this->assertSame($container, $newContainer);

        $this->assertTrue($newContainer->has(FlashMemory::class));
        $this->assertTrue($newContainer->has(Contact::class));
    }

    public function testConfigureCanOverridePreviousSettings()
    {
        $firstFactory = $this->getMockBuilder(FactoryInterface::class)
            ->getMock();
        $secondFactory = $this->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $container = $this->createContainer([
            'factories' => [
                Contact::class => $firstFactory
            ]
        ]);

        $newContainer = $container->add([
            'factories' => [
                Contact::class => $secondFactory
            ]
        ]);

        $this->assertSame($container, $newContainer);

        $firstFactory->expects($this->never())->method('__invoke');
        $secondFactory->expects($this->once())->method('__invoke');

        $newContainer->get(Contact::class);
    }

    public function testHasReturnsFalseIfServiceNotConfigured()
    {
        $container = $this->createContainer([
            'factories' => [
                SimCard::class => SimCardFactory::class,
            ],
        ]);

        $this->assertFalse($container->has(Contact::class));
    }

    public function testHasReturnsTrueIfServiceIsConfigured()
    {
        $container = $this->createContainer([
            'services' => [
                Contact::class => new Contact(),
            ],
        ]);

        $this->assertTrue($container->has(Contact::class));
    }

    public function testHasReturnsTrueIfFactoryIsConfigured()
    {
        $container = $this->createContainer([
            'factories' => [
                Contact::class => InvokableFactory::class,
            ],
        ]);
        $this->assertTrue($container->has(Contact::class));
    }

    public function testCanConfigureAllServiceTypes()
    {
        $container = $this->createContainer([
            'services' => [
                'options' => ['foo' => 'bar'],
            ],
            'aliases' => [
                'alias' => FlashMemory::class,
                SimCardInterface::class => SimCard::class,
            ],
            'factories' => [
                SimCard::class => SimCardFactory::class,
                FlashMemory::class => InvokableFactory::class,
                Phone::class => PhoneFactory::class,
            ],
        ]);

        $options = $container->get('options');
        $this->assertIsArray($options, 'Options service did not resolve as expected');
        $this->assertSame(
            $options,
            $container->get('options'),
            'Options service resolved as unshared instead of shared'
        );

        $simCard = $container->get(SimCard::class);
        $this->assertInstanceOf(SimCard::class, $simCard, 'SimCard service did not resolve as expected');
        $this->assertSame(
            $simCard,
            $container->get(SimCard::class),
            'SimCard service should be shared, but resolved as unshared'
        );

        $alias = $container->get('alias');
        $this->assertInstanceOf(FlashMemory::class, $alias, 'Alias service did not resolve as expected');
        $this->assertSame(
            $alias,
            $container->get(FlashMemory::class),
            'Alias service should be shared, but resolved as unshared'
        );

        $phone = $container->get(Phone::class);
        $this->assertInstanceOf(Phone::class, $phone, 'Phone service did not resolve as expected');
        $this->assertSame(
            $phone,
            $container->get(Phone::class),
            'Phone service should be shared, but resolved as unshared'
        );
    }

    public function testGetRaisesExceptionWhenNoFactoryIsResolved()
    {
        $container = $this->createContainer();
        $this->expectException(NotCreatedException::class);
        $this->expectExceptionMessage('Unable to resolve');
        $container->get(SimCard::class);
    }

    public function testCanInjectServices()
    {
        $container = $this->createContainer();
        $container->setService('options', ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $container->get('options'));
    }

    public function testCanInjectFactories()
    {
        $simCard = new SimCard();
        $container = $this->createContainer();

        $container->setFactory('foo', function () use ($simCard) {
            return $simCard;
        });
        $this->assertTrue($container->has('foo'));
        $foo = $container->get('foo');
        $this->assertSame($simCard, $foo);
    }


    public function testCanInjectAliases()
    {
        $container = $this->createContainer([
            'factories' => [
                'foo' => function () {
                    return new Contact();
                }
            ],
        ]);

        $container->setAlias('bar', 'foo');

        $foo = $container->get('foo');
        $bar = $container->get('bar');
        $this->assertInstanceOf(Contact::class, $foo);
        $this->assertInstanceOf(Contact::class, $bar);
        $this->assertSame($foo, $bar);
    }

    public function testCanInjectInvokables()
    {
        $container = $this->createContainer();
        $container->setInvokable('foo', FlashMemory::class);
        $this->assertTrue($container->has('foo'));
        $this->assertTrue($container->has(FlashMemory::class));
        $foo = $container->get('foo');
        $this->assertInstanceOf(FlashMemory::class, $foo);
    }

    public function testCrashesOnCyclicAliases()
    {
        $this->expectException(CyclicAliasException::class);

        $this->createContainer([
            'aliases' => [
                'a' => 'b',
                'b' => 'a',
            ],
        ]);
    }

    public function testContainerIsAPsr11Container()
    {
        $container = $this->createContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testConfigurationTakesPrecedenceWhenMerged()
    {
        $factory = $this->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $factory->expects($this->once())->method('__invoke');

        $container = $this->createContainer([
            'factories' => [
                Contact::class => $factory
            ]
        ]);

        $container->get(Contact::class);
    }

    public function testMapsOneToOneInvokablesAsInvokableFactoriesInternally()
    {
        $config = [
            'invokables' => [
                Contact::class => Contact::class,
            ],
        ];

        $container = $this->createContainer($config);
        $this->assertAttributeSame([
            Contact::class => InvokableFactory::class,
        ], 'factories', $container, 'Invokable object factory not found');
    }

    public function testMapsNonSymmetricInvokablesAsAliasPlusInvokableFactory()
    {
        $config = [
            'invokables' => [
                'Invokable' => Contact::class,
            ],
        ];

        $container = $this->createContainer($config);
        $this->assertAttributeSame([
            'Invokable' => Contact::class,
        ], 'aliases', $container, 'Alias not found for non-symmetric invokable');
        $this->assertAttributeSame([
            Contact::class => InvokableFactory::class,
        ], 'factories', $container, 'Factory not found for non-symmetric invokable target');
    }

    public function testSharedServicesReferencingAliasShouldBeHonored()
    {
        $config = [
            'aliases' => [
                'Invokable' => Contact::class,
            ],
            'factories' => [
                Contact::class => InvokableFactory::class,
            ]
        ];

        $container = $this->createContainer($config);
        $instance1 = $container->build('Invokable');
        $instance2 = $container->build('Invokable');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testAliasToAnExplicitServiceShouldWork()
    {
        $config = [
            'aliases' => [
                'Invokable' => Contact::class,
            ],
            'services' => [
                Contact::class => new Contact(),
            ],
        ];

        $container = $this->createContainer($config);

        $service = $container->get(Contact::class);
        $alias = $container->get('Invokable');

        $this->assertSame($service, $alias);
    }

    public function testSetAliasShouldWorkWithRecursiveAlias()
    {
        $config = [
            'aliases' => [
                'Alias' => 'TailInvokable',
            ],
            'services' => [
                Contact::class => new Contact(),
            ],
        ];
        $container = $this->createContainer($config);
        $container->setAlias('HeadAlias', 'Alias');
        $container->setAlias('TailInvokable', Contact::class);

        $service = $container->get(Contact::class);
        $alias = $container->get('Alias');
        $headAlias = $container->get('HeadAlias');

        $this->assertSame($service, $alias);
        $this->assertSame($service, $headAlias);
    }

    public static function sampleFactory()
    {
        return new SimCard();
    }

    public function testFactoryMayBeStaticMethodDescribedByCallableString()
    {
        $config = [
            'factories' => [
                SimCard::class => 'LoomTest\Container\ContainerTest::sampleFactory',
            ]
        ];
        $container = $this->createContainer($config);
        $this->assertEquals(SimCard::class, get_class($container->get(SimCard::class)));
    }
}
