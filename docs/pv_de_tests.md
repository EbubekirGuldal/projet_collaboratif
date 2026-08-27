# PV de tests

## 1. Identification

- Projet : (RE)Sources Relationnelles
- Date de preparation initiale : 16/03/2026
- Date de revision : 27/08/2026
- Type de campagne : verification technique + preparation recette
- Perimetre de la revision : verification dans le code du statut reel des
  anomalies ANO-001 a ANO-004, correction de celles encore ouvertes, ajout des
  tests de non-regression correspondants

## 2. Perimetre de la campagne

Couvert :

- analyse du code applicatif ;
- verification des routes declarees ;
- execution de la campagne automatisee PHPUnit, desormais dans le pipeline
  d'integration continue a chaque push et chaque pull request ;
- verification du chemin de deploiement : montage de la stack conteneurisee,
  application des migrations sur base vide, interrogation de l'application.

Non execute :

- execution manuelle complete des cas du cahier de tests ;
- validation metier par le commanditaire ;
- test des courriels sortants en conditions reelles.

## 3. Resultats observes

### 3.1 Tests automatises

La campagne n'est plus lancee a la main : le job `tests` du pipeline
`.github/workflows/ci.yml` execute `php bin/phpunit` sur un service MySQL 8,
apres creation du schema de test et generation des cles JWT.

- Resultat au 27/08/2026 : **OK**
- 7 tests, 8 assertions, 0 echec
- Fichiers : `tests/MainTest.php`, `tests/Security/LoginTest.php`,
  `tests/Security/ResourceAccessResolverTest.php`

Le blocage decrit en mars (`LogicException: You must set the KERNEL_CLASS
environment variable`) n'existe plus : `phpunit.dist.xml` declare
`KERNEL_CLASS=App\Kernel`.

### 3.2 Verification du chemin de deploiement

Le job `deploy-check` monte la stack `compose.yaml`, laisse l'entrypoint
appliquer les migrations sur une base vierge, puis controle le resultat.

- `GET /healthz` : **200**
- Migrations appliquees : **1**
- Tables applicatives creees : **13**

### 3.3 Verification du perimetre applicatif

Routes metier identifiees et coherentes avec le besoin : authentification web,
authentification API JWT, profil utilisateur, ressources et interactions,
administration EasyAdmin.

## 4. Anomalies

### Tableau de suivi

| Ref | Objet | Gravite | Statut au 27/08/2026 |
|---|---|---|---|
| ANO-001 | Blocage de la campagne PHPUnit | Bloquante | **Corrigee** |
| ANO-002 | Prenom perdu a l'inscription API | Majeure | **Corrigee** |
| ANO-003 | Statut de publication compare a une chaine | Majeure | **Corrigee** |
| ANO-004 | Routes de profil sans restriction de methode HTTP | Mineure | **Ouverte** |
| ANO-005 | Commandes de jeu de donnees inexploitables | Majeure | **Ouverte** |

### ANO-001 — Blocage de la campagne PHPUnit

- **Gravite** : bloquante
- **Statut** : corrigee
- **Constat initial** : `KERNEL_CLASS` n'etait pas defini dans `phpunit.dist.xml`.
- **Verification** : la variable est presente, `<server name="KERNEL_CLASS" value="App\Kernel" />`.
  Elle y figurait en double ; le doublon a ete retire.
- **Preuve** : le job `tests` execute la suite a chaque push et rend 7 tests / 8
  assertions / 0 echec.

### ANO-002 — Prenom perdu a l'inscription API

- **Gravite** : majeure
- **Statut** : corrigee
- **Constat** : dans `AuthApiController::register()`, la condition portait sur
  `$data["firstName"]` mais la valeur etait lue dans `$data["firstname"]`.
- **Impact reel mesure** : la cle `firstname` etant absente du corps JSON, sa
  lecture valait `null`. Comme `User::$firstName` est nullable, aucune erreur
  n'etait levee : le prenom etait **silencieusement perdu** et la reponse HTTP
  restait un 200. Le client n'avait aucun moyen de detecter la perte.
- **Correction** : lecture de `$data["firstName"]`, et emploi de `??` pour ne pas
  declencher d'avertissement PHP lorsque le champ n'est pas transmis.
- **Test de non-regression** : `tests/Api/AuthApiRegisterTest.php`, deux cas —
  le prenom et le nom sont bien persistes en base ; l'inscription reste acceptee
  sans ces champs. Ces tests echouent sur le code d'avant correction.

### ANO-003 — Statut de publication compare a une chaine

