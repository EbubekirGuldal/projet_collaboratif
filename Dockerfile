# Stage 1 : build des assets front
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package.json package-lock.json webpack.config.js ./
COPY assets/ ./assets/
RUN npm ci && npm run build

# Stage 2 : dependances PHP (prod uniquement)
FROM composer:2 AS composer_builder
WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# Stage 3 : image finale PHP-FPM + Nginx
FROM php:8.2-fpm-alpine AS app

RUN apk add --no-cache \
    nginx \
    libpq-dev \
    icu-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        intl \
        ctype \
        iconv \
    && rm -rf /tmp/*

WORKDIR /var/www/html

COPY --from=composer_builder /app/vendor ./vendor
COPY . .
COPY --from=node_builder /app/public/build ./public/build

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data var

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
