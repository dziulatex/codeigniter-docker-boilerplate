<?php

namespace Tests\Unit\Repository;

use App\DTO\CoasterDTO;
use App\DTO\WagonDTO;
use App\Helper\WaitForPromiseHelper;
use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Ramsey\Uuid\Uuid;

class WagonRepositoryTest extends CIUnitTestCase
{
    private WagonRepository $wagonRepository;
    private CoasterRepository $coasterRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $redis = Services::redis();
        WaitForPromiseHelper::wait($redis->flushDB());

        $this->wagonRepository = Services::wagonRepository();
        $this->coasterRepository = Services::coasterRepository();
    }

    public function testSaveSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        WaitForPromiseHelper::wait($this->coasterRepository->save($coasterDTO));
        $wagonDTO = new WagonDTO($coasterDTO->getId(), 24, 1.5);
        WaitForPromiseHelper::wait($this->wagonRepository->save($wagonDTO));

        $storedWagon = WaitForPromiseHelper::wait($this->wagonRepository->findById($wagonDTO->getId()));
        static::assertNotNull($storedWagon);
        static::assertEquals(24, $storedWagon->getIloscMiejsc());
        static::assertEquals(1.5, $storedWagon->getPredkoscWagonu());
        static::assertEquals($coasterDTO->getId(), $storedWagon->getCoasterId());
    }

    public function testFindByIdSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = WaitForPromiseHelper::wait($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagonDTO = new WagonDTO($coasterId, 24, 1.5);
        $wagonId = WaitForPromiseHelper::wait($this->wagonRepository->save($wagonDTO));
        static::assertNotEmpty($wagonId);

        $storedWagon = WaitForPromiseHelper::wait($this->wagonRepository->findById($wagonId));
        static::assertNotNull($storedWagon);
        static::assertEquals($wagonId, $storedWagon->getId());
    }

    public function testFindByIdNotFound(): void
    {
        $storedWagon = WaitForPromiseHelper::wait($this->wagonRepository->findById(Uuid::uuid4()));
        static::assertNull($storedWagon);
    }

    public function testDeleteSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = WaitForPromiseHelper::wait($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagonDTO = new WagonDTO($coasterId, 24, 1.5);
        $wagonId = WaitForPromiseHelper::wait($this->wagonRepository->save($wagonDTO));
        static::assertNotEmpty($wagonId);

        $deleteResult = WaitForPromiseHelper::wait($this->wagonRepository->delete($wagonId));
        static::assertTrue($deleteResult);

        $deletedWagon = WaitForPromiseHelper::wait($this->wagonRepository->findById($wagonId));
        static::assertNull($deletedWagon);
    }

    public function testDeleteNotFound(): void
    {
        $deleteResult = WaitForPromiseHelper::wait($this->wagonRepository->delete(Uuid::uuid4()));
        static::assertFalse($deleteResult);
    }

    public function testGetWagonsByCoasterSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = WaitForPromiseHelper::wait($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        // Create wagons for this coaster
        $wagonDTO1 = new WagonDTO($coasterId, 24, 1.5);
        $wagonId1 = WaitForPromiseHelper::wait($this->wagonRepository->save($wagonDTO1))->toString();
        static::assertNotEmpty($wagonId1);
        $wagonDTO2 = new WagonDTO($coasterId, 32, 1.8);
        $wagonId2 = WaitForPromiseHelper::wait($this->wagonRepository->save($wagonDTO2))->toString();
        static::assertNotEmpty($wagonId2);

        $wagons = WaitForPromiseHelper::wait($this->wagonRepository->getWagonsByCoaster($coasterId));
        static::assertIsArray($wagons);
        static::assertCount(2, $wagons);
        $wagonIds = array_map(static fn($wagon) => $wagon->getId()->toString(), $wagons);
        static::assertContains($wagonId1, $wagonIds);
        static::assertContains($wagonId2, $wagonIds);
    }

    public function testGetWagonsByCoasterEmpty(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = WaitForPromiseHelper::wait($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagons = WaitForPromiseHelper::wait($this->wagonRepository->getWagonsByCoaster($coasterId));
        static::assertIsArray($wagons);
        static::assertEmpty($wagons);
    }

    public function testUpdateLastRunSuccess(): void
    {
        // Create a coaster first
        $coasterDTO = new CoasterDTO(10, 5000, 1500, '09:00', '17:00');
        $coasterId = WaitForPromiseHelper::wait($this->coasterRepository->save($coasterDTO));
        static::assertNotEmpty($coasterId);

        $wagonDTO = new WagonDTO($coasterId, 24, 1.5);

        WaitForPromiseHelper::wait($this->wagonRepository->save($wagonDTO));

        $initialWagon = WaitForPromiseHelper::wait($this->wagonRepository->findById($wagonDTO->getId()));
        static::assertEmpty($initialWagon->getLastRun());

        $updateResult = WaitForPromiseHelper::wait($this->wagonRepository->updateLastRun($wagonDTO->getId()));
        static::assertTrue($updateResult);

        $updatedWagon = WaitForPromiseHelper::wait($this->wagonRepository->findById($wagonDTO->getId()));
        static::assertNotNull($updatedWagon->getLastRun());
    }

    public function testUpdateLastRunNotFound(): void
    {
        $updateResult = WaitForPromiseHelper::wait(
            $this->wagonRepository->updateLastRun(Uuid::uuid4())
        );
        static::assertFalse($updateResult);
    }
}