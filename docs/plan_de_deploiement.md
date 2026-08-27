# Plan de deploiement

Ce plan decrit le deploiement de (RE)Sources Relationnelles. Il s'appuie
exclusivement sur ce qui existe dans le depot : `.github/workflows/ci.yml`,
`Dockerfile`, `compose.yaml` et `docker/entrypoint.sh`.

> **Limite assumee, a lire en premier.**
> **Aucun serveur cible n'est provisionne a ce jour.** La chaine s'arrete a la
> publication d'une image conteneur verifiee sur GitHub Container Registry.
> L'environnement de production decrit en section 3.3 est une **cible**, pas un
> existant. Tout ce qui la concerne est signale comme tel ; rien n'y est presente
> comme deploye.

## 1. Architecture technique

### 1.1 Composants applicatifs

| Composant | Role | Technologie |
|---|---|---|
| Serveur web | Terminaison HTTP, service des fichiers statiques | Nginx |
| Application | Traitement des requetes | PHP-FPM 8.2, Symfony 7.4 |
| Base de donnees | Persistance | MySQL 8, InnoDB, utf8mb4 |
| Registre d'images | Distribution de l'image applicative | GitHub Container Registry |

Nginx et PHP-FPM cohabitent **dans le meme conteneur** : Nginx est lance en
tache de fond par l'entrypoint, puis PHP-FPM prend le PID 1 via `exec`, afin que
les signaux d'arret soient recus par le processus applicatif.

### 1.2 Composition de l'image

Trois etapes de construction (`Dockerfile`) :

| Etape | Base | Produit |
|---|---|---|
| `node_builder` | `node:20-alpine` | `public/build` compile par Webpack Encore |
| `composer_builder` | `composer:2` | `vendor/` sans `require-dev`, autoloader optimise |
| `app` | `php:8.2-fpm-alpine` | Image finale : Nginx, PHP-FPM, extensions `intl`, `pdo_mysql`, `opcache` |

Le `.dockerignore` empeche `vendor/`, `node_modules/`, `public/build/` et `.env`
du poste local d'entrer dans le contexte : les dependances et les assets viennent
toujours des etapes de construction.

### 1.3 Flux de donnees

```
Navigateur / application mobile
        |  HTTP
        v
     Nginx  ---- /healthz (reponse directe, sans PHP ni base)
        |  FastCGI 127.0.0.1:9000
        v
     PHP-FPM (Symfony)
        |  PDO
        v
     MySQL 8
```

## 2. Environnements

| Environnement | Existe | Ou | Base de donnees | Usage |
|---|---|---|---|---|
| **Local** | Oui | Poste developpeur | MySQL local ou service `database` de compose | Developpement |
| **Integration continue** | Oui | Runners GitHub `ubuntu-latest` | Service MySQL 8 ephemere | Verification automatique de chaque changement |
| **Cible (production)** | **Non** | A provisionner | A provisionner | Service rendu aux utilisateurs |

## 3. Etapes de deploiement par environnement

### 3.1 Local

1. `composer install` puis `npm ci && npm run build`
2. Valeurs propres au poste dans `.env.local` (jamais dans `.env.dist`)
3. `php bin/console doctrine:migrations:migrate`
4. `php bin/console lexik:jwt:generate-keypair --skip-if-exists`
5. `symfony serve`

Variante conteneurisee, plus proche de la cible :
`docker compose --env-file .env.local up -d`.

### 3.2 Integration continue

Declenchement : tout push et toute pull request vers `master`. Cinq jobs
enchaines, chacun bloquant pour le suivant.

| Job | Etapes | Ce qu'il garantit |
|---|---|---|
| `build` | Installation PHP, compilation des assets | Le projet s'installe et se construit |
| `tests` | Schema de test, cles JWT, `php bin/phpunit` | Le comportement attendu est verifie |
| `security` | `composer audit`, `npm audit`, PHPStan, PHP_CodeSniffer | Aucune vulnerabilite connue haute ou critique |
| `deploy-check` | Stack compose, migrations, `/healthz`, comptage des tables | **Le deploiement fonctionne reellement** |
| `docker` | Construction de l'image, publication conditionnelle | L'image est constructible ; publiee depuis `master` seulement |