- **Gravite** : majeure
- **Statut** : corrigee
- **Verification** : `ResourceController::edit()` (ligne 76) et la methode de
  publication (ligne 183) utilisent desormais
  `in_array($resource->getResourceStatus(), [ResourceStatus::PUBLIC, ResourceStatus::SHARED], true)`,
  soit une comparaison stricte sur l'enum. Aucune comparaison a la chaine
  `Publiee` ne subsiste dans `src/Controller/` ni dans `templates/`.
- **Reserve** : la meme famille de defaut subsiste dans les commandes de jeu de
  donnees. Elle fait l'objet de ANO-005, ouverte separement plutot que de
  maintenir ANO-003 ouverte pour un autre perimetre.

### ANO-004 — Routes de profil sans restriction de methode HTTP

- **Gravite** : mineure
- **Statut** : ouverte
- **Constat** : `ProfileController` restreint la methode HTTP sur la plupart de
  ses routes (`methods: ['GET']` ou `['POST']`), mais trois d'entre elles ne le
  font pas : `/profile` (ligne 23), `/profile/edit` (ligne 122) et
  `/profile/dashboard` (ligne 357). Elles acceptent donc toutes les methodes.
- **Impact** : `/profile/edit` traite un formulaire ; l'absence de restriction
  elargit la surface d'appel sans benefice. L'impact reste limite tant que la
  protection CSRF du formulaire s'applique.
- **Action recommandee** : ajouter `methods: ['GET']` sur `/profile` et
  `/profile/dashboard`, `methods: ['GET', 'POST']` sur `/profile/edit`.
- **Non corrigee dans cette campagne** : le comportement attendu de
  `/profile/edit` doit d'abord etre confirme par l'equipe.

<!-- À COMPLÉTER : /profile/edit doit-il accepter autre chose que GET et POST ? Un client mobile appelle-t-il ces routes avec PUT ou PATCH ? -->

### ANO-005 — Commandes de jeu de donnees inexploitables

- **Gravite** : majeure
- **Statut** : ouverte
- **Constat** : deux commandes echouent a l'execution.
  - `DemoDataCommand` (ligne 340) appelle
    `$resource->setResourceStatus($definition['status'])` avec la chaine
    `'Publiee'`, alors que la signature est
    `setResourceStatus(ResourceStatus $resourceStatus)` : **TypeError**. La ligne
    suivante appelle `setVisibilityStatus()`, methode qui **n'existe sur aucune
    entite** : erreur fatale.
  - `AdminStatsTestDataCommand` (ligne 128) insere en SQL direct dans une colonne
    `visibility_status` **absente du schema** derive des entites, avec des
    valeurs `'Publiee'`, `'Brouillon'`, `'Archivee'` qui ne correspondent a
    aucun cas de l'enum (`private`, `shared`, `public`, `under_review`).
- **Impact** : aucune des deux commandes ne peut alimenter une base de
  demonstration. C'est directement genant pour une demonstration en soutenance.
- **Pourquoi non corrigee ici** : la correction suppose de trancher si la notion
  de « visibilite » distincte du statut a ete retiree du modele volontairement,
  ou si sa suppression est elle-meme une regression. Ce choix appartient a
  l'equipe.

<!-- À COMPLÉTER : la propriete visibilityStatus a-t-elle ete retiree de l'entite Resource volontairement ? Si oui, les deux commandes doivent etre reecrites sur le seul ResourceStatus ; si non, la propriete doit etre reintroduite avec sa migration. -->

## 5. Synthese

- Anomalies bloquantes : **aucune**
- Anomalies majeures ouvertes : **une** (ANO-005), circonscrite aux commandes de
  jeu de donnees, sans effet sur l'application en fonctionnement
- Anomalies mineures ouvertes : **une** (ANO-004)
- Campagne automatisee : **executee et verte**, a chaque push
- Chemin de deploiement : **verifie automatiquement**

**Decision : recette prononcee sous reserve.**

La reserve porte sur ANO-005 et ANO-004, aucune des deux n'affectant le service
rendu aux utilisateurs. Le motif d'ajournement de mars — campagne automatisee
inexploitable — est leve.

## 6. Conditions de levee de la reserve

- ANO-005 : arbitrage de l'equipe sur `visibilityStatus`, puis reecriture des
  deux commandes et execution reussie de chacune.
- ANO-004 : restriction des methodes HTTP sur les trois routes concernees.
- Execution des cas critiques du cahier de tests par le commanditaire.

## 7. Visa

- Preparation technique : a jour au 27/08/2026
- Validation QA / equipe projet : <!-- À COMPLÉTER : qui vise, a quelle date ? -->
- Validation commanditaire : <!-- À COMPLÉTER : qui vise, a quelle date ? -->
