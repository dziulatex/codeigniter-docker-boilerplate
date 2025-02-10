<?php

namespace App\Commands;

use App\DTO\CoasterDTO;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use LogicException;
use React\EventLoop\Loop;
use Clue\React\Redis\RedisClient;
use App\Service\CoasterProblemDetector;
use App\Repository\CoasterRepository;
use App\Repository\WagonRepository;
use CodeIgniter\Log\Logger;
use CodeIgniter\CLI\Commands;

use React\Promise\Deferred;

use function count;
use function React\Promise\all;

class ConsoleMonitor extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'monitor:start';
    protected $description = 'Start real-time monitoring of roller coaster system';
    protected $usage = 'monitor:start [arguments] [options]';

    protected string $logPath;
    protected array $lastStatus = [];
    protected CoasterProblemDetector $problemDetector;
    protected CoasterRepository $coasterRepository;
    protected WagonRepository $wagonRepository;
    protected RedisClient $redis;
    protected RedisClient $pubSubRedis;

    public function __construct(Logger $logger, Commands $commands)
    {
        parent::__construct($logger, $commands);
        $this->problemDetector = new CoasterProblemDetector();
        $this->coasterRepository = new CoasterRepository();
        $this->wagonRepository = new WagonRepository();
        $this->redis = Services::redis();
        $this->pubSubRedis = Services::redis(false); // Drugie połączenie tylko dla subskrypcji
        putenv('TERM=xterm-256color');
    }

    protected function clearScreen(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }

    public function run(array $params): void
    {
        CLI::write('Starting Roller Coaster Monitoring System...', 'green');

        $loop = Loop::get();
        if (!$loop) {
            throw new LogicException('No loop available');
        }

        // Początkowy status
        $this->checkStatus();

        // Nasłuchiwanie na subskrypcję
        $this->pubSubRedis->on('message', function ($channel) {
            if ($channel === 'coaster:updates') {
                $this->checkStatus();
            }
        });
        // Subskrypcja
        $this->pubSubRedis->subscribe('coaster:updates');

        $loop->addSignal(SIGINT, function () use ($loop) {
            $this->pubSubRedis->unsubscribe('coaster:updates');
            $loop->stop();
        });

        $loop->run();
    }

    protected function checkStatus(): void
    {
        $this->redis->smembers('coasters')
            ->then(function ($coasterIds) {
                if (empty($coasterIds)) {
                    $this->displayWarning('No coasters found in the system');
                    return;
                }

                $statusData = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'coasters' => []
                ];

                $deferred = new Deferred();
                $promises = [];
                $i = 0;

                $processNextCoaster = function () use (
                    &$processNextCoaster,
                    &$i,
                    $coasterIds,
                    &$promises,
                    $deferred,
                    $statusData
                ) {
                    if ($i >= count($coasterIds)) {
                        all($promises)->then(function ($coasterStatuses) use ($statusData, $deferred) {
                            $statusData['coasters'] = array_filter($coasterStatuses);
                            $this->displayStatus($statusData);
                            $deferred->resolve($statusData);
                        });
                        return;
                    }

                    $coasterId = $coasterIds[$i++];
                    $promises[] = $this->coasterRepository->findById($coasterId)
                        ->then(function ($coaster) {
                            if (!$coaster) {
                                return null;
                            }

                            /** @var CoasterDTO $coaster */
                            return $this->wagonRepository->getWagonsByCoaster($coaster->getId())
                                ->then(function ($wagons) use ($coaster) {
                                    $operationalStatus = $this->problemDetector->getOperationalStatus($coaster);
                                    $problems = $this->problemDetector->detectProblems($coaster, $wagons);
                                    if ($problems) {
                                        $this->logProblem($coaster->getId()->toString(), $problems);
                                    }
                                    return [
                                        'id' => $coaster->getId()->toString(),
                                        'operating_hours' => "{$coaster->getGodzinyOd()} - {$coaster->getGodzinyDo()}",
                                        'wagons' => [
                                            'current' => count($wagons),
                                            'expected' => $operationalStatus['expected_wagons']
                                        ],
                                        'personnel' => [
                                            'current' => $coaster->getLiczbaPersonelu(),
                                            'needed' => $operationalStatus['required_personnel']
                                        ],
                                        'is_operating' => $operationalStatus['is_operating'],
                                        'daily_customers' => $coaster->getLiczbaKlientow(),
                                        'problems' => $problems
                                    ];
                                });
                        });

                    Loop::get()->futureTick($processNextCoaster);
                };

                $processNextCoaster();
                return $deferred->promise();
            })
            ->catch(function ($error) {
                $this->displayError('Error monitoring system: ' . $error->getMessage());
                $this->logError($error->getMessage());
            });
    }

    protected function displayStatus(array $data): void
    {
        $this->clearScreen();
        CLI::write('Roller Coaster System Status - ' . $data['timestamp'], 'green');
        CLI::write(str_repeat('-', 80));

        $i = 0;
        $displayNext = static function () use (&$displayNext, &$i, $data) {
            if ($i >= count($data['coasters'])) {
                return;
            }

            $coaster = $data['coasters'][$i++];
            CLI::write("[Kolejka {$coaster['id']}]", 'cyan');
            CLI::write("1. Godziny działania: {$coaster['operating_hours']}");
            CLI::write("2. Liczba wagonów: {$coaster['wagons']['current']}/{$coaster['wagons']['expected']}");
            CLI::write("3. Dostępny personel: {$coaster['personnel']['current']}/{$coaster['personnel']['needed']}");
            CLI::write("4. Klienci dziennie: {$coaster['daily_customers']}");

            if (!$coaster['is_operating']) {
                CLI::write('5. Status: Poza godzinami pracy', 'yellow');
            } elseif (empty($coaster['problems'])) {
                CLI::write('5. Status: OK', 'green');
            } else {
                CLI::write('5. Problem: ' . implode(', ', $coaster['problems']), 'red');
            }

            CLI::write(str_repeat('-', 80));
            Loop::get()->futureTick($displayNext);
        };

        $displayNext();
    }

    protected function displayWarning(string $message): void
    {
        CLI::write($message, 'yellow');
    }

    protected function displayError(string $message): void
    {
        CLI::error($message);
    }

    protected function logProblem(string $coasterId, array $problems): void
    {
        $this->logger->info("Kolejka $coasterId - Problem: " . implode(', ', $problems));
    }

    protected function logError(string $error): void
    {
        $this->logger->error($error);
    }
}