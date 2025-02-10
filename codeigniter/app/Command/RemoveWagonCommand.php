<?php

namespace App\Command;

use Ramsey\Uuid\UuidInterface;

class RemoveWagonCommand
{
    public function __construct(
        private readonly UuidInterface $coasterId,
        private readonly UuidInterface $wagonId
    ) {
    }

    public function getCoasterId(): UuidInterface
    {
        return $this->coasterId;
    }

    public function getWagonId(): UuidInterface
    {
        return $this->wagonId;
    }
}