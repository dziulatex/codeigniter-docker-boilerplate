<?php

namespace App\Helper;

use LogicException;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use Throwable;

class WaitForPromiseHelper
{
    public static function wait(PromiseInterface $promise)
    {
        $loop = Loop::get();
        $resolved = false;
        $result = null;
        $exception = null;

        $promise->then(
            function ($value) use (&$resolved, &$result) {
                $resolved = true;
                $result = $value;
            },
            function (Throwable $error) use (&$resolved, &$exception) {
                $resolved = true;
                $exception = $error;
            }
        );

        while (!$resolved) {
            if (!$loop) {
                $mess = 'Cannot get loop';
                log_message('error', $mess);
                throw new LogicException($mess);
            }
            $loop->run();
        }

        if ($exception) {
            throw $exception; // Throw exception if the promise was rejected
        }

        return $result;
    }
}