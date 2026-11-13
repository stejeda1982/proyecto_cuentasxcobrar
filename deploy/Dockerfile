# T0.8 — Imagen de la aplicación (PHP-FPM 8.4 con ext-ldap).
FROM php:8.4-fpm-alpine

# Dependencias del sistema y extensiones que requiere el stack
# (mbstring, pdo_pgsql, ldap para el directorio corporativo, redis).
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev openldap-dev \
    && docker-php-ext-install intl pdo_pgsql ldap bcmath zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencias primero (capa cacheable).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Código de la aplicación.
COPY . .
RUN composer dump-autoload -o \
    && php artisan config:cache || true

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
