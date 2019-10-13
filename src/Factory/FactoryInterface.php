<?php

declare(strict_types=1);

namespace Loom\Container\Factory;

use Loom\Container\Exception\ContainerException;
use Loom\Container\Exception\NotCreatedException;
use Loom\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;

interface FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return object
     * @throws NotFoundException if unable to resolve the service.
     * @throws NotCreatedException if an exception is raised when creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null);
}
