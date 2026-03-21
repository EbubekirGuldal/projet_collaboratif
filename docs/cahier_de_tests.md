# Cahier de tests

## Convention

- Type : FONC = fonctionnel, API = test API, SEC = securite / droits, REC = recette
- Priorite : H = haute, M = moyenne, B = basse
- Statut : a renseigner pendant la campagne

## 1. Authentification et comptes

### TC-AUTH-01 - Connexion web valide

- Type : FONC
- Priorite : H
- Preconditions : utilisateur actif existant avec mot de passe connu
- Etapes :
1. Ouvrir `/login`
2. Saisir email et mot de passe valides
3. Soumettre le formulaire
- Resultat attendu :
  - redirection vers `/`
  - utilisateur connecte
  - lien de deconnexion visible
  - date `lastConnexion` mise a jour

### TC-AUTH-02 - Connexion web invalide

- Type : FONC
- Priorite : H
- Preconditions : page `/login` accessible
- Etapes :
1. Saisir un email valide avec un mauvais mot de passe
2. Soumettre
- Resultat attendu :
  - connexion refusee
  - message d'erreur affiche
  - aucune session connectee

### TC-AUTH-03 - Inscription web valide

- Type : FONC
- Priorite : H
- Preconditions : aucun compte existant avec l'email utilise
- Etapes :
1. Ouvrir `/register`
2. Renseigner email, username, prenom, nom, mot de passe
3. Cocher l'acceptation des conditions
4. Soumettre
- Resultat attendu :
  - compte cree en base
  - mot de passe chiffre
  - utilisateur connecte
  - email de verification prepare a l'envoi

### TC-AUTH-04 - Inscription sans accepter les conditions

- Type : FONC
- Priorite : M
- Preconditions : page `/register`
- Etapes :
1. Renseigner les champs obligatoires
2. Ne pas cocher les conditions
3. Soumettre
- Resultat attendu :
  - inscription refusee
  - message de validation sur les conditions

### TC-AUTH-05 - Verification email

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte non verifie, lien signe disponible
- Etapes :
1. Ouvrir le lien `/verify/email?...`
- Resultat attendu :
  - compte marque comme verifie
  - message de succes
  - redirection vers l'accueil

### TC-AUTH-06 - Renvoi d'email de verification

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte non verifie
- Etapes :
1. Ouvrir `/verify/resend`
- Resultat attendu :
  - nouvel email emis
  - message de confirmation affiche

### TC-AUTH-07 - Demande de reinitialisation du mot de passe

- Type : FONC
- Priorite : H
- Preconditions : compte existant
- Etapes :
1. Ouvrir `/reset-password`
2. Saisir un email connu
3. Soumettre
- Resultat attendu :
  - message neutre de confirmation
  - generation d'un token de reset
  - email envoye

### TC-AUTH-08 - Reinitialisation du mot de passe

- Type : FONC
- Priorite : H
- Preconditions : token de reset valide
- Etapes :
1. Ouvrir `/reset-password/reset/{token}`
2. Saisir un nouveau mot de passe valide
3. Soumettre
- Resultat attendu :
  - mot de passe mis a jour
  - token invalide apres usage
  - redirection vers `/login`

### TC-AUTH-09 - Login API valide

- Type : API
- Priorite : H
- Preconditions : utilisateur actif existant
- Etapes :
1. Appeler `POST /auth/login` avec `email` et `password` valides
- Resultat attendu :
  - HTTP 200
  - JSON contenant `token`
  - bloc `user` renseigne
  - `lastConnexion` mise a jour

### TC-AUTH-10 - Login API avec mot de passe invalide

- Type : API
- Priorite : H
- Preconditions : utilisateur existant
- Etapes :
1. Appeler `POST /auth/login` avec mauvais mot de passe
- Resultat attendu :
  - HTTP 400
  - message `Mot de passe ou email incorrect`

### TC-AUTH-11 - Login API avec compte inactif

- Type : API
- Priorite : H
- Preconditions : utilisateur existant avec `isActive = false`
- Etapes :
1. Appeler `POST /auth/login` avec identifiants corrects
- Resultat attendu :
  - HTTP 400
  - refus d'authentification

### TC-AUTH-12 - Verification de token API

- Type : API
- Priorite : H
- Preconditions : JWT valide
- Etapes :
1. Appeler `POST /api/verify-token` avec le header Bearer
- Resultat attendu :
  - HTTP 200
  - message `Token valide`

## 2. Profil utilisateur

### TC-PROF-01 - Consultation du profil connecte

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte
- Etapes :
1. Ouvrir `/profile`
- Resultat attendu :
  - page profil affichee

### TC-PROF-02 - Acces au profil sans connexion

- Type : SEC
- Priorite : H
- Preconditions : aucune session active
- Etapes :
1. Ouvrir `/profile`
- Resultat attendu :
  - redirection vers `/login`

