<?php

namespace Config;

use Clue\React\Redis\RedisClient;
use CodeIgniter\Config\BaseService;
use Exception;
use LogicException;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use RedisException;
use Throwable;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function redis($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('redis');
        }

        /** @var Redis $config */
        $config = config('Redis');


        try {
            return new RedisClient($config->host);
        } catch (RedisException $e) {
            log_message('error', 'Redis connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function redisXd($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('redis');
        }

        /** @var Redis $config */
        $config = config('Redis');


        try {
            return new RedisClient($config->host);
        } catch (RedisException $e) {
            log_message('error', 'Redis connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function waitForPromise(PromiseInterface $promise)
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
