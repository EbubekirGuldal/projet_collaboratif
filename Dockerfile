# syntax=docker/dockerfile:1

# ------------------------------------------------- Stage 1 : assets front
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package.json package-lock.json webpack.config.js ./
RUN npm ci
COPY assets/ ./assets/
RUN npm run build

# ------------------------------------- Stage 2 : dependances PHP (prod only)
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

# ------------------------------------ Stage 3 : image finale PHP-FPM + Nginx
FROM php:8.2-fpm-alpine AS app

# icu-libs est la dependance d'execution de intl ; icu-dev et PHPIZE_DEPS ne
# servent qu'a la compilation et sont retires ensuite pour ne pas alourdir
# l'image. pdo_mysql (et non pdo_pgsql) : les migrations du projet sont MySQL.
RUN apk add --no-cache nginx icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_mysql opcache \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

# Le code d'abord, puis les artefacts des stages de build : dans cet ordre,
# un vendor/ ou public/build/ qui aurait echappe au .dockerignore ne peut pas
# ecraser celui qui vient d'etre construit.
COPY . .
COPY --from=composer_builder /app/vendor ./vendor
COPY --from=node_builder /app/public/build ./public/build

ENV APP_ENV=prod \
    APP_DEBUG=0

# var/ est gitignore donc absent du contexte : il faut le creer.
# assets:install publie les assets des bundles (EasyAdmin, Vich...) dans
# public/bundles, sinon ils repondent 404.
RUN mkdir -p var/cache var/log /run/nginx \
    && php bin/console assets:install public --no-interaction \
    && chown -R www-data:www-data var public

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget -qO- http://127.0.0.1/healthz >/dev/null 2>&1 || exit 1

ENTRYPOINT ["/entrypoint.sh"]