### TC-PROF-03 - Modification du profil

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte
- Etapes :
1. Soumettre `/profile/edit` avec `username`, `firstName`, `lastName`
2. Conserver l'email courant
- Resultat attendu :
  - informations mises a jour
  - message de succes

### TC-PROF-04 - Modification de l'email

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte
- Etapes :
1. Soumettre `/profile/edit` avec un nouvel email
- Resultat attendu :
  - email mis a jour
  - `isVerified` repasse a `false`
  - deconnexion de l'utilisateur
  - redirection vers `/login`

### TC-PROF-05 - Changement de mot de passe profil

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte
- Etapes :
1. Appeler `/profile/change-password`
2. Fournir mot de passe actuel correct, nouveau mot de passe et confirmation identiques
- Resultat attendu :
  - mot de passe mis a jour
  - message de succes

### TC-PROF-06 - Changement de mot de passe avec confirmation differente

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte
- Etapes :
1. Saisir deux nouveaux mots de passe differents
- Resultat attendu :
  - modification refusee
  - message d'avertissement

### TC-PROF-07 - Upload photo de profil valide

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte
- Etapes :
1. Soumettre `/profile/photo` avec token CSRF valide
2. Envoyer une image JPG ou PNG de moins de 5 Mo
- Resultat attendu :
  - fichier accepte
  - photo mise a jour
  - message de succes

### TC-PROF-08 - Upload photo invalide

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte
- Etapes :
1. Soumettre un fichier non image ou superieur a 5 Mo
- Resultat attendu :
  - rejet du fichier
  - message d'erreur

### TC-PROF-09 - Suppression de compte

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte, token CSRF valide
- Etapes :
1. Soumettre `/profile/delete`
- Resultat attendu :
  - compte supprime
  - commentaires de l'utilisateur supprimes
  - favoris vides
  - session invalidee
  - redirection vers `/`

## 3. Ressources et interactions

### TC-RES-01 - Consultation du fil d'accueil

- Type : FONC
- Priorite : H
- Preconditions : ressources disponibles
- Etapes :
1. Ouvrir `/`
- Resultat attendu :
  - liste des ressources affichee
  - tri par nouveaute par defaut

### TC-RES-02 - Recherche de ressources

- Type : FONC
- Priorite : M
- Preconditions : plusieurs ressources avec titres ou contenus differents
- Etapes :
1. Ouvrir `/?q=motcle`
- Resultat attendu :
  - seules les ressources correspondantes sont affichees

### TC-RES-03 - Tri top des ressources

- Type : FONC
- Priorite : M
- Preconditions : ressources avec likes/commentaires differents
- Etapes :
1. Ouvrir `/?sort=top`
- Resultat attendu :
  - ressources triees par popularite

### TC-RES-04 - Creation de ressource valide

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte
- Etapes :
1. Ouvrir `/resource/new`
2. Saisir titre, contenu, lien externe eventuel et image eventuelle
3. Soumettre
- Resultat attendu :
  - ressource creee
  - auteur renseigne
  - `publishedAt` renseigne
  - redirection vers la fiche ressource

### TC-RES-05 - Creation de ressource sans connexion

- Type : SEC
- Priorite : H
- Preconditions : aucune session active
- Etapes :
1. Ouvrir `/resource/new`
- Resultat attendu :
  - acces refuse ou redirection vers login

### TC-RES-06 - Consultation d'une ressource

- Type : FONC
- Priorite : H
- Preconditions : ressource existante
- Etapes :
1. Ouvrir `/resource/{id}`
- Resultat attendu :
  - detail de la ressource affiche
  - commentaires visibles

### TC-RES-07 - Ajout d'un commentaire valide

- Type : FONC
- Priorite : H
- Preconditions : utilisateur connecte, ressource existante
- Etapes :
1. Poster un commentaire non vide sur `/resource/{id}`
- Resultat attendu :
  - commentaire cree
  - message de succes

### TC-RES-08 - Ajout d'un commentaire vide

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte
- Etapes :
1. Poster un commentaire vide ou blanc
- Resultat attendu :
  - commentaire refuse
  - message `Le commentaire ne peut pas etre vide`

### TC-RES-09 - Edition de sa propre ressource

- Type : FONC
- Priorite : H
- Preconditions : utilisateur proprietaire de la ressource
- Etapes :
1. Ouvrir `/resource/{id}/edit`
2. Modifier les informations
3. Soumettre
- Resultat attendu :
  - ressource mise a jour
  - message de succes

### TC-RES-10 - Edition d'une ressource d'un autre utilisateur

- Type : SEC
- Priorite : H
- Preconditions : utilisateur connecte non proprietaire et non admin
- Etapes :
1. Ouvrir `/resource/{id}/edit`
- Resultat attendu :
  - acces refuse

