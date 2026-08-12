FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    oniguruma-dev \
    icu-dev

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    bcmath \
    pcntl \
    mbstring \
    opcache \
    intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar configuraciones de Nginx y Supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .

RUN composer run-script post-autoload-dump || true

# Configurar permisos para el usuario www-data (estándar en php-fpm-alpine)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Optimizar Laravel para producción (ignoramos fallos si falta conexión a BD en el build)
RUN php artisan config:cache || true && \
    php artisan route:cache || true && \
    php artisan view:cache || true

EXPOSE 8000

# Iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
