<?php

namespace App\Trait;

use App\Exception\EntityNotFoundException;
use Exception;
use LogicException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Psr\Http\Message\ResponseInterface;

trait HandleMessengerException
{
    protected function handleMessengerException(Exception $e): ResponseInterface
    {
        if ($e instanceof HandlerFailedException) {
            $e = $e->getPrevious() ?? $e;
        }

        if ($e instanceof EntityNotFoundException) {
            return $this->respond([
                'status' => 404,
                'error' => 404,
                'messages' => $e->getMessage()
            ], 404);
        }

        if ($e instanceof LogicException) {
            return $this->respond([
                'status' => 400,
                'error' => 400,
                'messages' => $e->getMessage()
            ], 400);
        }

        log_message('error', 'Unexpected error: ' . $e->getMessage());
        return $this->respond([
            'status' => 500,
            'error' => 500,
            'messages' => 'Internal server error'
        ], 500);
    }
}