# syntax=docker/dockerfile:1

FROM composer:2 AS vendor

WORKDIR /app

COPY apps/api/composer.json apps/api/composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY apps/api ./

RUN composer dump-autoload --optimize --no-dev --no-scripts

FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
        libjpeg-turbo \
        libpng \
        libwebp \
        libzip \
        postgresql-client \
        tzdata \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && for extension in bcmath gd opcache pcntl pdo_pgsql zip; do \
        docker-php-ext-install "$extension"; \
    done \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=vendor /app /var/www/html
COPY infrastructure/docker/api/entrypoint.sh /usr/local/bin/api-entrypoint
COPY infrastructure/docker/api/php.ini /usr/local/etc/php/conf.d/meridian.ini

RUN chmod +x /usr/local/bin/api-entrypoint \
    && mkdir -p \
        /var/www/html/bootstrap/cache \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["api-entrypoint"]
CMD ["php-fpm"]
