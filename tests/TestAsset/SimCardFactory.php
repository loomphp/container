<?php

declare(strict_types=1);

namespace LoomTest\Container\TestAsset;

use Psr\Container\ContainerInterface;

class SimCardFactory
{
    public function __invoke(ContainerInterface $container): SimCard
    {
        return new SimCard();
    }
}
