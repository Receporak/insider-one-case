FROM php:8.4-fpm-alpine

# System dependencies (PECL build tools + runtime libs + supervisor)
RUN apk add --no-cache \
    bash \
    unzip \
    autoconf g++ make \
    libpq-dev \
    libzip-dev \
    linux-headers \
    supervisor

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl opcache

# Redis (PECL)
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Supervisord config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# supervisorctl wrapper — her zaman doğru config'i kullanır
RUN printf '#!/bin/sh\nexec /usr/bin/supervisorctl -c /etc/supervisor/conf.d/supervisord.conf "$@"\n' \
    > /usr/local/bin/supervisorctl && chmod +x /usr/local/bin/supervisorctl

# Entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
