<?php

declare(strict_types=1);

namespace LoomTest\Container\TestAsset;

use Psr\Container\ContainerInterface;

class FailingFactory
{
    public function __invoke(ContainerInterface $container)
    {
        throw new \RuntimeException('Error!');
    }
}
