# Desarrollo local con Xdebug.
FROM php:8.2.18-fpm-bookworm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libsqlite3-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip pdo pdo_mysql pdo_sqlite gd \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/zz-fpm-env.conf /usr/local/etc/php-fpm.d/zz-fpm-env.conf
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/backend

RUN mkdir -p /var/www/backend/storage \
 && chown -R www-data:www-data /var/www/backend/storage \
 && chmod -R 775 /var/www/backend/storage

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction
COPY backend/ ./

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
