# Conteneurisation

## Ce que produit la CI

Le job `Build & Push Docker image` construit l'image a chaque push et a chaque
pull request. Le push vers le registre n'a lieu que sur `master` :

    ghcr.io/ebubekirguldal/projet_collaboratif:latest

Construire aussi sur les PR permet de detecter un `Dockerfile` casse avant le
merge, sans publier d'image intermediaire.

## Composition de l'image

Trois stages :

| Stage | Base | Role |
|---|---|---|
| `node_builder` | `node:20-alpine` | `npm ci` + `npm run build` (Webpack Encore) → `public/build` |
| `composer_builder` | `composer:2` | dependances PHP **sans** `require-dev`, autoloader optimise |
| `app` | `php:8.2-fpm-alpine` | Nginx + PHP-FPM, recoit les artefacts des deux stages |

Extensions PHP compilees : `intl`, `pdo_mysql`, `opcache`. La chaine de
compilation (`$PHPIZE_DEPS`, `icu-dev`) est retiree apres usage ; seul
`icu-libs`, necessaire a `intl` a l'execution, reste dans l'image.

> **MySQL, pas PostgreSQL.** Les migrations du projet sont specifiques MySQL
> (`ENGINE = InnoDB`, `AUTO_INCREMENT`, `utf8mb4`). L'extension et le service
> `database` de `compose.yaml` sont alignes dessus.

Le `.dockerignore` empeche `vendor/`, `node_modules/`, `public/build/` et `.env`
de la machine locale d'entrer dans le contexte de build : les dependances et les
assets viennent toujours des stages, jamais du poste du developpeur.

## Demarrage du conteneur

`docker/entrypoint.sh`, dans l'ordre :

1. genere la paire de cles JWT si elle est absente (`--skip-if-exists`) ;
2. attend que la base reponde (30 tentatives, 2 s) ;
3. applique les migrations (`--allow-no-migration`) ;
4. prechauffe le cache et rend `var/` a `www-data` ;
5. lance Nginx puis `exec php-fpm` (PID 1, pour que les signaux d'arret soient recus).

`GET /healthz` est servi directement par Nginx, sans PHP ni base : c'est la sonde
du `HEALTHCHECK`.

## Lancer la stack en local

Les variables sensibles n'ont pas de valeur par defaut : `compose` refuse de
demarrer si elles manquent. Placez-les dans un `.env.local` a la racine :

    APP_SECRET=<32 octets aleatoires>
    JWT_PASSPHRASE=<phrase aleatoire>
    MYSQL_PASSWORD=<mot de passe applicatif>
    MYSQL_ROOT_PASSWORD=<mot de passe root>

Puis :

    docker compose --env-file .env.local up -d
    docker compose logs -f app

L'application ecoute sur http://localhost:8080 (`APP_PORT` pour changer de port).

## Volumes

| Volume | Monte sur | Pourquoi |
|---|---|---|
| `database_data` | `/var/lib/mysql` | persistance de la base |
| `jwt_keys` | `/var/www/html/config/jwt` | sans lui, les cles sont regenerees a chaque demarrage et **tous les tokens emis deviennent invalides** |
| `uploads` | `/var/www/html/public/uploads` | photos televersees (VichUploader) |

## Limite connue

`migrations/` ne contient aucune migration sur `master` a ce jour. L'entrypoint
passe donc `--allow-no-migration` et demarre sur une base vide : le schema ne
sera cree qu'une fois les migrations des branches de developpement fusionnees.
