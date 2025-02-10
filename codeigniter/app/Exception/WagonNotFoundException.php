<?php

namespace App\Exception;


class WagonNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('Wagon not found');
    }
}