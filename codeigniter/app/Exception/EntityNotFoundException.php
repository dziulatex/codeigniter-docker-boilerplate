<?php

namespace App\Exception;

use LogicException;

abstract class EntityNotFoundException extends LogicException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
