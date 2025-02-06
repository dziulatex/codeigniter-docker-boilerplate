<?php

namespace App\Service;

use App\DTO\CoasterDTO;

use function count;

class CoasterProblemDetector
{
    protected function calculateExpectedWagons(float $trackLength): int
    {
        return ceil($trackLength / 300); // 300m per wagon
    }

    protected function calculateRequiredPersonnel(int $expectedWagons): int
    {
        return ceil($expectedWagons * 2); // 2 staff members per wagon
    }

    protected function isWithinOperatingHours(string $currentTime, string $startTime, string $endTime): bool
    {
        $current = strtotime($currentTime);
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        if ($start <= $end) {
            return $current >= $start && $current <= $end;
        }

        return $current >= $start || $current <= $end;
    }

    public function detectProblems(CoasterDTO $coaster, array $wagons): array
    {
        $problems = [];
        $currentTime = date('H:i');

        // Check if coaster should be operational now
        $withinOperatingHours = $this->isWithinOperatingHours(
            $currentTime,
            $coaster->getGodzinyOd(),
            $coaster->getGodzinyDo()
        );

        if (!$withinOperatingHours) {
            return $problems;
        }

        // Calculate expected resources
        $expectedWagons = $this->calculateExpectedWagons($coaster->getDlTrasy());
        $requiredPersonnel = $this->calculateRequiredPersonnel($expectedWagons);

        // Check personnel shortage
        if ($coaster->getLiczbaPersonelu() < $requiredPersonnel) {
            $shortage = $requiredPersonnel - $coaster->getLiczbaPersonelu();
            $problems[] = "Brakuje $shortage pracowników";
        }
        if ($coaster->getLiczbaPersonelu() > $requiredPersonnel) {
            $personnelOverload = $coaster->getLiczbaPersonelu() - $requiredPersonnel;
            $problems[] = "$personnelOverload pracowników za dużo";
        }
        // Check wagon shortage
        if (count($wagons) < $expectedWagons) {
            $shortage = $expectedWagons - count($wagons);
            $problems[] = "Brak $shortage wagonów";
        }

        return $problems;
    }

    public function getOperationalStatus(CoasterDTO $coaster): array
    {
        $expectedWagons = $this->calculateExpectedWagons($coaster->getDlTrasy());
        $requiredPersonnel = $this->calculateRequiredPersonnel($expectedWagons);

        return [
            'expected_wagons' => $expectedWagons,
            'required_personnel' => $requiredPersonnel,
            'is_operating' => $this->isWithinOperatingHours(
                date('H:i'),
                $coaster->getGodzinyOd(),
                $coaster->getGodzinyDo()
            )
        ];
    }
}