<?php

namespace Tests\Unit\Repository;

use App\DTO\CoasterDTO;
use App\DTO\WagonDTO;
use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class WagonRepositoryTest extends CIUnitTestCase
{
    private WagonRepository $wagonRepository;
    private CoasterRepository $coasterRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $redis = Services::redis();
        Services::waitForPromise($redis->flushDB());

        $this->wagonRepository = new WagonRepository();
        $this->coasterRepository = new CoasterRepository();
    }

    public function testSaveSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        Services::waitForPromise($this->coasterRepository->save($coasterDTO));
        $wagonDTO = new WagonDTO($coasterDTO->getId(), 24, 1.5);
        Services::waitForPromise($this->wagonRepository->save($wagonDTO));

        $storedWagon = Services::waitForPromise($this->wagonRepository->findById($wagonDTO->getId()));
        static::assertNotNull($storedWagon);
        static::assertEquals(24, $storedWagon->getIloscMiejsc());
        static::assertEquals(1.5, $storedWagon->getPredkoscWagonu());
        static::assertEquals($coasterDTO->getId(), $storedWagon->getCoasterId());
    }

    public function testFindByIdSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = Services::waitForPromise($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagonDTO = new WagonDTO($coasterId, 24, 1.5);
        $wagonId = Services::waitForPromise($this->wagonRepository->save($wagonDTO));
        static::assertNotEmpty($wagonId);

        $storedWagon = Services::waitForPromise($this->wagonRepository->findById($wagonId));
        static::assertNotNull($storedWagon);
        static::assertEquals($wagonId, $storedWagon->getId());
    }

    public function testFindByIdNotFound(): void
    {
        $storedWagon = Services::waitForPromise($this->wagonRepository->findById('non-existent-wagon-id'));
        static::assertNull($storedWagon);
    }

    public function testDeleteSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = Services::waitForPromise($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagonDTO = new WagonDTO($coasterId, 24, 1.5);
        $wagonId = Services::waitForPromise($this->wagonRepository->save($wagonDTO));
        static::assertNotEmpty($wagonId);

        $deleteResult = Services::waitForPromise($this->wagonRepository->delete($wagonId));
        static::assertTrue($deleteResult);

        $deletedWagon = Services::waitForPromise($this->wagonRepository->findById($wagonId));
        static::assertNull($deletedWagon);
    }

    public function testDeleteNotFound(): void
    {
        $deleteResult = Services::waitForPromise($this->wagonRepository->delete('non-existent-wagon-id'));
        static::assertFalse($deleteResult);
    }

    public function testGetWagonsByCoasterSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = Services::waitForPromise($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        // Create wagons for this coaster
        $wagonDTO1 = new WagonDTO($coasterId, 24, 1.5);
        $wagonId1 = Services::waitForPromise($this->wagonRepository->save($wagonDTO1));
        static::assertNotEmpty($wagonId1);
        $wagonDTO2 = new WagonDTO($coasterId, 32, 1.8);
        $wagonId2 = Services::waitForPromise($this->wagonRepository->save($wagonDTO2));
        static::assertNotEmpty($wagonId2);

        $wagons = Services::waitForPromise($this->wagonRepository->getWagonsByCoaster($coasterId));
        static::assertIsArray($wagons);
        static::assertCount(2, $wagons);
        $wagonIds = array_map(fn($wagon) => $wagon->getId(), $wagons);
        static::assertContains($wagonId1, $wagonIds);
        static::assertContains($wagonId2, $wagonIds);
    }

    public function testGetWagonsByCoasterEmpty(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = Services::waitForPromise($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagons = Services::waitForPromise($this->wagonRepository->getWagonsByCoaster($coasterId));
        static::assertIsArray($wagons);
        static::assertEmpty($wagons);
    }

    public function testUpdateLastRunSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = Services::waitForPromise($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagonDTO = new WagonDTO($coasterId, 24, 1.5);

        Services::waitForPromise($this->wagonRepository->save($wagonDTO));

        $initialWagon = Services::waitForPromise($this->wagonRepository->findById($wagonDTO->getId()));
        static::assertEmpty($initialWagon->getLastRun());

        $updateResult = Services::waitForPromise($this->wagonRepository->updateLastRun($wagonDTO->getId()));
        static::assertTrue($updateResult);

        $updatedWagon = Services::waitForPromise($this->wagonRepository->findById($wagonDTO->getId()));
        static::assertNotNull($updatedWagon->getLastRun());
    }

    public function testUpdateLastRunNotFound(): void
    {
        $updateResult = Services::waitForPromise($this->wagonRepository->updateLastRun('non-existent-wagon-id'));
        static::assertFalse($updateResult);
    }
}