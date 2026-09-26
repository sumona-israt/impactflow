FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    # gd is required by phpoffice/phpspreadsheet (Phase 6) — this was never
    # actually added when that dependency landed, since the image had never
    # been build-tested until Phase 10's E2E run caught it (see
    # docs/testing-strategy.md).
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql bcmath opcache zip intl mbstring gd \
    # PCOV, not Xdebug: this image only ever needs coverage *measurement* (no
    # step-debugging), and PCOV is dramatically faster for that one job (see
    # docs/testing-strategy.md). Dev-only in spirit — the --no-dev composer
    # install below never pulls in Pest, so this extension is inert unless
    # someone runs `composer install` (no --no-dev) inside the container for
    # local coverage runs.
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps

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
