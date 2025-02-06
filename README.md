# Running the Project

## Development Environment
```bash
CI_ENVIRONMENT=development APP_ENV=dev docker compose up -d
```

## Production Environment
```bash
CI_ENVIRONMENT=production APP_ENV=prod docker compose up -d
```

## Running Tests
```bash
docker exec php-fpm vendor/bin/phpunit
```

## Monitoring
To view coaster information logs:
```bash
docker logs -f console-monitor
```