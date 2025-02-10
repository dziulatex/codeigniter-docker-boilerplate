<?php

namespace App\CommandHandler;

use App\Command\UpdateCoasterCommand;
use App\DTO\CoasterDTO;
use App\Exception\CoasterNotFoundException;
use App\Exception\CommandFailedException;
use App\Helper\WaitForPromiseHelper;
use App\Repository\CoasterRepository;
use Config\Services;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateCoasterHandler
{
    private CoasterRepository $coasterRepository;

    public function __construct()
    {
        $this->coasterRepository = Services::coasterRepository();
    }

    public function __invoke(UpdateCoasterCommand $command): void
    {
        $existingCoaster = WaitForPromiseHelper::wait(
            $this->coasterRepository->findById($command->getId())
        );

        if (!$existingCoaster) {
            throw new CoasterNotFoundException();
        }

        $coasterDTO = new CoasterDTO(
            $command->getLiczbaPersonelu(),
            $command->getLiczbaKlientow(),
            $existingCoaster->getDlTrasy(),
            $command->getGodzinyOd(),
            $command->getGodzinyDo(),
            $command->getId()
        );

        $result = WaitForPromiseHelper::wait(
            $this->coasterRepository->update($coasterDTO)
        );

        if (!$result) {
            throw new CommandFailedException('update roller coaster');
        }
    }
}