### TC-RES-11 - Like d'une ressource

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte, ressource existante
- Etapes :
1. Appeler `POST /resource/{id}/like`
2. Rejouer la meme action
- Resultat attendu :
  - 1er appel : `liked = true`, compteur incremente
  - 2e appel : `liked = false`, compteur decremente

### TC-RES-12 - Like sans connexion

- Type : SEC
- Priorite : H
- Preconditions : aucune session active
- Etapes :
1. Appeler `POST /resource/{id}/like`
- Resultat attendu :
  - HTTP 403

### TC-RES-13 - Favori d'une ressource

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte
- Etapes :
1. Appeler `POST /resource/{id}/favorite`
2. Rejouer la meme action
- Resultat attendu :
  - ajout puis retrait des favoris
  - JSON `favorited` coherent

### TC-RES-14 - Partage d'une ressource

- Type : FONC
- Priorite : M
- Preconditions : ressource existante
- Etapes :
1. Appeler `POST /resource/{id}/share`
2. Rejouer la meme action dans la meme session
- Resultat attendu :
  - 1er appel : compteur incremente
  - 2e appel : compteur non reincremente
  - message `Deja partage dans cette session`

### TC-RES-15 - Signalement valide

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte, token CSRF valide
- Etapes :
1. Soumettre `/resource/{id}/report` avec un motif
- Resultat attendu :
  - creation d'un `ModerationLog`
  - message de succes

### TC-RES-16 - Signalement sans motif

- Type : FONC
- Priorite : M
- Preconditions : utilisateur connecte, token CSRF valide
- Etapes :
1. Soumettre le formulaire sans categorie
- Resultat attendu :
  - signalement refuse
  - message demandant un motif

## 4. API utilisateur et API resource

### TC-API-01 - Consultation publique de la collection des ressources

- Type : API
- Priorite : H
- Preconditions : au moins une ressource existante
- Etapes :
1. Appeler `GET /api/resources`
- Resultat attendu :
  - HTTP 200 sans JWT
  - collection retournee
  - pagination partielle active

### TC-API-02 - Consultation d'une ressource API

- Type : API
- Priorite : H
- Preconditions : ressource existante
- Etapes :
1. Appeler `GET /api/resources/{id}`
- Resultat attendu :
  - HTTP 200
  - donnees de la ressource et du user selon groupe `resource:read`

### TC-API-03 - Patch d'une ressource par son proprietaire

- Type : API
- Priorite : H
- Preconditions : JWT du proprietaire de la ressource
- Etapes :
1. Appeler `PATCH /api/resources/{id}` avec une modification autorisee
- Resultat attendu :
  - HTTP 200
  - ressource modifiee

### TC-API-04 - Patch d'une ressource par un autre utilisateur

- Type : SEC
- Priorite : H
- Preconditions : JWT d'un utilisateur non proprietaire
- Etapes :
1. Appeler `PATCH /api/resources/{id}`
- Resultat attendu :
  - acces refuse

### TC-API-05 - Consultation d'un utilisateur via API

- Type : API
- Priorite : M
- Preconditions : JWT valide
- Etapes :
1. Appeler `GET /api/users/{id}`
- Resultat attendu :
  - HTTP 200
  - donnees selon groupe `user:read`

### TC-API-06 - Changement de mot de passe API

- Type : API
- Priorite : H
- Preconditions : JWT valide
- Etapes :
1. Appeler `POST /api/change-password` avec `current_password` correct et `new_password`
- Resultat attendu :
  - HTTP 200
  - mot de passe modifie

### TC-API-07 - Changement de mot de passe API sans authentification

- Type : SEC
- Priorite : H
- Preconditions : aucun JWT
- Etapes :
1. Appeler `POST /api/change-password`
- Resultat attendu :
  - HTTP 401 ou 403 selon firewall

## 5. Administration

### TC-ADM-01 - Acces admin autorise

- Type : SEC
- Priorite : H
- Preconditions : utilisateur avec `ROLE_ADMIN`
- Etapes :
1. Ouvrir `/admin`
- Resultat attendu :
  - dashboard affiche

### TC-ADM-02 - Acces admin interdit a un utilisateur standard

- Type : SEC
- Priorite : H
- Preconditions : utilisateur sans `ROLE_ADMIN`
- Etapes :
1. Ouvrir `/admin`
- Resultat attendu :
  - acces refuse

### TC-ADM-03 - Presence des CRUD principaux

- Type : REC
- Priorite : M
- Preconditions : admin connecte
- Etapes :
1. Ouvrir les menus User, Comment, Resource, Moderation
- Resultat attendu :
  - ecrans de liste accessibles
  - operations de base disponibles

## 6. Cas de vigilance specifique

Ces points meritent un suivi particulier pendant la campagne :
- tester l'inscription API avec les variantes `firstName` / `firstname`
- verifier la logique de publication d'une ressource lors de l'edition
- verifier les methodes HTTP acceptees sur `/profile/edit`
- verifier l'encodage des messages et caracteres speciaux dans les reponses API
