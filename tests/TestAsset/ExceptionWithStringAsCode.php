<?php

declare(strict_types=1);

namespace LoomTest\Container\TestAsset;

use Exception;

class ExceptionWithStringAsCode extends Exception
{
    /** @var string */
    protected $code = 'ExceptionString';
}
