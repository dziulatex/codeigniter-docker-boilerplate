<?php

namespace App\DTO;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WagonDTO
{
    private UuidInterface $id;
    private UuidInterface $coasterId;
    private int $iloscMiejsc;
    private float $predkoscWagonu;
    private ?string $lastRun;

    public function __construct(
        UuidInterface $coasterId,
        int $iloscMiejsc,
        float $predkoscWagonu,
        ?string $lastRun = null,
        ?UuidInterface $id = null
    ) {
        $this->id = $id ?? Uuid::uuid4();
        $this->coasterId = $coasterId;
        $this->iloscMiejsc = $iloscMiejsc;
        $this->predkoscWagonu = $predkoscWagonu;
        $this->lastRun = $lastRun;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'coaster_id' => $this->coasterId->toString(),
            'ilosc_miejsc' => $this->iloscMiejsc,
            'predkosc_wagonu' => $this->predkoscWagonu,
            'last_run' => $this->lastRun,
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id;
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

    public function getLastRun(): ?string
    {
        return $this->lastRun;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Uuid::fromString($data['coaster_id']),
            (int)$data['ilosc_miejsc'],
            $data['predkosc_wagonu'],
            $data['last_run'] ?? null,
            Uuid::fromString($data['id'])
        );
    }
}