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

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .

RUN composer run-script post-autoload-dump || true
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
