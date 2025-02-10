<?php

namespace App\Command;

use Ramsey\Uuid\UuidInterface;

class UpdateCoasterCommand
{
    public function __construct(
        private readonly UuidInterface $id,
        private readonly int $liczbaPersonelu,
        private readonly int $liczbaKlientow,
        private readonly string $godzinyOd,
        private readonly string $godzinyDo
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getLiczbaPersonelu(): int
    {
        return $this->liczbaPersonelu;
    }

    public function getLiczbaKlientow(): int
    {
        return $this->liczbaKlientow;
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