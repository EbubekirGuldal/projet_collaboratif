#!/bin/sh
set -eu

APP_ENV="${APP_ENV:-prod}"
export APP_ENV

echo "[entrypoint] environnement : $APP_ENV"

# Les cles JWT sont gitignorees, donc absentes de l'image. On les genere au
# premier demarrage. Montez config/jwt sur un volume si vous voulez que les
# tokens emis survivent a un redemarrage du conteneur.
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

# Attente active de la base : depends_on/condition ne couvre pas compose down/up
# ni un redemarrage du service base pendant que l'app tourne.
echo "[entrypoint] attente de la base de donnees..."
i=0
until php bin/console doctrine:query:sql "SELECT 1" --quiet >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 30 ]; then
        echo "[entrypoint] base injoignable apres 60s, abandon" >&2
        exit 1
    fi
    sleep 2
done

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console cache:warmup

# php-fpm ecrit dans var/ en www-data ; le cache vient d'etre (re)genere en root
chown -R www-data:www-data var

nginx -g "daemon off;" &
exec php-fpm
