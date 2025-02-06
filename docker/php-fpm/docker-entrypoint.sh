#!/bin/sh
set -e

if [ "$CI_ENVIRONMENT" = "development" ]; then
    composer install --no-interaction --prefer-dist
else
    composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
fi

exec php-fpm -F >> /entrypoint.log 2>&1