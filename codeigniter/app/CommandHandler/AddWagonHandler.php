<?php

namespace App\CommandHandler;

use App\Command\AddWagonCommand;
use App\DTO\WagonDTO;
use App\Exception\CoasterNotFoundException;
use App\Exception\CommandFailedException;
use App\Helper\WaitForPromiseHelper;
use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use Config\Services;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddWagonHandler
{
    private CoasterRepository $coasterRepository;
    private WagonRepository $wagonRepository;

    public function __construct()
    {
        $this->coasterRepository = Services::coasterRepository();
        $this->wagonRepository = Services::wagonRepository();
    }

    public function __invoke(AddWagonCommand $command): string
    {
        $exists = WaitForPromiseHelper::wait(
            $this->coasterRepository->exists($command->getCoasterId()->toString())
        );

        if (!$exists) {
            throw new CoasterNotFoundException();
        }

        $wagonDTO = new WagonDTO(
            $command->getCoasterId(),
            $command->getIloscMiejsc(),
            $command->getPredkoscWagonu()
        );

        $result = WaitForPromiseHelper::wait(
            $this->wagonRepository->save($wagonDTO)
        );

        if (!$result) {
            throw new CommandFailedException('add wagon');
        }

        return $wagonDTO->getId();
    }
}