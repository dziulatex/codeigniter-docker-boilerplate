<?php

namespace integration\Controllers\Api;

use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

class CoasterControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private CoasterRepository $coasterRepository;
    private WagonRepository $wagonRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $redis = Services::redis();
        Services::waitForPromise($redis->flushDB());

        $this->coasterRepository = new CoasterRepository();
        $this->wagonRepository = new WagonRepository();
    }

    public function testCreateCoasterSuccess(): void
    {
        $data = [
            'liczba_personelu' => 16,
            'liczba_klientow' => 60000,
            'dl_trasy' => 1800,
            'godziny_od' => '08:00',
            'godziny_do' => '16:00'
        ];

        $result = $this->withBodyFormat('json')
            ->post('api/coasters', $data);

        $result->assertStatus(201);
        $response = json_decode($result->getJSON(), true);
        $coasterId = $response['id'];

        $storedCoaster = Services::waitForPromise(
            $this->coasterRepository->findById($coasterId)
        );
        static::assertNotNull($storedCoaster);
        static::assertEquals($data['liczba_personelu'], $storedCoaster->getLiczbaPersonelu());
        static::assertEquals($data['liczba_klientow'], $storedCoaster->getLiczbaKlientow());
        static::assertEquals($data['dl_trasy'], $storedCoaster->getDlTrasy());
        static::assertEquals($data['godziny_od'], $storedCoaster->getGodzinyOd());
        static::assertEquals($data['godziny_do'], $storedCoaster->getGodzinyDo());
    }

    public function testUpdateCoasterSuccess(): void
    {
        $initialData = [
            'liczba_personelu' => 16,
            'liczba_klientow' => 60000,
            'dl_trasy' => 1800,
            'godziny_od' => '08:00',
            'godziny_do' => '16:00'
        ];

        $createResult = $this->withBodyFormat('json')
            ->post('api/coasters', $initialData);
        $createResponse = json_decode($createResult->getJSON(), true);
        $coasterId = $createResponse['id'];

        $updateData = [
            'liczba_personelu' => 20,
            'liczba_klientow' => 70000,
            'dl_trasy' => 1800,
            'godziny_od' => '09:00',
            'godziny_do' => '17:00'
        ];

        $result = $this->withBodyFormat('json')
            ->put("api/coasters/$coasterId", $updateData);

        $result->assertStatus(200);

        $storedCoaster = Services::waitForPromise(
            $this->coasterRepository->findById($coasterId)
        );

        static::assertNotNull($storedCoaster);
        static::assertEquals($updateData['liczba_personelu'], $storedCoaster->getLiczbaPersonelu());
        static::assertEquals($updateData['liczba_klientow'], $storedCoaster->getLiczbaKlientow());
        static::assertEquals($initialData['dl_trasy'], $storedCoaster->getDlTrasy());
    }

    public function testAddWagonSuccess(): void
    {
        $coasterData = [
            'liczba_personelu' => 16,
            'liczba_klientow' => 60000,
            'dl_trasy' => 1800,
            'godziny_od' => '08:00',
            'godziny_do' => '16:00'
        ];

        $createResult = $this->withBodyFormat('json')
            ->post('api/coasters', $coasterData);
        $createResponse = json_decode($createResult->getJSON(), true);
        $coasterId = $createResponse['id'];

        $wagonData = [
            'ilosc_miejsc' => 32,
            'predkosc_wagonu' => 1.2
        ];

        $result = $this->withBodyFormat('json')
            ->post("api/coasters/$coasterId/wagons", $wagonData);

        $result->assertStatus(201);
        $response = json_decode($result->getJSON(), true);
        $wagonId = $response['id'];

        $storedWagon = Services::waitForPromise(
            $this->wagonRepository->findById($wagonId)
        );

        static::assertNotNull($storedWagon);
        static::assertEquals($wagonData['ilosc_miejsc'], $storedWagon->getIloscMiejsc());
        static::assertEquals($wagonData['predkosc_wagonu'], $storedWagon->getPredkoscWagonu());
        static::assertEquals($coasterId, $storedWagon->getCoasterId());
    }

    public function testRemoveWagonSuccess(): void
    {
        $coasterData = [
            'liczba_personelu' => 16,
            'liczba_klientow' => 60000,
            'dl_trasy' => 1800,
            'godziny_od' => '08:00',
            'godziny_do' => '16:00'
        ];

        $createResult = $this->withBodyFormat('json')
            ->post('api/coasters', $coasterData);
        $createResponse = json_decode($createResult->getJSON(), true);
        $coasterId = $createResponse['id'];

        $wagonData = [
            'ilosc_miejsc' => 32,
            'predkosc_wagonu' => 1.2
        ];

        $addWagonResult = $this->withBodyFormat('json')
            ->post("api/coasters/$coasterId/wagons", $wagonData);
        $wagonResponse = json_decode($addWagonResult->getJSON(), true);
        $wagonId = $wagonResponse['id'];

        $result = $this->delete("api/coasters/$coasterId/wagons/$wagonId");
        $result->assertStatus(200);

        $deletedWagon = Services::waitForPromise(
            $this->wagonRepository->findById($wagonId)
        );
        static::assertNull($deletedWagon);

        $coasterWagons = Services::waitForPromise(
            $this->wagonRepository->getWagonsByCoaster($coasterId)
        );
        static::assertEmpty($coasterWagons);
    }

    public function testCreateCoasterValidationFailure(): void
    {
        $data = [
            'liczba_personelu' => -1,
            'liczba_klientow' => 60000,
            'dl_trasy' => 1800,
            'godziny_od' => '08:00',
            'godziny_do' => '16:00'
        ];

        $result = $this->withBodyFormat('json')
            ->post('api/coasters', $data);

        $result->assertStatus(400);
        $response = json_decode($result->getJSON(), true);
        static::assertArrayHasKey('messages', $response);
        static::assertArrayHasKey('liczba_personelu', $response['messages']);
    }
}