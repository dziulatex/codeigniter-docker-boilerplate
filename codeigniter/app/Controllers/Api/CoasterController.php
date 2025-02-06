<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use App\Validator\CoasterValidator;
use App\Validator\WagonValidator;
use App\DTO\CoasterDTO;
use App\DTO\WagonDTO;
use Config\Services;
use Exception;

class CoasterController extends ResourceController
{
    use ResponseTrait;

    protected CoasterRepository $coasterRepository;
    protected WagonRepository $wagonRepository;
    protected CoasterValidator $coasterValidator;
    protected WagonValidator $wagonValidator;

    public function __construct()
    {
        $this->coasterRepository = new CoasterRepository();
        $this->wagonRepository = new WagonRepository();
        $this->coasterValidator = new CoasterValidator();
        $this->wagonValidator = new WagonValidator();
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
            $coasterDTO = new CoasterDTO(
                $data['liczba_personelu'],
                $data['liczba_klientow'],
                $data['dl_trasy'],
                $data['godziny_od'],
                $data['godziny_do']
            );

            $promise = $this->coasterRepository->save($coasterDTO);
            $result = Services::waitForPromise($promise);
            if (!$result) {
                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => 'Failed to create roller coaster'
                ], 400);
            }
            return $this->respond([
                'status' => 201,
                'error' => null,
                'messages' => 'Roller coaster created successfully',
                'id' => $coasterDTO->getId()
            ], 201);
        } catch (Exception $e) {
            log_message('error', 'Failed to create coaster: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => 'Internal server error'
            ], 500);
        }
    }

    public function update($id = null)
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
            $existingCoaster = Services::waitForPromise(
                $this->coasterRepository->findById($id)
            );
            if (!$existingCoaster) {
                return $this->respond([
                    'status' => 404,
                    'error' => 404,
                    'messages' => 'Roller coaster not found'
                ], 404);
            }

            $coasterDTO = new CoasterDTO(
                $data['liczba_personelu'],
                $data['liczba_klientow'],
                $existingCoaster->getDlTrasy(),
                $data['godziny_od'],
                $data['godziny_do'],
                $id
            );

            $result = Services::waitForPromise(
                $this->coasterRepository->update($coasterDTO)
            );

            if (!$result) {
                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => 'Failed to update roller coaster'
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'error' => null,
                'messages' => 'Roller coaster updated successfully'
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'Failed to update coaster: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => 'Internal server error'
            ], 500);
        }
    }

    public function addWagon($coasterId)
    {
        $data = $this->request->getJSON(true);

        if (!$this->wagonValidator->validate($data)) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => $this->wagonValidator->getErrors()
            ], 400);
        }

        try {
            $exists = Services::waitForPromise(
                $this->coasterRepository->exists($coasterId)
            );

            if (!$exists) {
                return $this->respond([
                    'status' => 404,
                    'error' => 404,
                    'messages' => 'Roller coaster not found'
                ], 404);
            }

            $wagonDTO = new WagonDTO(
                $coasterId,
                $data['ilosc_miejsc'],
                $data['predkosc_wagonu']
            );

            $result = Services::waitForPromise(
                $this->wagonRepository->save($wagonDTO)
            );

            if (!$result) {
                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => 'Failed to add wagon'
                ], 400);
            }

            return $this->respond([
                'status' => 201,
                'error' => null,
                'messages' => 'Wagon added successfully',
                'id' => $wagonDTO->getId()
            ], 201);
        } catch (Exception $e) {
            log_message('error', 'Failed to add wagon: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => 'Internal server error'
            ], 500);
        }
    }

    public function removeWagon($coasterId, $wagonId)
    {
        try {
            $exists = Services::waitForPromise(
                $this->coasterRepository->exists($coasterId)
            );

            if (!$exists) {
                return $this->respond([
                    'status' => 404,
                    'error' => 404,
                    'messages' => 'Roller coaster not found'
                ], 404);
            }

            $wagon = Services::waitForPromise(
                $this->wagonRepository->findById($wagonId)
            );

            if (!$wagon) {
                return $this->respond([
                    'status' => 404,
                    'error' => 404,
                    'messages' => 'Wagon not found'
                ], 404);
            }

            if ($wagon->getCoasterId() !== $coasterId) {
                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => 'Wagon does not belong to this roller coaster'
                ], 400);
            }

            $result = Services::waitForPromise(
                $this->wagonRepository->delete($wagonId)
            );

            if (!$result) {
                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => 'Failed to remove wagon'
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'error' => null,
                'messages' => 'Wagon removed successfully'
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'Failed to remove wagon: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => 'Internal server error'
            ], 500);
        }
    }
}