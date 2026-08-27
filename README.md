# (RE)Sources Relationnelles

Plateforme de partage de ressources destinees a renforcer les relations
sociales : publication et consultation de ressources, interactions
(commentaires, favoris, partages), moderation, et administration.

Application web Symfony, doublee d'une API JSON consommee par un prototype
mobile.

## Sommaire

- [Stack technique](#stack-technique)
- [Prerequis](#prerequis)
- [Installation locale](#installation-locale)
- [Lancement avec Docker](#lancement-avec-docker)
- [Tests](#tests)
- [Integration continue](#integration-continue)
- [Structure du depot](#structure-du-depot)
- [Documentation](#documentation)
- [Contribuer](#contribuer)

## Stack technique

| Couche | Technologie |
|---|---|
| Langage | PHP 8.2 |
| Framework | Symfony 7.4 |
| Base de donnees | MySQL 8 (Doctrine ORM 3, migrations Doctrine) |
| API | API Platform 4, authentification JWT (LexikJWTAuthenticationBundle) |
| Front | Twig, Bootstrap 5, Webpack Encore |
| Administration | EasyAdmin 4 |
| Televersement | VichUploaderBundle |
| Mobile | Prototype Expo / React Native (`mobile/`) |
| Conteneurisation | Image multi-stage PHP-FPM + Nginx, publiee sur GHCR |
| Integration continue | GitHub Actions |

## Prerequis

- PHP 8.2 avec les extensions `ctype`, `iconv`, `intl`, `pdo_mysql`
- Composer 2
- Node.js 20 et npm
- MySQL 8
- Docker et Docker Compose, pour le lancement conteneurise

## Installation locale

```bash
git clone git@github.com:EbubekirGuldal/projet_collaboratif.git
cd projet_collaboratif

composer install
npm ci && npm run build
```

### Configuration

`.env.dist` est versionne et ne contient **que des valeurs par defaut sans
secret**. Symfony le charge automatiquement quand `.env` est absent, ce qui
permet a un clone frais et a la CI de demarrer sans configuration.

Pour vos propres valeurs, creez un `.env.local` — ignore par git :

```dotenv
DATABASE_URL="mysql://<utilisateur>:<motdepasse>@127.0.0.1:3306/projet_co?serverVersion=8.0&charset=utf8mb4"
APP_SECRET=<32 octets aleatoires>
JWT_PASSPHRASE=<phrase aleatoire>
MAILER_DSN=<votre DSN, ou null://null>
```

> Ne mettez jamais de secret dans `.env` ni dans `.env.dist`. Voir
> [SECURITY.md](SECURITY.md).

### Base de donnees et cles

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

### Lancement

```bash
symfony serve            # ou : php -S localhost:8000 -t public
npm run watch            # recompilation des assets
```

## Lancement avec Docker

Les valeurs sensibles n'ont pas de valeur par defaut : `compose` refuse de
demarrer si elles manquent. Placez-les dans un `.env.local` a la racine :

```dotenv
APP_SECRET=<32 octets aleatoires>
JWT_PASSPHRASE=<phrase aleatoire>
MYSQL_PASSWORD=<mot de passe applicatif>
MYSQL_ROOT_PASSWORD=<mot de passe root>
```

```bash
docker compose --env-file .env.local up -d
docker compose logs -f app
```

L'application ecoute sur <http://localhost:8080> (`APP_PORT` pour changer de
port). L'entrypoint genere les cles JWT si elles manquent, attend la base,
applique les migrations et prechauffe le cache.

Detail de l'image, des volumes et de l'entrypoint :
[docs/conteneurisation.md](docs/conteneurisation.md).

## Tests

```bash
php bin/console doctrine:schema:create --env=test
php bin/phpunit
```

Le proces-verbal de la derniere campagne est dans
[docs/pv_de_tests.md](docs/pv_de_tests.md).

## Integration continue

`.github/workflows/ci.yml` s'execute a chaque push et chaque pull request :

| Job | Ce qu'il verifie |
|---|---|
| `build` | Installation des dependances PHP et compilation des assets front |
| `tests` | Suite PHPUnit sur un service MySQL 8, schema de test cree et cles JWT generees |
| `security` | `composer audit`, `npm audit`, PHPStan et PHP_CodeSniffer sur `src/` |
| `deploy-check` | Monte la stack compose, applique les migrations sur base vide, verifie que `/healthz` repond 200 et compte les tables creees |
| `docker` | Construit l'image ; la publie sur GHCR uniquement depuis `master` |

`.github/workflows/codeql.yml` complete par une analyse statique de securite,
sur push, sur pull request et chaque lundi.

## Structure du depot

```
.github/            Pipeline, gabarits d'issue et de PR, Dependabot, labels
assets/             Sources front (JS, CSS) compilees par Webpack Encore
bin/                Console Symfony et lanceur PHPUnit
config/             Configuration Symfony (securite, doctrine, bundles)
docker/             nginx.conf, php.ini, entrypoint.sh de l'image
docs/               Documentation de projet (voir ci-dessous)
migrations/         Migrations Doctrine
mobile/             Prototype mobile Expo / React Native
public/             Racine web, assets compiles, fichiers televerses
src/                Code applicatif (Controller, Entity, Security, Repository)
templates/          Vues Twig
tests/              Suite PHPUnit
translations/       Fichiers de traduction
```

## Documentation

| Document | Objet |
|---|---|
| [docs/plan_de_deploiement.md](docs/plan_de_deploiement.md) | Architecture, environnements, etapes de deploiement, versioning, rollback |
| [docs/plan_de_securisation.md](docs/plan_de_securisation.md) | Analyse des vulnerabilites (OWASP Top 10), actions correctives, gestion de crise |
| [docs/conteneurisation.md](docs/conteneurisation.md) | Composition de l'image, entrypoint, volumes |
| [docs/gestion_des_tickets.md](docs/gestion_des_tickets.md) | Methodologie prestataire / client, gravites et delais, escalade |
| [docs/rgpd.md](docs/rgpd.md) | Donnees personnelles, bases legales, droits des personnes |
| [docs/veille_technologique.md](docs/veille_technologique.md) | Sources suivies, methode, transformation en tickets |
| [docs/pv_de_tests.md](docs/pv_de_tests.md) | Proces-verbal de recette et suivi des anomalies |
| [docs/architecture.md](docs/architecture.md) | Architecture applicative |
| [docs/cahier_de_tests.md](docs/cahier_de_tests.md) | Cas de test fonctionnels |
| [docs/recette_fonctionnelle.md](docs/recette_fonctionnelle.md) | Recette fonctionnelle |

Politique de securite et signalement de vulnerabilite : [SECURITY.md](SECURITY.md).

## Contribuer

Lisez [CONTRIBUTING.md](CONTRIBUTING.md) avant toute contribution. En resume :

- une branche par sujet, prefixee `feat/`, `fix/`, `docs/`, `chore/` ou `refactor/` ;
- messages de commit au format Conventional Commits ;
- **aucun push direct sur `master`** : tout passe par une pull request ;
- le pipeline doit etre vert, et toute anomalie corrigee est accompagnee d'un
  test de non-regression.

## Equipe

Ebubekir Guldal, Baptiste Sohn, Wylson <!-- À COMPLÉTER : nom complet de Wylson, et role de chacun -->

## Licence

<!-- À COMPLÉTER : le projet a-t-il une licence ? Sans fichier LICENSE, le code est par defaut sous droit d'auteur exclusif, ce qui interdit toute reutilisation. -->
