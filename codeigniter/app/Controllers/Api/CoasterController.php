<?php

namespace App\Controllers\Api;

use App\Exception\CommandFailedException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Command\CreateCoasterCommand;
use App\Command\UpdateCoasterCommand;
use App\Command\AddWagonCommand;
use App\Command\RemoveWagonCommand;
use App\Validator\CoasterValidator;
use App\Validator\WagonValidator;
use App\Exception\CoasterNotFoundException;
use App\Exception\WagonNotFoundException;
use App\Exception\WagonDoesNotBelongToCoasterException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class CoasterController extends AbstractApiController
{
    use ResponseTrait;

    protected MessageBusInterface $messageBus;
    protected CoasterValidator $coasterValidator;
    protected WagonValidator $wagonValidator;

    public function __construct()
    {
        $this->messageBus = Services::messageBus();
        $this->coasterValidator = Services::coasterValidator();
        $this->wagonValidator = Services::wagonValidator();
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$this->coasterValidator->validate($data)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => $this->coasterValidator->getErrors()
            ], 400);
        }

        try {
            $command = new CreateCoasterCommand(
                $data['liczba_personelu'],
                $data['liczba_klientow'],
                $data['dl_trasy'],
                $data['godziny_od'],
                $data['godziny_do']
            );

            $envelope = $this->messageBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            $id = $handledStamp ? $handledStamp->getResult() : null;

            if (empty($id) || !Uuid::isValid($id)) {
                return $this->respond([
                    'status' => 500,
                    'error' => 500,
                    'messages' => 'Invalid UUID generated'
                ], 500);
            }

            return $this->respond([
                'status' => 201,
                'error' => null,
                'messages' => 'Roller coaster created successfully',
                'id' => $id
            ], 201);
        } catch (Exception $e) {
            return $this->handleMessengerException($e);
        }
    }

    public function update($id = null)
    {
        if (empty($id) || !Uuid::isValid($id)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => 'Invalid coaster ID format'
            ], 400);
        }

        $data = $this->request->getJSON(true);

        if (!$this->coasterValidator->validate($data)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => $this->coasterValidator->getErrors()
            ], 400);
        }

        try {
            $command = new UpdateCoasterCommand(
                Uuid::fromString($id),
                $data['liczba_personelu'],
                $data['liczba_klientow'],
                $data['godziny_od'],
                $data['godziny_do']
            );

            $this->messageBus->dispatch($command);

            return $this->respond([
                'status' => 200,
                'error' => null,
                'messages' => 'Roller coaster updated successfully'
            ], 200);
        } catch (Exception $e) {
            return $this->handleMessengerException($e);
        }
    }

    public function addWagon($coasterId): ResponseInterface
    {
        if (empty($coasterId) || !Uuid::isValid($coasterId)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => 'Invalid coaster ID format'
            ], 400);
        }

        $data = $this->request->getJSON(true);

        if (!$this->wagonValidator->validate($data)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => $this->wagonValidator->getErrors()
            ], 400);
        }

        try {
            $command = new AddWagonCommand(
                Uuid::fromString($coasterId),
                $data['ilosc_miejsc'],
                $data['predkosc_wagonu']
            );

            $envelope = $this->messageBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            $id = $handledStamp ? $handledStamp->getResult() : null;

            if (empty($id) || !Uuid::isValid($id)) {
                return $this->respond([
                    'status' => 500,
                    'error' => 500,
                    'messages' => 'Invalid wagon UUID generated'
                ], 500);
            }

            return $this->respond([
                'status' => 201,
                'error' => null,
                'messages' => 'Wagon added successfully',
                'id' => $id
            ], 201);
        } catch (Exception $e) {
            return $this->handleMessengerException($e);
        }
    }

    public function removeWagon($coasterId, $wagonId): ResponseInterface
    {
        if (empty($coasterId) || !Uuid::isValid($coasterId)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => 'Invalid coaster ID format'
            ], 400);
        }

        if (empty($wagonId) || !Uuid::isValid($wagonId)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => 'Invalid wagon ID format'
            ], 400);
        }

        try {
            $command = new RemoveWagonCommand(
                Uuid::fromString($coasterId),
                Uuid::fromString($wagonId)
            );
            $this->messageBus->dispatch($command);

            return $this->respond([
                'status' => 200,
                'error' => null,
                'messages' => 'Wagon removed successfully'
            ], 200);
        } catch (Exception $e) {
            return $this->handleMessengerException($e);
        }
    }
}