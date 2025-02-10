<?php

namespace App\CommandHandler;

use App\Command\CreateCoasterCommand;
use App\DTO\CoasterDTO;
use App\Exception\CommandFailedException;
use App\Helper\WaitForPromiseHelper;
use App\Repository\CoasterRepository;
use Config\Services;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateCoasterHandler
{
    private CoasterRepository $coasterRepository;

    public function __construct()
    {
        $this->coasterRepository = Services::coasterRepository();
    }

    public function __invoke(CreateCoasterCommand $command): string
    {
        $coasterDTO = new CoasterDTO(
            $command->getLiczbaPersonelu(),
            $command->getLiczbaKlientow(),
            $command->getDlTrasy(),
            $command->getGodzinyOd(),
            $command->getGodzinyDo()
        );

        $result = WaitForPromiseHelper::wait(
            $this->coasterRepository->save($coasterDTO)
        );

        if (!$result) {
            throw new CommandFailedException('Create roller coaster');
        }

        return $coasterDTO->getId();
    }
}