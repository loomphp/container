<?php

declare(strict_types=1);

namespace LoomTest\Container\TestAsset;

class FlashMemory
{
    public $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