Le job `deploy-check` est le pivot de ce plan : il execute le meme chemin qu'un
deploiement reel — image construite depuis le code de la branche, base vierge,
migrations appliquees par l'entrypoint — et echoue si l'application ne repond
pas. Sans lui, ce document ne serait qu'une intention.

Resultat constate au 27/08/2026 : `GET /healthz` → 200, 1 migration appliquee,
13 tables applicatives creees.

### 3.3 Cible — non provisionnee

Sequence prevue une fois un hote disponible :

1. Recuperer l'image : `docker pull ghcr.io/ebubekirguldal/projet_collaboratif:latest`
2. Renseigner les secrets d'environnement (section 4.3)
3. `docker compose --env-file <fichier de secrets> up -d`
4. L'entrypoint genere les cles JWT si absentes, attend la base, applique les
   migrations, prechauffe le cache
5. Verifier `GET /healthz` et l'etat `healthy` du conteneur

<!-- À COMPLÉTER : quel hebergeur est envisage (VPS, PaaS, hebergement mutualise) ? Un nom de domaine est-il reserve ? Qui detient les acces ? -->

## 4. Ressources necessaires

### 4.1 Existantes

| Ressource | Etat | Detail |
|---|---|---|
| Depot de code | En place | GitHub, `EbubekirGuldal/projet_collaboratif` |
| Execution du pipeline | En place | GitHub Actions, runners `ubuntu-latest` |
| Registre d'images | En place | GHCR, authentifie par le `GITHUB_TOKEN` du pipeline |
| Base de donnees de test | En place | Service MySQL 8 ephemere, recree a chaque run |

Aucun secret GitHub n'est requis a ce jour : la publication sur GHCR utilise le
`GITHUB_TOKEN` fourni automatiquement, et `deploy-check` tire ses mots de passe
au hasard a chaque execution.

### 4.2 A provisionner

| Ressource | Besoin | Pourquoi |
|---|---|---|
| Hote d'execution | Docker et Docker Compose | Executer l'image |
| Serveur MySQL 8 | Volume persistant | Le volume `database_data` de `compose.yaml` couvre le cas conteneurise |
| Stockage des televersements | Volume persistant | Les photos sont ecrites dans `public/images/` (`vich_uploader.yaml`) ; sans volume, elles disparaissent a chaque redemarrage |
| Stockage des cles JWT | Volume persistant | Sans le volume `jwt_keys`, les cles sont regenerees au demarrage et **tous les jetons emis deviennent invalides** |
| Terminaison TLS | Certificat et reverse proxy | L'image expose du HTTP en clair sur le port 80 |
| Service SMTP | Compte d'envoi | Reinitialisation de mot de passe et verification d'adresse |
| Nom de domaine | Enregistrement DNS | `DEFAULT_URI` sert a construire les URL des courriels |

### 4.3 Secrets d'execution

Aucun n'a de valeur par defaut : `compose` refuse de demarrer s'il en manque un.

| Variable | Usage |
|---|---|
| `APP_SECRET` | Signature des jetons CSRF et des cookies |
| `JWT_PASSPHRASE` | Protection de la cle privee JWT |
| `MYSQL_PASSWORD` | Compte applicatif de la base |
| `MYSQL_ROOT_PASSWORD` | Compte d'administration de la base |
| `MAILER_DSN` | Service d'envoi de courriels |
| `DEFAULT_URI` | URL publique, utilisee hors contexte HTTP |
| `CORS_ALLOW_ORIGIN` | Origines autorisees pour l'API |

## 5. Strategie de versioning

### 5.1 Existant

- **Branche de reference** : `master`, alimentee uniquement par pull request.
- **Branches de travail** : prefixees `feat/`, `fix/`, `docs/`, `chore/`,
  `refactor/` (voir `CONTRIBUTING.md`).
- **Messages de commit** : Conventional Commits.
- **Image** : un seul tag mobile, `:latest`, republie a chaque merge sur `master`.

### 5.2 Limite du tag unique

`:latest` ne designe pas un contenu stable : `docker pull` a deux moments
distincts peut donner deux images differentes. Un retour arriere n'est donc pas
possible par le registre, puisqu'aucune image anterieure n'est adressable.

### 5.3 Evolution recommandee

1. Poser un tag git `vX.Y.Z` (versionnage semantique) a chaque livraison.
2. Publier l'image sous trois references : `vX.Y.Z`, `sha-<commit>` et `latest`.
3. Deployer par tag immuable, jamais par `latest`.

