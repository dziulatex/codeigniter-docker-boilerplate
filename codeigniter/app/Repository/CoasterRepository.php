<?php

namespace App\Repository;

use App\DTO\CoasterDTO;
use Clue\React\Redis\RedisClient;
use Config\Services;
use CodeIgniter\Database\Exceptions\DatabaseException;
use React\Promise\PromiseInterface;

use function count;

class CoasterRepository
{
    private RedisClient $redis;
    private const PREFIX = 'coaster:';

    public function __construct()
    {
        $this->redis = Services::redis();
    }

    private function notifyUpdate(string $coasterId): PromiseInterface
    {
        return $this->redis->publish(
            'coaster:updates',
            json_encode([
                'id' => $coasterId,
                'type' => 'coaster',
                'timestamp' => date('Y-m-d H:i:s')
            ])
        );
    }

    public function save(CoasterDTO $coaster): PromiseInterface
    {
        $key = self::PREFIX . $coaster->getId();
        $data = array_map('\strval', $coaster->toArray());

        $hmsetData = [];
        foreach ($data as $field => $value) {
            $hmsetData[] = $field;
            $hmsetData[] = $value;
        }

        return $this->redis->hmset($key, ...$hmsetData)
            ->then(function ($result) use ($coaster) {
                if ($result === 'OK') {
                    return $this->redis->sadd('coasters', $coaster->getId())
                        ->then(function () use ($coaster) {
                            return $this->notifyUpdate($coaster->getId())
                                ->then(function () use ($coaster) {
                                    return $coaster->getId();
                                });
                        });
                }
                return false;
            })
            ->catch(function ($e) {
                log_message('error', 'Failed to save coaster: ' . $e->getMessage());
                throw new DatabaseException('Failed to save coaster');
            });
    }

    public function findById(string $id): PromiseInterface
    {
        return $this->redis->hgetall(self::PREFIX . $id)
            ->then(function ($data) {
                if (empty($data)) {
                    return null;
                }
                $count = count($data);
                $associativeData = [];
                for ($i = 0, $iMax = $count; $i < $iMax; $i += 2) {
                    $associativeData[$data[$i]] = $data[$i + 1];
                }

                return CoasterDTO::fromArray($associativeData);
            })
            ->catch(function ($e) {
                log_message('error', 'Failed to find coaster: ' . $e->getMessage());
                throw new DatabaseException('Failed to find coaster');
            });
    }

    public function update(CoasterDTO $coaster): PromiseInterface
    {
        $key = self::PREFIX . $coaster->getId();

        return $this->redis->exists($key)
            ->then(function ($exists) use ($coaster) {
                if ($exists === 0) {
                    return false;
                }
                return $this->save($coaster)
                    ->then(function ($result) use ($coaster) {
                        if ($result) {
                            return $this->notifyUpdate($coaster->getId())
                                ->then(function () {
                                    return true;
                                });
                        }
                        return false;
                    });
            });
    }

    public function exists(string $id): PromiseInterface
    {
        return $this->redis->exists(self::PREFIX . $id)
            ->then(function ($result) {
                return $result > 0;
            });
    }
}