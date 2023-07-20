<?php

declare(strict_types=1);

namespace LaminasTest\View\Helper\TestAsset;

class PartialLoopToArrayImplementor
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
