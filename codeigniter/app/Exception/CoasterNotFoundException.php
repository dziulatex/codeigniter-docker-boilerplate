<?php

namespace App\Exception;

use LogicException;

class CoasterNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('Roller coaster not found');
    }
}