#!/bin/sh
set -e

php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console cache:warmup --env=prod

nginx -g "daemon off;" &
exec php-fpm
