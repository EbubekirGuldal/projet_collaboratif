# Recette fonctionnelle

## 1. Objet

Ce document sert de support de validation finale du projet "Ressources Relationnelles". Il permet de confirmer que les fonctionnalites attendues sont conformes au besoin metier et exploitables par les utilisateurs.

## 2. Participants a la recette

- Responsable projet
- Testeur / recetteur
- Eventuel representant metier
- Administrateur de demonstration

## 3. Preconditions de recette

Avant la recette, verifier que :
- l'application est accessible
- la base contient un jeu de donnees minimum
- un compte utilisateur standard et un compte admin sont disponibles
- la messagerie ou un equivalent de test est active
- les uploads d'images sont actifs

## 4. Scenarios de recette

### REC-01 - Parcours visiteur

- Ouvrir la page d'accueil
- Rechercher une ressource
- Consulter la fiche detail d'une ressource
- Resultat attendu :
  - le fil s'affiche correctement
  - la recherche filtre les resultats
  - la fiche ressource est lisible et complete

### REC-02 - Parcours inscription / connexion

- Creer un compte via `/register`
- Se connecter via `/login` ou via l'API
- Verifier la presence du compte dans l'application
- Resultat attendu :
  - le compte est cree
  - l'utilisateur peut se connecter
  - les informations du profil sont coherentes

### REC-03 - Parcours profil

- Modifier username, prenom, nom
- Changer le mot de passe
- Modifier la photo de profil
- Resultat attendu :
  - les modifications sont prises en compte
  - les messages retour utilisateur sont clairs

### REC-04 - Parcours ressource

- Creer une ressource
- Consulter la ressource creee
- Modifier sa ressource
- Ajouter un commentaire
- Resultat attendu :
  - la ressource est creee et visible
  - les modifications sont persistantes
  - le commentaire apparait sur la fiche

### REC-05 - Parcours interactions

- Liker une ressource
- Ajouter puis retirer un favori
- Partager une ressource
- Signaler une ressource
- Resultat attendu :
  - les compteurs et etats evoluent correctement
  - le signalement est pris en compte

### REC-06 - Parcours administration

- Se connecter avec un compte admin
- Acceder au dashboard
- Ouvrir les CRUD utilisateurs, ressources, commentaires, moderation
- Resultat attendu :
  - l'acces admin est reserve aux administrateurs
  - les ecrans de gestion sont disponibles

## 5. Criteres d'acceptation

La recette est consideree conforme si :
- tous les scenarios critiques sont valides
- aucun blocage n'empeche un parcours principal
- les controles d'acces sont respectes
- les donnees creees ou modifiees sont coherentes en base

## 6. Tableau de decision

| Scenario | Statut | Commentaire |
| --- | --- | --- |
| REC-01 Parcours visiteur | A renseigner |  |
| REC-02 Parcours inscription / connexion | A renseigner |  |
| REC-03 Parcours profil | A renseigner |  |
| REC-04 Parcours ressource | A renseigner |  |
| REC-05 Parcours interactions | A renseigner |  |
| REC-06 Parcours administration | A renseigner |  |

## 7. Decision finale

- Recette prononcee : oui / non
- Date : a renseigner
- Reserve(s) : a renseigner
- Signataire : a renseigner

## 8. Traceabilite avec le cahier de tests

Correspondance recommandee :
- REC-01 <-> TC-RES-01 a TC-RES-03
- REC-02 <-> TC-AUTH-01 a TC-AUTH-12
- REC-03 <-> TC-PROF-01 a TC-PROF-09
- REC-04 <-> TC-RES-04 a TC-RES-10
- REC-05 <-> TC-RES-11 a TC-RES-16
- REC-06 <-> TC-ADM-01 a TC-ADM-03
