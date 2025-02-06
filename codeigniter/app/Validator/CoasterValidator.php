<?php

namespace App\Validator;

use CodeIgniter\Validation\ValidationInterface;
use Config\Services;

class CoasterValidator
{
    private ValidationInterface $validation;

    public function __construct()
    {
        $this->validation = Services::validation();
    }

    public function validate(array $data): bool
    {
        $this->validation->setRules([
            'liczba_personelu' => [
                'rules' => 'required|integer|greater_than[0]',
                'errors' => [
                    'required' => 'Liczba personelu jest wymagana',
                    'integer' => 'Liczba personelu musi być liczbą całkowitą',
                    'greater_than' => 'Liczba personelu musi być większa od 0'
                ]
            ],
            'liczba_klientow' => [
                'rules' => 'required|integer|greater_than[0]',
                'errors' => [
                    'required' => 'Liczba klientów jest wymagana',
                    'integer' => 'Liczba klientów musi być liczbą całkowitą',
                    'greater_than' => 'Liczba klientów musi być większa od 0'
                ]
            ],
            'dl_trasy' => [
                'rules' => 'required|numeric|greater_than[0]',
                'errors' => [
                    'required' => 'Długość trasy jest wymagana',
                    'numeric' => 'Długość trasy musi być liczbą',
                    'greater_than' => 'Długość trasy musi być większa od 0'
                ]
            ],
            'godziny_od' => [
                'rules' => 'required|valid_time',
                'errors' => [
                    'required' => 'Godzina rozpoczęcia jest wymagana',
                    'valid_time' => 'Nieprawidłowy format godziny rozpoczęcia (HH:MM)'
                ]
            ],
            'godziny_do' => [
                'rules' => 'required|valid_time',
                'errors' => [
                    'required' => 'Godzina zakończenia jest wymagana',
                    'valid_time' => 'Nieprawidłowy format godziny zakończenia (HH:MM)'
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