<?php

namespace App\Command;

class CreateCoasterCommand
{
    public function __construct(
        private readonly int $liczbaPersonelu,
        private readonly int $liczbaKlientow,
        private readonly float $dlTrasy,
        private readonly string $godzinyOd,
        private readonly string $godzinyDo
    ) {
    }

    public function getLiczbaPersonelu(): int
    {
        return $this->liczbaPersonelu;
    }

    public function getLiczbaKlientow(): int
    {
        return $this->liczbaKlientow;
    }

    public function getDlTrasy(): float
    {
        return $this->dlTrasy;
    }

    public function getGodzinyOd(): string
    {
        return $this->godzinyOd;
    }

    public function getGodzinyDo(): string
    {
        return $this->godzinyDo;
    }
}