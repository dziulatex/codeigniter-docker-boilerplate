<?php

namespace Tests\Unit\Service;

use Config\Services;
use PHPUnit\Framework\TestCase;
use App\Service\CoasterProblemDetector;
use App\DTO\CoasterDTO;

class CoasterProblemDetectorTest extends TestCase
{
    private CoasterProblemDetector $detector;

    protected function setUp(): void
    {
        $this->detector = Services::coasterProblemDetector();
    }

    public function testOutsideOperatingHoursReturnsNoProblems()
    {
        $coaster = new CoasterDTO(4, 20, 600, '10:00', '18:00'); // Coaster operates from 10 AM to 6 PM
        $wagons = [1, 2]; // Assume 2 wagons
        static::assertEmpty($this->detector->detectProblems($coaster, $wagons));
    }

    public function testPersonnelShortageDetected()
    {
        $coaster = new CoasterDTO(2, 20, 600, date('H:i'), date('H:i', strtotime('+1 hour'))); // Within operating hours
        $wagons = [1, 2]; // 2 wagons

        $problems = $this->detector->detectProblems($coaster, $wagons);
        static::assertContains('Brakuje 2 pracowników', $problems);
    }

    public function testPersonnelSurplusDetected()
    {
        $coaster = new CoasterDTO(
            10, 20, 600, date('H:i'), date('H:i', strtotime('+1 hour'))
        ); // Within operating hours
        $wagons = [1, 2]; // 2 wagons

        $problems = $this->detector->detectProblems($coaster, $wagons);
        static::assertContains('6 pracowników za dużo', $problems);
    }

    public function testWagonShortageDetected()
    {
        $coaster = new CoasterDTO(
            4, 20, 1000, date('H:i'), date('H:i', strtotime('+1 hour'))
        ); // Within operating hours
        $wagons = [1]; // Only 1 wagon instead of expected 4

        $problems = $this->detector->detectProblems($coaster, $wagons);
        static::assertContains('Brak 3 wagonów', $problems);
    }

    public function testMultipleProblemsDetected()
    {
        $coaster = new CoasterDTO(
            3, 20, 1000, date('H:i'), date('H:i', strtotime('+1 hour'))
        ); // Within operating hours
        $wagons = [1]; // Only 1 wagon

        $problems = $this->detector->detectProblems($coaster, $wagons);
        static::assertContains('Brakuje 5 pracowników', $problems);
        static::assertContains('Brak 3 wagonów', $problems);
    }

    public function testOperationalStatus()
    {
        $coaster = new CoasterDTO(4, 20, 900, '06:00', '22:00'); // Operating 6 AM - 10 PM
        $status = $this->detector->getOperationalStatus($coaster);
        static::assertEquals(3, $status['expected_wagons']); // 900m → 3 wagons
        static::assertEquals(6, $status['required_personnel']); // 3 wagons → 6 staff
        static::assertTrue($status['is_operating']); // Should be operating now
    }
}