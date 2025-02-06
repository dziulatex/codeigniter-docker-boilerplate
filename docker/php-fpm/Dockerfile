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
        echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi


# Configure logging based on environment
RUN if [ "$CI_ENVIRONMENT" = "development" ] ; then \
        echo "log_errors = On\nerror_reporting = E_ALL" > /usr/local/etc/php/conf.d/error-logging.ini ; \
    else \
        echo "log_errors = On\nerror_reporting = E_ERROR | E_WARNING" > /usr/local/etc/php/conf.d/error-logging.ini ; \
    fi

# Set up working directory
WORKDIR /var/www
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Set the entrypoint
ENTRYPOINT ["docker-entrypoint"]
# Setup permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www && \
    if [ ! -d "/var/www/writable" ]; then \
        mkdir -p /var/www/writable && \
        chmod 777 /var/www/writable; \
    fi

CMD ["php-fpm"]
