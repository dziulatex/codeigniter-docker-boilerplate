<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Redis extends BaseConfig
{
    /**
     * Default connection info
     */
    public string $host = 'redis-dev';
    public int $port = 6379;
    public float $timeout = 2.0;

    /**
     * Whether to show debug messages
     */
    public $debug = false;

    public function __construct()
    {
        parent::__construct();

        // Override with environment variables if set
        $this->host = getenv('REDIS_HOST') ?: $this->host;
        $this->port = getenv('REDIS_PORT') ?: $this->port;
        // Enable debug in development
        $this->debug = (ENVIRONMENT === 'development');
    }
}