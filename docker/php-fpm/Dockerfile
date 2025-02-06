FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libbrotli-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN git config --global --add safe.directory /var/www

# Install PHP extensions
RUN docker-php-ext-install zip intl pcntl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure PHP for development/production
ARG CI_ENVIRONMENT=development
ENV CI_ENVIRONMENT=${CI_ENVIRONMENT}

# Install and configure Xdebug if in development environment
RUN if [ "$CI_ENVIRONMENT" = "development" ]; then \
       pecl install xdebug && \
       docker-php-ext-enable xdebug && \
       echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
       echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
       export XDEBUG_CONFIG="client_host=host.docker.internal" && \
       export XDEBUG_MODE="debug,coverage" && \
       export XDEBUG_SESSION=1; \
   fi

# Configure logging based on environment
RUN if [ "$CI_ENVIRONMENT" = "development" ] ; then \
        echo "log_errors = On\nerror_reporting = E_ALL" > /usr/local/etc/php/conf.d/error-logging.ini ; \
    else \
        echo "log_errors = On\nerror_reporting = E_ERROR | E_WARNING" > /usr/local/etc/php/conf.d/error-logging.ini ; \
    fi

# Create required directories
RUN mkdir -p \
    /var/www \
    /var/www/writable/debugbar \
    /var/www/writable/logs \
    /var/www/writable/cache \
    /var/www/writable/session \
    /var/www/vendor

# Set up entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Create log file for entrypoint
RUN touch /entrypoint.log

# Create user with UID 1000 if it doesn't exist
RUN if ! id -u 1000 > /dev/null 2>&1; then \
        useradd -u 1000 -g www-data -d /var/www -s /bin/bash developer; \
    fi

# Set proper ownership and permissions
RUN chown -R 1000:www-data /var/www /entrypoint.log && \
    chmod -R 775 /var/www/writable && \
    chmod g+s /var/www/writable/logs

WORKDIR /var/www

# Switch to UID 1000
USER 1000

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]