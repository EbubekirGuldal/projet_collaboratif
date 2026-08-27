# Conformite RGPD

Traitement des donnees personnelles dans (RE)Sources Relationnelles.

Ce document decrit **ce que fait reellement l'application**, verifie dans le
code. Les manques sont signales comme tels : un document de conformite qui
enjolive empeche de voir ce qui reste a faire.

## 1. Donnees collectees et finalites

Etablies a partir de `src/Entity/User.php` et des entites liees.

### 1.1 Identification et compte

| Donnee | Champ | Finalite | Obligatoire |
|---|---|---|---|
| Adresse de courriel | `User::$email` | Identifiant de connexion, verification d'adresse, reinitialisation | Oui |
| Mot de passe hache | `User::$password` | Authentification | Oui |
| Nom d'utilisateur | `User::$username` | Affichage public | Oui |
| Nom | `User::$lastName` | Personnalisation du profil | Non |
| Prenom | `User::$firstName` | Personnalisation du profil | Non |
| Photo de profil | `User::$picture` | Affichage du profil | Non |
| Roles | `User::$roles` | Controle d'acces | Attribue par le systeme |
| Etat de verification | `User::$isVerified` | Validation de l'adresse | Systeme |
| Etat d'activation | `User::$isActive` | Suspension par la moderation | Systeme |

### 1.2 Usage

| Donnee | Ou | Finalite |
|---|---|---|
| Dates de creation et de mise a jour | `User::$createdAt`, `$updatedAt` | Tracabilite du compte |
| Derniere connexion | `User::$lastConnexion` | Detection des comptes inactifs |
| Ressources publiees | `Resource` | Objet meme du service |
| Commentaires | `Comment` | Interaction entre membres |
| Favoris, mentions d'interet, partages | `user_favorites`, `resource_like`, `user_liked`, `share` | Personnalisation |
| Etat de lecture | `user_resource_state` | Suivi de progression |
| Actions de moderation | `ModerationLog` | Tracabilite des decisions |
| Demandes de reinitialisation | `ResetPasswordRequest` | Securite du mot de passe |

### 1.3 Donnees non collectees

Ni donnee de sante, ni opinion politique, religieuse ou syndicale, ni donnee
biometrique, ni geolocalisation : **aucune categorie particuliere au sens de
l'article 9**. Aucun traceur publicitaire, aucun outil de mesure d'audience
tiers.

## 2. Bases legales

| Traitement | Base legale | Justification |
|---|---|---|
| Gestion du compte | Execution du contrat (art. 6.1.b) | Le compte est necessaire au service |
| Publication de ressources et commentaires | Execution du contrat (art. 6.1.b) | Objet du service, a l'initiative de l'utilisateur |
| Verification d'adresse et reinitialisation | Interet legitime (art. 6.1.f) | Securite des comptes |
| Journal de moderation | Interet legitime (art. 6.1.f) | Tracabilite, protection des utilisateurs |
| Photo de profil | Consentement (art. 6.1.a) | Facultative, deposee par l'utilisateur |

<!-- À COMPLÉTER : ces bases legales ont-elles ete validees par le porteur du projet ? Le service releve-t-il d'une mission d'interet public, ce qui changerait la base legale de plusieurs traitements ? -->

## 3. Durees de conservation

**Aucune duree n'est appliquee techniquement a ce jour.** Il n'existe ni tache
planifiee de purge, ni commande de nettoyage, ni champ d'expiration hors
`ResetPasswordRequest::$expiresAt`.

| Donnee | Duree proposee | Etat |
|---|---|---|
| Compte actif | Duree d'usage du service | Applique de fait |
| Compte inactif | 3 ans apres `lastConnexion`, suppression apres relance | **Non applique** |
| Ressources et commentaires | Jusqu'a suppression par l'auteur ou du compte | Applique |
| Journal de moderation | 1 an apres l'action | **Non applique** |
| Demandes de reinitialisation | Expiration courte via `expiresAt` | Applique par le bundle |

`User::$lastConnexion` existe deja : la purge des comptes inactifs est
outillable sans changement de schema.

<!-- À COMPLÉTER : quelles durees le porteur du projet retient-il ? Une commande de purge planifiee est-elle prevue avant la mise en service ? -->

## 4. Droits des personnes

| Droit | Etat | Comment |
|---|---|---|
| **Information** (art. 13) | Partiel | Pages `/mentions-legales`, `/politique-confidentialite`, `/donnees-personnelles` |
| **Acces** (art. 15) | Partiel | Donnees visibles sur le profil ; **aucun export structure** |
| **Rectification** (art. 16) | En place | `/profile/edit` et `PATCH /api/users/{id}` |
| **Effacement** (art. 17) | En place, avec reserve | `/profile/delete` — voir 4.1 |
| **Portabilite** (art. 20) | **Absent** | Aucun export machine |
| **Opposition** (art. 21) | Sans objet | Ni prospection ni profilage |
| **Limitation** (art. 18) | Partiel | `isActive` permet de suspendre, sans procedure formalisee |

