<?php

declare(strict_types=1);

namespace LoomTest\Container\TestAsset;

use Psr\Container\ContainerInterface;

class FailingExceptionWithStringAsCodeFactory
{
    public function __invoke(ContainerInterface $container)
    {
        throw (new ExceptionWithStringAsCode('There is an error'));
    }
}