Cela rend le rollback de la section 6 realisable, ce qu'il n'est pas aujourd'hui.

<!-- À COMPLÉTER : l'equipe adopte-t-elle le versionnage semantique ? Quelle version pour la premiere livraison ? -->

## 6. Gestion des rollbacks

### 6.1 Retour du code applicatif

Tant que les images ne sont pas taguees par version (section 5.3), le retour
arriere passe par le code :

1. `git revert` du commit ou du merge fautif sur `master` — jamais `git reset`,
   qui reecrirait un historique partage ;
2. le pipeline reconstruit et republie `:latest` ;
3. `docker compose pull && docker compose up -d` sur l'hote.

Le delai de retour est donc celui d'un pipeline complet, de l'ordre de quelques
minutes, et non celui d'un changement de tag.

### 6.2 Retour du schema de base

C'est le point sensible : **une migration appliquee ne se defait pas en revenant
sur le code**. Le conteneur precedent redemarrerait sur un schema plus recent
que ce qu'il attend.

- Chaque migration doit avoir un `down()` relu — le gabarit de pull request
  l'impose. La migration initiale `Version20260827090000` en a un : il retire les
  17 contraintes de cle etrangere avant de supprimer les 13 tables.
- Une migration **destructive** (suppression de colonne ou de table) n'est pas
  reversible par son `down()` : les donnees ne reviennent pas. Une telle
  migration doit etre precedee d'une sauvegarde et annoncee dans la section
  « Impact sur le deploiement » de la pull request.

### 6.3 Sauvegarde

<!-- À COMPLÉTER : aucune sauvegarde n'est en place ni automatisee a ce jour. Quelle frequence, quelle retention, ou sont stockees les sauvegardes, et qui teste la restauration ? Sans restauration testee, une sauvegarde n'est pas une garantie. -->

## 7. Pilotage et indicateurs

### 7.1 Mesures produites automatiquement

Toutes proviennent du pipeline et sont consultables dans l'onglet Actions :

| Indicateur | Source | Valeur au 27/08/2026 |
|---|---|---|
| Taux de reussite du pipeline sur `master` | GitHub Actions | Vert depuis la remise en etat |
| Duree d'un pipeline complet | GitHub Actions | environ 5 minutes |
| Tests executes | Job `tests` | 7 tests, 8 assertions, 0 echec |
| Vulnerabilites hautes ou critiques | Job `security` | 0 (37 avis corriges, dont 1 critique et 8 elevees) |
| Deploiement verifie | Job `deploy-check` | `/healthz` 200, 1 migration, 13 tables |
| Image publiee | Job `docker` | `ghcr.io/ebubekirguldal/projet_collaboratif:latest` |

### 7.2 Surveillance de l'application

| Moyen | Etat |
|---|---|
| Sonde `/healthz` servie par Nginx | En place, branchee sur le `HEALTHCHECK` de l'image |
| `HEALTHCHECK` Docker | En place — interval 30 s, timeout 5 s, 3 essais |
| Journaux applicatifs | En place — Monolog vers `stderr`, donc `docker compose logs` |
| Redemarrage automatique | En place — `restart: unless-stopped` |
| Supervision externe, alertes | **Absente** |
| Metriques (temps de reponse, taux d'erreur) | **Absentes** |
| Centralisation des journaux | **Absente** |

La sonde `/healthz` ne verifie que la disponibilite de Nginx : elle repond 200
meme si PHP-FPM ou la base sont indisponibles. C'est volontaire — elle doit
rester insensible aux dependances pour que le `HEALTHCHECK` distingue « le
conteneur est mort » de « une dependance est en panne » — mais cela signifie
qu'elle **ne suffit pas** a affirmer que le service est rendu.

<!-- À COMPLÉTER : une surveillance externe est-elle prevue ? Qui est alerte en cas d'indisponibilite, et par quel canal ? -->

### 7.3 Ce qui reste a construire

Par ordre d'importance pour un passage en production :

1. Provisionner l'hote cible et la terminaison TLS.
2. Mettre en place la sauvegarde de la base **et tester sa restauration**.
3. Passer aux images taguees par version, pour rendre le rollback immediat.
4. Ajouter une supervision externe avec alerte.
5. Ajouter un environnement de recette distinct de la production, pour que le
   commanditaire valide avant mise en service.