### 4.1 Effacement — ce que fait reellement le code

`ProfileController::deleteAccount()` (ligne 251) :

1. verifie le jeton CSRF ;
2. **anonymise** les ressources publiees (`$resource->setUser(null)`) : le
   contenu reste en ligne, sans auteur ;
3. **supprime** les commentaires ;
4. vide les favoris ;
5. supprime le compte, invalide la session et le jeton de securite.

C'est une mise en oeuvre serieuse : l'anonymisation preserve le contenu
collectif tout en supprimant le lien avec la personne.

**Reserve identifiee.** La methode ne traite ni `resource_like`, ni
`user_liked`, ni `moderation_log`, ni `share`, ni `user_resource_state`. Or la
migration initiale declare `user_id INT NOT NULL` avec contrainte de cle
etrangere sur ces tables. La suppression d'un compte ayant aime, partage ou
modere une ressource **echouerait sur une violation de contrainte**, et le droit
a l'effacement ne serait pas rendu effectif pour ces utilisateurs.

- **Etabli par** : lecture croisee du controleur et de la migration.
- **Non verifie par execution** : aucun test ne couvre ce cas.
- **Correction attendue** : traiter ces relations dans `deleteAccount()`, puis
  ajouter un test supprimant un compte ayant aime, partage et modere.

<!-- À COMPLÉTER : ce scenario a-t-il ete constate en base ? Un test de suppression de compte complet doit etre ajoute pour trancher. -->

### 4.2 Portabilite — absente

Aucun export au format structure et lisible par machine. Une demande ne pourrait
etre honoree que manuellement, par requete en base.

**Correction proposee** : une commande `app:export-user-data` produisant un JSON
des donnees d'un compte, et un bouton de telechargement sur le profil.

## 5. Mesures techniques en place

| Mesure | Ou |
|---|---|
| Mots de passe haches (`auto` : bcrypt ou argon2) | `config/packages/security.yaml` |
| Verification de l'adresse | `symfonycasts/verify-email-bundle` |
| Reinitialisation par jeton a duree limitee | `symfonycasts/reset-password-bundle` |
| Jetons CSRF sur les operations sensibles | Profil, suppression, signalement, connexion |
| Cloisonnement par roles | `role_hierarchy` et `access_control` |
| Suspension d'un compte | `User::$isActive`, verifie par `ApiUserChecker` |
| Controle du televersement de photo de profil | Plafond 5 Mo, liste blanche de types MIME |
| Aucun secret dans le depot | `.gitignore`, `.env.dist`, gabarit de PR |
| Suivi des vulnerabilites | Job `security`, CodeQL, Dependabot |

## 6. Ecarts ouverts

| # | Ecart | Consequence |
|---|---|---|
| 1 | **Courriels des auteurs exposes sans authentification** (VUL-02) | Violation directe du principe de minimisation. Correction identifiee, sans effet de bord connu. |
| 2 | **Modification possible d'un compte tiers** (VUL-01) | Atteinte a l'integrite des donnees personnelles. |
| 3 | **Effacement incomplet** (4.1) | Le droit de l'article 17 peut echouer techniquement. |
| 4 | **Aucun export** (4.2) | Droit de l'article 20 non couvert. |
| 5 | **Aucune duree de conservation appliquee** (3) | Conservation illimitee de fait. |
| 6 | **Aucun registre des traitements** | Obligation de l'article 30. |
| 7 | **Absence de TLS** (VUL-11) | Bloquant des la mise en ligne : donnees en clair sur le reseau. |

Les points 1, 2 et 7 sont detailles dans
[`plan_de_securisation.md`](plan_de_securisation.md).

<!-- À COMPLÉTER : un registre des traitements existe-t-il hors du depot ? Un responsable de traitement, et le cas echeant un delegue a la protection des donnees, sont-ils designes ? Leurs coordonnees doivent figurer dans les pages legales. -->

## 7. Violation de donnees

En cas de fuite, la procedure de gestion de crise de
[`plan_de_securisation.md`](plan_de_securisation.md) s'applique. Point specifique
au RGPD : **notification a la CNIL sous 72 h** (article 33) des lors que des
donnees personnelles sont concernees, et information des personnes affectees
lorsque le risque est eleve (article 34).

## 8. Pages legales

`LegalController` expose trois pages, rendues depuis `templates/legal/` :

| Route | Gabarit |
|---|---|
| `/mentions-legales` | `legal_notice.html.twig` |
| `/politique-confidentialite` | `privacy_policy.html.twig` |
| `/donnees-personnelles` | `personal_data.html.twig` |

<!-- À COMPLÉTER : le contenu de ces trois pages doit etre relu au regard de ce document. En particulier, la politique de confidentialite doit annoncer les durees de conservation reellement appliquees, et non des durees theoriques. -->
