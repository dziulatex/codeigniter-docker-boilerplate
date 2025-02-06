<?php

namespace App\Validator;

use CodeIgniter\Validation\ValidationInterface;
use Config\Services;

class WagonValidator
{
    private ValidationInterface $validation;

    public function __construct()
    {
        $this->validation = Services::validation();
    }

    public function validate(array $data): bool
    {
        $this->validation->setRules([
            'ilosc_miejsc' => [
                'rules' => 'required|integer|greater_than[0]',
                'errors' => [
                    'required' => 'Ilość miejsc jest wymagana',
                    'integer' => 'Ilość miejsc musi być liczbą całkowitą',
                    'greater_than' => 'Ilość miejsc musi być większa od 0'
                ]
            ],
            'predkosc_wagonu' => [
                'rules' => 'required|numeric|greater_than[0]',
                'errors' => [
                    'required' => 'Prędkość wagonu jest wymagana',
                    'numeric' => 'Prędkość wagonu musi być liczbą',
                    'greater_than' => 'Prędkość wagonu musi być większa od 0'
                ]
            ]
        ]);

        return $this->validation->run($data);
    }

    public function getErrors(): array
    {
        return $this->validation->getErrors();
    }
}