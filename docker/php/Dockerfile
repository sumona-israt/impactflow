FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql bcmath opcache zip intl mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# Config/route/view caching is intentionally NOT done at build time: it would
# bake in whatever env vars happen to be present during `docker build`
# (typically none), overriding the real values docker-compose injects at
# container start (DB_HOST, REDIS_HOST, etc.) and silently breaking them.

RUN addgroup www && adduser -G www -D www \
    && chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache

USER www

EXPOSE 9000
CMD ["php-fpm"]
