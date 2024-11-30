# Use PHP 8.2-fpm-bullseye for better stability and security
FROM php:8.2-fpm-bullseye as php

# Set environment variables
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_ENABLE_CLI=0
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=1
ENV PHP_OPCACHE_REVALIDATE_FREQ=1

# Install dependencies and clean up in a single layer to reduce image size
RUN apt-get update && apt-get install -y \
    unzip \
    libpq-dev \
    libcurl4-gnutls-dev \
    nginx \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions using the docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    mysqli \
    pdo \
    pdo_mysql \
    bcmath \
    curl \
    opcache \
    mbstring

# Copy composer from official image (updated to latest stable version)
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy configuration files
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Set working directory
WORKDIR /var/www

# Create Laravel directory structure with proper permissions
RUN mkdir -p /var/www/storage/framework/{cache,testing,sessions,views} \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 775 /var/www/storage

# Copy application files
COPY --chown=www-data:www-data . .

# Set up user and group (using ARG for flexibility)
ARG UID=1000
ARG GID=1001
RUN usermod --uid ${UID} www-data \
    && groupmod --gid ${GID} www-data

# Set final permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && chmod +x docker/entrypoint.sh

# Use multi-stage build to optimize final image
FROM php as final
COPY --from=php /var/www /var/www

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Set entrypoint
ENTRYPOINT ["docker/entrypoint.sh"]