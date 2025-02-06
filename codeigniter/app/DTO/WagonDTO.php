<?php

namespace App\DTO;

use Ramsey\Uuid\Uuid;

class WagonDTO
{
    private string $id;
    private string $coasterId;
    private int $iloscMiejsc;
    private float $predkoscWagonu;
    private ?string $lastRun;

    public function __construct(
        string $coasterId,
        int $iloscMiejsc,
        float $predkoscWagonu,
        ?string $lastRun = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->coasterId = $coasterId;
        $this->iloscMiejsc = $iloscMiejsc;
        $this->predkoscWagonu = $predkoscWagonu;
        $this->lastRun = $lastRun;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'coaster_id' => $this->coasterId,
            'ilosc_miejsc' => $this->iloscMiejsc,
            'predkosc_wagonu' => $this->predkoscWagonu,
            'last_run' => $this->lastRun,
        ];
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getCoasterId(): string
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

    public function getLastRun(): ?string
    {
        return $this->lastRun;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['coaster_id'],
            (int)$data['ilosc_miejsc'],
            $data['predkosc_wagonu'],
            $data['last_run'] ?? null,
            $data['id'] ?? null
        );
    }
}