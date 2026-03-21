# Plan de test

## Objectifs

- verifier le parcours public
- verifier les parcours citoyens verifies
- verifier les parcours moderation et administration
- verifier la visibilite des ressources
- verifier l API exploitee par le prototype mobile

## Types de tests

- tests unitaires
- tests fonctionnels web
- tests d integration base de donnees
- recette manuelle

## Jeux de tests prioritaires

| ID | Scenario | Resultat attendu |
|---|---|---|
| T01 | Ouvrir la page d accueil | le fil public s affiche |
| T02 | S inscrire puis verifier son email | le compte passe en verifie |
| T03 | Creer une ressource privee | visible uniquement par l auteur et la moderation |
| T04 | Creer une ressource partagee | visible aux utilisateurs connectes |
| T05 | Creer une ressource publique | visible dans le fil public |
| T06 | Commenter une ressource avec compte verifie | commentaire cree en attente ou approuve selon le role |
| T07 | Moderer un commentaire pending | le statut passe en approved ou rejected |
| T08 | Sauvegarder une ressource | visible dans le profil utilisateur |
| T09 | Partager une ressource | compteur de partage incremente |
| T10 | Signaler une ressource | log de moderation cree |
| T11 | Se connecter en moderateur | acces au back-office moderation |
| T12 | Se connecter en admin | acces au catalogue |
| T13 | Se connecter en super admin | acces a la gestion des utilisateurs |
| T14 | Appeler `GET /api/resources.json` | retour JSON exploitable par le mobile |

## Automatisation existante

- `tests/Security/LoginTest.php`
- `tests/Security/ResourceAccessResolverTest.php`

## Commandes

```bash
php bin/console doctrine:schema:create --env=test
php bin/phpunit
```

## PV de recette synthese

- etat actuel: prototype fonctionnel web + prototype mobile
- anomalies bloquees: aucune lors des tests automatiques actuels
- points a surveiller: donnees de demo, contenus multilingues, enrichissement des tests E2E
