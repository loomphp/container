<?php

declare(strict_types=1);

namespace LoomTest\Container\TestAsset;

use Psr\Container\ContainerInterface;

class PhoneFactory
{
    public function __invoke(ContainerInterface $container): Phone
    {
        return new Phone($container->get(SimCard::class));
    }
}
