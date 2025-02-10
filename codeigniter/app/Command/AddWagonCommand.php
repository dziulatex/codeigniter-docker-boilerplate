<?php

namespace App\Command;

use Ramsey\Uuid\UuidInterface;

class AddWagonCommand
{
    public function __construct(
        private readonly UuidInterface $coasterId,
        private readonly int $iloscMiejsc,
        private readonly float $predkoscWagonu
    ) {
    }

    public function getCoasterId(): UuidInterface
    {
        return $this->coasterId;
    }

    public function getIloscMiejsc(): int
    {
        return $this->iloscMiejsc;
    }

    public function getPredkoscWagonu(): float
    {
        return $this->predkoscWagonu;
    }
}