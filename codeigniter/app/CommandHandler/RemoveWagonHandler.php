<?php

namespace App\CommandHandler;

use App\Command\RemoveWagonCommand;
use App\Exception\CoasterNotFoundException;
use App\Exception\CommandFailedException;
use App\Exception\WagonDoesNotBelongToCoasterException;
use App\Exception\WagonNotFoundException;
use App\Helper\WaitForPromiseHelper;
use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use Config\Services;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveWagonHandler
{
    private CoasterRepository $coasterRepository;
    private WagonRepository $wagonRepository;

    public function __construct()
    {
        $this->coasterRepository = Services::coasterRepository();
        $this->wagonRepository = Services::wagonRepository();
    }

    public function __invoke(RemoveWagonCommand $command): void
    {
        $exists = WaitForPromiseHelper::wait(
            $this->coasterRepository->exists($command->getCoasterId())
        );

        if (!$exists) {
            throw new CoasterNotFoundException();
        }

        $wagon = WaitForPromiseHelper::wait(
            $this->wagonRepository->findById($command->getWagonId())
        );

        if (!$wagon) {
            throw new WagonNotFoundException();
        }

        if ($wagon->getCoasterId()->toString() !== $command->getCoasterId()->toString()) {
            throw new WagonDoesNotBelongToCoasterException();
        }

        $result = WaitForPromiseHelper::wait(
            $this->wagonRepository->delete($command->getWagonId())
        );

        if (!$result) {
            throw new CommandFailedException('remove wagon');
        }
    }
}