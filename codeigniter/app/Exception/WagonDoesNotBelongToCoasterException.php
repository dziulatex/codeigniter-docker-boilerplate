<?php

namespace App\Exception;

use LogicException;

class WagonDoesNotBelongToCoasterException extends LogicException
{
    public function __construct()
    {
        parent::__construct('Wagon does not belong to this roller coaster');
    }
}