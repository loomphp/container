<?php

declare(strict_types=1);

namespace Loom\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class NotCreatedException extends RuntimeException implements ContainerExceptionInterface
{
}
