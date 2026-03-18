# Plan de tests

## 1. Contexte

Le projet "Ressources Relationnelles" est une application Symfony 7.3 composee :
- d'une interface web pour les utilisateurs finaux
- d'une API JSON / API Platform avec authentification JWT
- d'un back-office EasyAdmin reserve aux administrateurs

Les fonctionnalites identifiees dans le code sont les suivantes :
- consultation du fil de ressources
- inscription, connexion, verification email et reinitialisation de mot de passe
- gestion du profil utilisateur
- creation, consultation et modification de ressources
- commentaires, likes, favoris, partages et signalements
- administration des utilisateurs, ressources, commentaires et logs de moderation

## 2. Objectifs

Les tests ont pour objectifs de :
- verifier que les parcours metier principaux fonctionnent de bout en bout
- securiser les droits d'acces web, API et administration
- detecter les regressions sur les formulaires, uploads, interactions et API
- disposer d'un support de recette exploitable pour la validation finale

## 3. Perimetre

### Inclus

- Interface web :
  - page d'accueil et recherche
  - connexion / inscription
  - verification email
  - mot de passe oublie
  - profil utilisateur
  - creation / edition / consultation de ressource
  - ajout de commentaire
  - like / favori / partage / signalement
- API :
  - `POST /auth/login`
  - `POST /auth/register`
  - `POST /api/verify-token`
  - `POST /api/change-password`
  - `GET /api/resources`
  - `GET /api/resources/{id}`
  - `PATCH /api/resources/{id}`
  - `GET /api/users/{id}`
  - `PATCH /api/users/{id}`
- Administration :
  - acces `ROLE_ADMIN`
  - dashboard
  - CRUD EasyAdmin

### Hors perimetre

- tests de charge et de performance pousses
- audit de securite approfondi de type pentest
- compatibilite navigateurs anciens
- supervision infra, sauvegarde et deploiement CI/CD

## 4. Methodologie

La strategie retenue est une approche par risques, combinee a plusieurs niveaux de tests :

### 4.1 Tests de verification technique

- revue du code pour identifier les regles metier, les controles d'acces et les points sensibles
- execution des tests automatises existants
- verification de la configuration des routes et de la securite

### 4.2 Tests fonctionnels

- tests manuels sur les parcours utilisateurs visibles
- controle des messages, redirections, validations et persistence en base
- verification des cas nominaux et des cas d'erreur

### 4.3 Tests API

- verification des reponses JSON, des codes HTTP et du comportement JWT
- verification des restrictions d'acces sur les endpoints proteges
- verification de la coherence entre API Platform et logique metier

### 4.4 Tests de recette

- validation finale par scenarios representatifs du besoin metier
- prononce de la recette apres traitement des anomalies bloquantes

## 5. Environnement de test

Environnement cible observe dans le projet :
- PHP 8.2
- Symfony 7.3
- Doctrine ORM
- API Platform 4.2
- LexikJWTAuthenticationBundle
- EasyAdmin 4
- VichUploaderBundle
- PHPUnit 11

Jeu de donnees recommande :
- 1 utilisateur standard actif
- 1 utilisateur admin
- 1 utilisateur inactif
- plusieurs ressources avec et sans image
- au moins 1 ressource favoritee
- au moins 1 ressource commentee
- au moins 1 moderation log

## 6. Typologie des tests

### Tests unitaires

Faible couverture actuelle. A renforcer si le projet evolue, notamment sur :
- `DashboardPeriodResolver`
- `DashboardStatsMath`
- logique de tri / filtrage

### Tests fonctionnels automatises

Couverture actuelle identifiee :
- `tests/Security/LoginTest.php`

Etat observe le 16/03/2026 :
- l'execution de `php bin/phpunit` est en erreur
- cause : variable d'environnement `KERNEL_CLASS` absente de `phpunit.dist.xml`

### Tests manuels

Necessaires pour :
- uploads d'image
- verification email
- reset password
- parcours de moderation
- verification UX et messages de retour

## 7. Criteres d'entree

La campagne peut commencer si :
- l'application demarre correctement
- la base de donnees est migree
- les donnees de test sont disponibles
- les acces web, API et admin sont connus
- les services d'email et d'upload sont configures ou simules

## 8. Criteres de sortie

La campagne est consideree satisfaisante si :
- tous les tests critiques et majeurs sont executes
- aucune anomalie bloquante ne subsiste
- les anomalies mineures restantes sont documentees et acceptees
- la recette fonctionnelle est signee

## 9. Livrables

Les livrables produits sont :
- `docs/plan_de_tests.md`
- `docs/cahier_de_tests.md`
- `docs/pv_de_tests.md`
- `docs/recette_fonctionnelle.md`

## 10. Risques et points de vigilance

Points sensibles identifies pendant l'analyse :
- coexistence de deux modes d'authentification : session web et JWT API
- upload de fichiers via Vich Uploader sur profil et ressources
- controle d'acces sur les modifications de ressources
- verification email et reset password dependants de l'envoi d'emails
- tests automatiques actuellement bloques par la configuration PHPUnit

Points de vigilance issus du code et a confirmer en test :
- `AuthApiController::register()` utilise `firstName` dans une condition puis `firstname` dans l'affectation
- `ResourceController::edit()` compare `resourceStatus` a la chaine `Publiee` alors que l'entite utilise l'enum `ResourceStatus`
- les routes `/profile/edit` et certains parcours profil meritent une verification stricte des methodes HTTP et des validations

## 11. Organisation de campagne

Ordre recommande :
1. Smoke test technique
2. Authentification web et API
3. Profil utilisateur
4. Ressources et interactions
5. Administration
6. Recette finale

Priorisation :
- Priorite haute : connexion, securite, creation ressource, edition, profil, reset password, API protegee
- Priorite moyenne : recherche, tri, favoris, partages, signalement, moderation
- Priorite basse : ergonomie secondaire, cas de confort non bloquants
