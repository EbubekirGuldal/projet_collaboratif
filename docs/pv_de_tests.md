# PV de tests

## 1. Identification

- Projet : Ressources Relationnelles
- Date de preparation du PV : 16/03/2026
- Type de campagne : verification technique + preparation recette
- Auteur : Codex

## 2. Perimetre de la campagne

Perimetre couvert par ce PV :
- analyse du code applicatif
- verification des routes declarees
- tentative d'execution de la campagne automatisee PHPUnit
- preparation du cahier de tests et de la recette

Perimetre non execute dans ce PV :
- execution manuelle complete des cas de test
- validation metier par commanditaire
- test de tous les emails sortants en conditions reelles

## 3. Resultats observes

### 3.1 Tests automatises

- Commande executee : `php bin/phpunit`
- Date d'execution : 16/03/2026
- Resultat global : KO

Detail :
- 1 test detecte
- 0 assertion executee
- 1 erreur bloquante

Erreur relevee :
- `LogicException: You must set the KERNEL_CLASS environment variable...`

Conclusion :
- la campagne automatisee n'est pas exploitable en l'etat
- la configuration de `phpunit.dist.xml` doit etre completee avant toute execution fiable

### 3.2 Verification du perimetre applicatif

Routes metier identifiees et coherentes avec le besoin :
- authentification web
- authentification API JWT
- profil utilisateur
- ressources et interactions
- administration EasyAdmin

## 4. Anomalies et points de vigilance

### ANO-001 - Blocage de la campagne PHPUnit

- Gravite : bloquante
- Description : `KERNEL_CLASS` n'est pas defini dans `phpunit.dist.xml`
- Impact : impossibilite d'executer les tests fonctionnels Symfony
- Action recommandee : definir `KERNEL_CLASS=App\\Kernel`

### ANO-002 - Incoherence probable sur le prenom en inscription API

- Gravite : majeure
- Description : dans `AuthApiController::register()`, la condition utilise `firstName` alors que l'affectation utilise `firstname`
- Impact : le prenom peut ne pas etre enregistre correctement selon le payload envoye
- Action recommandee : harmoniser la cle du JSON et ajouter un test API dedie

### ANO-003 - Incoherence probable sur le statut de publication d'une ressource

- Gravite : majeure
- Description : `ResourceController::edit()` compare `resourceStatus` a la chaine `Publiee` alors que l'entite utilise l'enum `ResourceStatus`
- Impact : comportement de publication potentiellement incorrect a l'edition
- Action recommandee : corriger la comparaison et ajouter des tests de non-regression

### ANO-004 - Parcours profil a verifier finement

- Gravite : mineure a majeure selon resultat
- Description : la route `/profile/edit` accepte `ANY` et ne montre pas de validation metier visible dans le controleur
- Impact : risque de comportement non maitrise selon la requete
- Action recommandee : executer les cas du cahier de tests et restreindre la methode HTTP si besoin

## 5. Synthese

- Etat actuel : recette non prononcee
- Motif principal : campagne non executee completement et presence de points bloquants / majeurs a verifier
- Decision : ajourne

## 6. Conditions de levee

La recette pourra etre prononcee si :
- le blocage PHPUnit est corrige
- les cas critiques du cahier de tests sont executes
- aucune anomalie bloquante ne subsiste
- les anomalies majeures sont corrigees ou acceptees explicitement

## 7. Visa

- Preparation technique : a jour
- Validation QA / equipe projet : a renseigner
- Validation commanditaire : a renseigner
