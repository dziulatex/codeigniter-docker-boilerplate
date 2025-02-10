<?php

namespace App\Exception;

use LogicException;

class CommandFailedException extends LogicException
{
    public function __construct(string $operation)
    {
        parent::__construct("Failed to $operation");
    }
}
