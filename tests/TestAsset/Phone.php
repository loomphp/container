<?php

declare(strict_types=1);

namespace LoomTest\Container\TestAsset;

class Phone
{
    public $simCard;

    public function __construct(SimCard $simCard)
    {
        $this->simCard = $simCard;
    }
}
