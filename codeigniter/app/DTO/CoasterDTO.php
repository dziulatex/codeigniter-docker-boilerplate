<?php

namespace App\DTO;

use Ramsey\Uuid\Uuid;

class CoasterDTO
{
    private string $id;
    private int $liczbaPersonelu;
    private int $liczbaKlientow;
    private float $dlTrasy;
    private string $godzinyOd;
    private string $godzinyDo;

    public function __construct(
        int $liczbaPersonelu,
        int $liczbaKlientow,
        float $dlTrasy,
        string $godzinyOd,
        string $godzinyDo,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->liczbaPersonelu = $liczbaPersonelu;
        $this->liczbaKlientow = $liczbaKlientow;
        $this->dlTrasy = $dlTrasy;
        $this->godzinyOd = $godzinyOd;
        $this->godzinyDo = $godzinyDo;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'liczba_personelu' => $this->liczbaPersonelu,
            'liczba_klientow' => $this->liczbaKlientow,
            'dl_trasy' => $this->dlTrasy,
            'godziny_od' => $this->godzinyOd,
            'godziny_do' => $this->godzinyDo,
        ];
    }

    // Getters
    public function getId(): string
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

    public static function fromArray(array $data): self
    {
        return new self(
            (int)$data['liczba_personelu'],
            (int)$data['liczba_klientow'],
            $data['dl_trasy'],
            $data['godziny_od'],
            $data['godziny_do'],
            $data['id'] ?? null
        );
    }
}