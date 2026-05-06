FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    sqlite \
    sqlite-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_sqlite opcache pcntl \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize \
    && php artisan storage:link || true

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 9000

ENTRYPOINT ["sh", "docker/entrypoint.sh"]
