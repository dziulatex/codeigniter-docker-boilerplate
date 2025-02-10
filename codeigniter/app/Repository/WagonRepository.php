<?php

namespace App\Repository;

use App\DTO\WagonDTO;
use Config\Services;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Clue\React\Redis\RedisClient;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use React\Promise\PromiseInterface;

use function React\Promise\all;
use function count;

class WagonRepository
{
    private RedisClient $redis;
    private const PREFIX = 'wagon:';

    public function __construct()
    {
        $this->redis = Services::redis();
    }

    private function notifyWagonUpdate(UuidInterface $coasterId): PromiseInterface
    {
        return $this->redis->publish(
            'coaster:updates',
            json_encode([
                'id' => $coasterId->toString(),
                'type' => 'wagon',
                'timestamp' => date('Y-m-d H:i:s')
            ])
        );
    }

    public function save(WagonDTO $wagon): PromiseInterface
    {
        $key = self::PREFIX . $wagon->getId()->toString();
        $data = array_map('\strval', $wagon->toArray());

        $hmsetData = [];
        foreach ($data as $field => $value) {
            $hmsetData[] = $field;
            $hmsetData[] = $value;
        }

        return $this->redis->hmset($key, ...$hmsetData)
            ->then(function ($result) use ($wagon) {
                if ($result === 'OK') {
                    return $this->redis->sadd(
                        "coaster:{$wagon->getCoasterId()->toString()}:wagons",
                        $wagon->getId()->toString()
                    )
                        ->then(function () use ($wagon) {
                            return $this->notifyWagonUpdate($wagon->getCoasterId())
                                ->then(function () use ($wagon) {
                                    return $wagon->getId();
                                });
                        });
                }
                return false;
            })
            ->catch(function ($e) {
                log_message('error', 'Failed to save wagon: ' . $e->getMessage());
                throw new DatabaseException('Failed to save wagon');
            });
    }

    public function findById(UuidInterface $id): PromiseInterface
    {
        $idString = $id->toString();
        return $this->redis->hgetall(self::PREFIX . $idString)
            ->then(function ($data) {
                if (empty($data)) {
                    return null;
                }
                $count = count($data);
                $associativeData = [];
                for ($i = 0; $i < $count; $i += 2) {
                    $associativeData[$data[$i]] = $data[$i + 1];
                }
                return WagonDTO::fromArray($associativeData);
            })
            ->catch(function ($e) {
                log_message('error', 'Failed to find wagon: ' . $e->getMessage());
                throw new DatabaseException('Failed to find wagon');
            });
    }

    public function delete(UuidInterface $id): PromiseInterface
    {
        $idString = $id->toString();
        return $this->findById($id)
            ->then(function ($wagon) use ($idString) {
                if (!$wagon) {
                    return false;
                }

                return $this->redis->srem("coaster:{$wagon->getCoasterId()}:wagons", $idString)
                    ->then(function () use ($idString, $wagon) {
                        return $this->redis->del(self::PREFIX . $idString)
                            ->then(function ($result) use ($wagon) {
                                if ($result > 0) {
                                    return $this->notifyWagonUpdate($wagon->getCoasterId())
                                        ->then(function () {
                                            return true;
                                        });
                                }
                                return false;
                            });
                    });
            })
            ->catch(function ($e) {
                log_message('error', 'Failed to delete wagon: ' . $e->getMessage());
                throw new DatabaseException('Failed to delete wagon');
            });
    }

    public function getWagonsByCoaster(UuidInterface $coasterId): PromiseInterface
    {
        $coasterIdString = $coasterId->toString();
        return $this->redis->smembers("coaster:$coasterIdString:wagons")
            ->then(function ($ids) {
                $promises = array_map(
                    fn($id) => $this->findById(Uuid::fromString($id)),
                    $ids
                );
                return all($promises)
                    ->then(function ($wagons) {
                        return array_filter($wagons);
                    });
            })
            ->catch(function ($e) {
                log_message('error', 'Failed to get coaster wagons: ' . $e->getMessage());
                throw new DatabaseException('Failed to get coaster wagons');
            });
    }

    public function updateLastRun(UuidInterface $id): PromiseInterface
    {
        $idString = $id->toString();
        return $this->findById($id)
            ->then(function ($wagon) use ($idString) {
                if (!$wagon) {
                    return false;
                }

                return $this->redis->hset(self::PREFIX . $idString, 'last_run', date('Y-m-d H:i:s'))
                    ->then(function ($result) use ($wagon) {
                        if ($result !== false) {
                            return $this->notifyWagonUpdate($wagon->getCoasterId())
                                ->then(function () {
                                    return true;
                                });
                        }
                        return false;
                    });
            })
            ->catch(function ($e) {
                log_message('error', 'Failed to update wagon last run: ' . $e->getMessage());
                throw new DatabaseException('Failed to update wagon last run');
            });
    }
}