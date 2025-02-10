<?php

namespace Config;

use App\Command\AddWagonCommand;
use App\Command\CreateCoasterCommand;
use App\Command\RemoveWagonCommand;
use App\Command\UpdateCoasterCommand;
use App\CommandHandler\AddWagonHandler;
use App\CommandHandler\CreateCoasterHandler;
use App\CommandHandler\RemoveWagonHandler;
use App\CommandHandler\UpdateCoasterHandler;
use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use App\Service\CoasterProblemDetector;
use App\Validator\CoasterValidator;
use App\Validator\WagonValidator;
use Clue\React\Redis\RedisClient;
use CodeIgniter\Config\BaseService;
use RedisException;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

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
    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function redis()
    {
        /** @var Redis $config */
        $config = config('Redis');


        try {
            return new RedisClient($config->host);
        } catch (RedisException $e) {
            log_message('error', 'Redis connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function addWagonHandler()
    {
        return new AddWagonHandler();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function createCoasterHandler()
    {
        return new CreateCoasterHandler();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function removeWagonHandler()
    {
        return new RemoveWagonHandler();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function updateCoasterHandler()
    {
        return new UpdateCoasterHandler();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function messageBus(): MessageBus
    {
        $handlers = [
            AddWagonCommand::class => [
                new HandlerDescriptor(self::addWagonHandler(), [])
            ],
            CreateCoasterCommand::class => [
                new HandlerDescriptor(self::createCoasterHandler(), [])
            ],
            RemoveWagonCommand::class => [
                new HandlerDescriptor(self::removeWagonHandler(), [])
            ],
            UpdateCoasterCommand::class => [
                new HandlerDescriptor(self::updateCoasterHandler(), [])
            ],
        ];

        $handlersLocator = new HandlersLocator($handlers);

        $middleware = [
            new HandleMessageMiddleware($handlersLocator)
        ];

        return new MessageBus($middleware);
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function coasterValidator()
    {
        return new CoasterValidator();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function wagonValidator()
    {
        return new WagonValidator();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function coasterProblemDetector()
    {
        return new CoasterProblemDetector();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function coasterRepository()
    {
        return new CoasterRepository();
    }

    //could cache instance and have separate factory / just be autoinjected from DI.
    public static function wagonRepository()
    {
        return new WagonRepository();
    }
}
