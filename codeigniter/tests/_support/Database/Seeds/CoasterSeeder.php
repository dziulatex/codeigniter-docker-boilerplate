<?php

namespace Tests\Support\Database\Seeds;

use App\DTO\CoasterDTO;
use App\DTO\WagonDTO;
use App\Repositories\CoasterRepository;
use App\Repositories\WagonRepository;
use CodeIgniter\Database\Seeder;

class CoasterSeeder extends Seeder
{
    private CoasterRepository $coasterRepository;
    private WagonRepository $wagonRepository;

    public function __construct()
    {
        $this->coasterRepository = new CoasterRepository();
        $this->wagonRepository = new WagonRepository();
    }

    public function run()
    {
        // Clear any existing data
        $redis = \Config\Services::redis();
        $redis->flushDB();

        // Create sample coasters
        $coasters = [
            [
                'liczba_personelu' => 16,
                'liczba_klientow' => 60000,
                'dl_trasy' => 1800,
                'godziny_od' => '08:00',
                'godziny_do' => '16:00',
                'wagons' => [
                    [
                        'ilosc_miejsc' => 32,
                        'predkosc_wagonu' => 1.2
                    ],
                    [
                        'ilosc_miejsc' => 28,
                        'predkosc_wagonu' => 1.5
                    ]
                ]
            ],
            [
                'liczba_personelu' => 24,
                'liczba_klientow' => 80000,
                'dl_trasy' => 2200,
                'godziny_od' => '09:00',
                'godziny_do' => '18:00',
                'wagons' => [
                    [
                        'ilosc_miejsc' => 36,
                        'predkosc_wagonu' => 1.8
                    ],
                    [
                        'ilosc_miejsc' => 36,
                        'predkosc_wagonu' => 1.8
                    ],
                    [
                        'ilosc_miejsc' => 36,
                        'predkosc_wagonu' => 1.8
                    ]
                ]
            ],
            [
                'liczba_personelu' => 12,
                'liczba_klientow' => 40000,
                'dl_trasy' => 1200,
                'godziny_od' => '10:00',
                'godziny_do' => '20:00',
                'wagons' => [
                    [
                        'ilosc_miejsc' => 24,
                        'predkosc_wagonu' => 1.0
                    ]
                ]
            ]
        ];

        foreach ($coasters as $coasterData) {
            $wagons = $coasterData['wagons'];
            unset($coasterData['wagons']);

            // Create coaster
            $coaster = new CoasterDTO(
                $coasterData['liczba_personelu'],
                $coasterData['liczba_klientow'],
                $coasterData['dl_trasy'],
                $coasterData['godziny_od'],
                $coasterData['godziny_do']
            );

            $this->coasterRepository->save($coaster);

            // Add wagons
            foreach ($wagons as $wagonData) {
                $wagon = new WagonDTO(
                    $coaster->getId(),
                    $wagonData['ilosc_miejsc'],
                    $wagonData['predkosc_wagonu']
                );
                $this->wagonRepository->save($wagon);
            }
        }
    }

    /**
     * Get seeded data for testing
     */
    public function getSeededData(): array
    {
        $redis = \Config\Services::redis();
        $coasterIds = $redis->sMembers('coasters');
        $seededData = [];

        foreach ($coasterIds as $coasterId) {
            $coasterData = $redis->hGetAll("coaster:{$coasterId}");
            $wagonIds = $redis->sMembers("coaster:{$coasterId}:wagons");

            $wagons = [];
            foreach ($wagonIds as $wagonId) {
                $wagons[] = $redis->hGetAll("wagon:{$wagonId}");
            }

            $coasterData['wagons'] = $wagons;
            $seededData[] = $coasterData;
        }

        return $seededData;
    }

    /**
     * Get a specific seeded coaster by index
     */
    public function getCoaster(int $index): ?array
    {
        $data = $this->getSeededData();
        return $data[$index] ?? null;
    }

    /**
     * Get a random coaster ID
     */
    public function getRandomCoasterId(): ?string
    {
        $redis = \Config\Services::redis();
        $coasterIds = $redis->sMembers('coasters');
        return $coasterIds[array_rand($coasterIds)] ?? null;
    }

    /**
     * Get a random wagon ID from a specific coaster
     */
    public function getRandomWagonId(string $coasterId): ?string
    {
        $redis = \Config\Services::redis();
        $wagonIds = $redis->sMembers("coaster:{$coasterId}:wagons");
        return $wagonIds[array_rand($wagonIds)] ?? null;
    }
}