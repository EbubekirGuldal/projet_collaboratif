# Plan de securisation

Analyse des vulnerabilites et des risques de (RE)Sources Relationnelles,
structuree par le Top 10 OWASP 2021.

## Methode

Chaque entree provient d'une **observation dans ce depot** : fichier et ligne
sont cites. Aucune vulnerabilite theorique n'est listee ; aucune exploitation
n'a ete menee, les entrees issues de la seule lecture du code sont signalees
comme telles.

**Criticite = Impact x Probabilite**, chaque facteur note de 1 a 4.

| Note | Impact | Probabilite |
|---|---|---|
| 1 | Negligeable | Improbable |
| 2 | Limite, reversible | Possible sous conditions |
| 3 | Fort — donnees personnelles, compte utilisateur | Realiste sans outil particulier |
| 4 | Majeur — compromission ou fuite massive | Triviale, atteignable sans authentification |

| Criticite | Traitement |
|---|---|
| 12 a 16 | Critique — correction immediate, avant toute autre livraison |
| 8 a 11 | Elevee — correction dans l'iteration en cours |
| 4 a 7 | Moderee — planifiee |
| 1 a 3 | Faible — traitee si l'occasion se presente |

## Synthese

| Ref | OWASP | Objet | Impact | Proba | Criticite | Statut |
|---|---|---|---|---|---|---|
| VUL-01 | A01 | Operations API `User` sans regle de securite | 4 | 3 | **12** | Ouverte |
| VUL-02 | A01/A02 | Courriel des auteurs expose sans authentification | 3 | 4 | **12** | Ouverte |
| VUL-03 | A06 | 37 avis de securite sur les dependances | 4 | 3 | **12** | **Corrigee** |
| VUL-04 | A02 | Secrets presents dans l'historique git | 4 | 2 | **8** | Partielle |
| VUL-05 | A04/A07 | Aucune limitation de debit sur l'authentification | 3 | 3 | **9** | Ouverte |
| VUL-06 | A05 | Message d'exception renvoye au client de l'API | 2 | 4 | **8** | Ouverte |
| VUL-07 | A05 | Televersement d'image sans controle de type ni de taille | 2 | 3 | **6** | Ouverte |
| VUL-08 | A07 | Jeton JWT emis avant verification de l'adresse | 2 | 3 | **6** | Ouverte |
| VUL-09 | A05 | `access_control` ouvre `^/api/resources` en acces public | 3 | 2 | **6** | Preventive |
| VUL-10 | A08 | Actions GitHub non epinglees a un condensat | 3 | 2 | **6** | Attenuee |
| VUL-11 | A02 | Absence de TLS dans l'image | 4 | 1 | **4** | Assumee |
| VUL-12 | A03 | Injection SQL et XSS | 4 | 1 | **4** | Maitrisee |

## Detail des vulnerabilites

### VUL-01 — Operations API `User` sans regle de securite (A01, criticite 12)

- **Actif** : comptes utilisateurs et leurs donnees personnelles.
- **Observation** : `src/Entity/User.php`, attribut `#[ApiResource]`. Les
  operations `Get` et `Patch` sont declarees **sans cle `security:`**, et
  `config/packages/api_platform.yaml` ne definit aucune securite par defaut.
  La seule barriere est `access_control` (`^/api` → `IS_AUTHENTICATED_FULLY`).
- **Vecteur** : tout utilisateur authentifie appelle `GET /api/users/{id}` puis
  `PATCH /api/users/{id}` sur un identifiant qui n'est pas le sien. Le champ
  `email` appartient au groupe `user:update`, donc modifiable.
- **Impact** : lecture des donnees personnelles de n'importe quel compte, et
  **prise de controle d'un compte tiers** — changer son adresse, puis declencher
  une reinitialisation de mot de passe vers l'adresse de l'attaquant.
- **Comparaison interne** : l'entite `Resource` fait correctement le travail
  (`security: "is_granted('ROLE_USER')"` sur `Post`,
  `security: "object.getUser() == user"` sur `Patch`). L'ecart sur `User` est
  une omission, pas un choix de conception.
- **Correction** : ajouter sur les deux operations
  `security: "object == user or is_granted('ROLE_ADMIN')"`.
- **Reserve** : etabli par lecture du code, non par appel reel de l'API. Un test
  fonctionnel doit accompagner la correction.

### VUL-02 — Courriel des auteurs expose sans authentification (A01/A02, criticite 12)

- **Actif** : adresses de courriel de tous les contributeurs.
- **Observation** : chaine de trois faits verifies.
  1. `src/Entity/User.php` — `$email` porte `#[Groups(['user:read', 'user:update', 'resource:read'])]`.
  2. `src/Entity/Resource.php` — `$user` porte `#[Groups(['resource:read'])]`.
  3. `src/Entity/Resource.php` — l'operation `GetCollection` utilise
     `normalizationContext: ['groups' => ['resource:read']]` avec
     `security: "true"`, et `security.yaml` place `^/api/resources` en
     `PUBLIC_ACCESS`.
- **Vecteur** : `GET /api/resources.json`, sans aucune authentification.
- **Impact** : moissonnage des adresses de tous les auteurs de ressources.
  C'est une violation du principe de minimisation du RGPD et une base de donnees
  toute faite pour du hameconnage cible.
- **Correction** : retirer `resource:read` du groupe de `User::$email`. Verifie :
  ni `mobile/App.js` ni les gabarits Twig ne consomment ce champ via l'API — le
  mobile n'utilise `email` que pour le profil de l'utilisateur connecte, et
  `templates/resource/show.html.twig` fait un rendu serveur, insensible aux
  groupes de serialisation. La correction est donc sans effet de bord connu.

### VUL-03 — Avis de securite sur les dependances (A06, criticite 12) — corrigee

- **Observation** : `composer audit` sur le verrou d'origine remontait **37 avis
  affectant 14 paquets** : 1 critique, 8 elevees, 14 moderees, 10 faibles.
- **Les plus directement applicables** :
  - `CVE-2026-46633` (critique, Twig) — injection de code PHP via le nom de
    template d'un `{% use %}` ;
  - `CVE-2026-48489` (elevee, `symfony/security-http`) — contournement du
    pare-feu donnant un acces non authentifie a des routes protegees par
    `access_control` : exactement le mecanisme sur lequel reposent `^/admin` et
    `^/api` ici ;
  - `CVE-2026-45067` (elevee, `symfony/mime`) — injection d'en-tetes SMTP,
    applicable puisque l'application envoie des courriels.
- **Point notable** : les trois avis Symfony affectaient **toute la branche 7.3**.
  Aucun correctif n'existait sans changer de version mineure.
- **Correction appliquee** : montee en Symfony 7.4 et Twig 3.28.
  `composer audit --locked` ne remonte plus aucun avis.
- **Prevention** : job `security` du pipeline (echec sur haute ou critique) et
  Dependabot hebdomadaire sur `composer`, `npm` et `github-actions`.

### VUL-04 — Secrets dans l'historique git (A02, criticite 8) — partielle

- **Observation** : deux secrets ont ete versionnes puis retires.
  - Mot de passe d'application Gmail dans `MAILER_DSN` (`.env`), retire par le
    commit `28dc3f9` ;
  - passphrase JWT de 64 caracteres dans `phpunit.dist.xml`, retiree par la PR #4.
- **Vecteur** : `git show 28dc3f9^:.env` suffit. Le depot est public : toute
  personne l'ayant clone possede ces valeurs.
- **Impact** : envoi de courriels usurpant l'identite du projet ; forge de jetons
  si la passphrase a servi ailleurs.
- **Fait** : les deux valeurs sont hors du code courant ; `.env` est desormais
  ignore par git et le gabarit de pull request impose la verification.
- **Reste du** : **la revocation cote Google et la rotation de la passphrase**.
  Retirer un secret du dernier commit ne le revoque pas. Ces actions ne peuvent
  pas etre faites depuis le depot.

<!-- À COMPLÉTER : le mot de passe d'application Gmail a-t-il ete revoque ? La passphrase JWT a-t-elle servi hors des tests ? -->

### VUL-05 — Aucune limitation de debit sur l'authentification (A04/A07, criticite 9)

- **Observation** : aucun `rate_limiter` dans `config/packages/`, aucun
  `login_throttling` dans `security.yaml`. `AuthApiController::login()` et
  `register()` n'imposent aucun delai.
- **Vecteur** : appels repetes sur `POST /auth/login`.
- **Impact** : attaque par force brute sur les mots de passe ; creation massive
  de comptes via `POST /auth/register`.
- **Correction** : activer `login_throttling` sur le pare-feu `main`, et un
  `rate_limiter` Symfony sur les points d'entree `/auth/*`.

### VUL-06 — Message d'exception renvoye au client (A05, criticite 8)

- **Observation** : `AuthApiController`, blocs `catch (\Throwable $th)` :
  `return new JsonResponse(["status" => 500, "message" => "Erreur : " . $th->getMessage()], 500)`.
- **Vecteur** : provoquer une erreur, par exemple en reutilisant une adresse
  deja enregistree — la contrainte d'unicite sur `email` remonte alors une
  erreur Doctrine.
- **Impact** : divulgation de details internes (requete SQL, noms de tables et de
  colonnes, chemins de fichiers). Utile a un attaquant pour cartographier
  l'application. Secondairement, permet d'**enumerer les comptes existants**.
- **Correction** : journaliser l'exception cote serveur, renvoyer un message
  generique au client.

### VUL-07 — Televersement d'image sans controle (A05, criticite 6)

- **Observation** : `src/Form/ResourceFormType.php` ligne 101 declare
  `imageFile` en `VichImageType` **sans cle `constraints`**. Aucun
  `#[Assert\File]` sur `Resource::$imageFile`.
- **Contraste interne** : `ProfileController::updateProfilePhoto()` fait le
  travail correctement — plafond de 5 Mo et liste blanche de types MIME
  (`image/jpeg`, `image/png`, `image/webp`, `image/gif`). L'ecart est une
  omission sur un seul point d'entree.
- **Impact** : depot de fichiers volumineux (saturation du volume) et de types
  arbitraires. Un SVG televerse serait servi depuis la meme origine, donc
  exploitable en XSS stocke.
- **Attenuation en place** : `docker/nginx.conf` renvoie 404 sur toute URL en
  `.php` hors `index.php`, et `client_max_body_size` est plafonne a 10 Mo. Un
  fichier PHP televerse ne peut donc pas etre execute.
- **Correction** : aligner `ResourceFormType` sur le controleur de photo de
  profil — `Assert\File` avec `maxSize` et `mimeTypes`.

### VUL-08 — Jeton emis avant verification de l'adresse (A07, criticite 6)

- **Observation** : `AuthApiController::register()` appelle
  `$this->jwtManager->create($user)` immediatement apres le `flush()`, sans
  attendre la verification d'adresse, alors que `symfonycasts/verify-email-bundle`
  est installe et que `User::$isVerified` existe.
- **Impact** : compte utilisable avec une adresse non prouvee — usurpation
  d'identite et comptes jetables.
- **Correction** : conditionner l'emission du jeton a `isVerified`, ou restreindre
  les droits d'un compte non verifie.

### VUL-09 — `^/api/resources` en acces public (A05, criticite 6) — preventive

- **Observation** : `security.yaml` declare
  `{ path: ^/api/resources, roles: PUBLIC_ACCESS }` **avant**
  `{ path: ^/api, roles: IS_AUTHENTICATED_FULLY }`. La premiere regle qui
  correspond gagne : toutes les methodes sur `/api/resources` echappent a
  l'exigence d'authentification.
- **Pourquoi ce n'est pas une faille aujourd'hui** : API Platform applique ses
  propres regles par operation — `Post` exige `ROLE_USER`, `Patch` exige
  `object.getUser() == user`.
- **Pourquoi c'est un risque** : la protection repose **entierement** sur ces
  attributs. Une operation ajoutee sans cle `security:` serait immediatement
  accessible en ecriture sans authentification. C'est exactement le defaut de
  VUL-01, qui montre que l'omission se produit reellement dans ce projet.
- **Correction** : restreindre la regle publique aux lectures, ou definir une
  securite par defaut dans `api_platform.yaml`.

### VUL-10 — Actions GitHub non epinglees (A08, criticite 6) — attenuee

- **Observation** : le pipeline reference `actions/checkout@v4`,
  `docker/build-push-action@v6`, etc. — des etiquettes mobiles, pas des
  condensats.
- **Impact** : une etiquette repointee sur du code malveillant s'executerait avec
  les droits du depot, dont le jeton de publication GHCR.
- **Attenuation** : `permissions` est restreint au niveau du workflow
  (`contents: read`, `packages: write`) ; Dependabot suit desormais
  `github-actions`.
- **Correction** : epingler chaque action a son condensat SHA.

### VUL-11 — Absence de TLS dans l'image (A02, criticite 4) — assumee

- **Observation** : `docker/nginx.conf` ecoute en HTTP sur le port 80. Aucun
  certificat dans l'image.
- **Decision** : assumee. La terminaison TLS releve du reverse proxy de l'hote,
  pas de l'image. La probabilite est notee 1 parce qu'**aucun serveur cible n'est
  provisionne** : l'image n'est pas exposee.
- **Condition** : ce point redevient bloquant des la premiere mise en ligne. Voir
  `plan_de_deploiement.md`, section 4.2.

### VUL-12 — Injection SQL et XSS (A03, criticite 4) — maitrisee

Verifie, et conserve ici pour montrer ce qui a ete controle :

- **SQL** : les acces passent par Doctrine ORM. Les rares requetes SQL directes
  (`AdminStatsTestDataCommand`) utilisent des parametres nommes lies, jamais de
  concatenation.
- **XSS** : Twig echappe par defaut ; aucun `|raw` sur une donnee utilisateur.
- **CSRF** : jetons presents sur les formulaires sensibles — mise a jour de
  photo, suppression de compte, signalement, connexion.
- **Mots de passe** : hachage par `password_hashers: auto` (bcrypt/argon2 selon
  la plateforme), jamais de stockage en clair.

**Reserve** : ces controles resultent d'une lecture ciblee, pas d'un audit
exhaustif ni d'un test d'intrusion.

## Actions preventives en place

Ce qui empeche l'apparition de nouvelles vulnerabilites, independamment des
corrections ci-dessus :

| Mesure | Ou | Ce qu'elle empeche |
|---|---|---|
| `composer audit`, echec sur haute ou critique | Job `security` | Livrer une dependance vulnerable |
| `npm audit --audit-level=high` | Job `security` | Idem cote front |
| PHPStan niveau 5, baseline figee | Job `security` | Introduire de nouveaux defauts de typage |
| CodeQL, hebdomadaire et sur PR | `codeql.yml` | Motifs de vulnerabilite dans le code ecrit |
| Dependabot hebdomadaire | `dependabot.yml` | Le vieillissement silencieux des dependances |
| `docker` depend de `security` | `ci.yml` | Publier une image porteuse d'une vulnerabilite connue |
| Secrets ephemeres en CI | Job `deploy-check` | Stocker un secret de test |
| `/.env` ignore, `.env.dist` sans secret | `.gitignore` | Reversionner un secret |
| Point « aucun secret ajoute » | Gabarit de PR | Idem, cote humain |
| Canal prive de signalement | `SECURITY.md` | Divulguer une faille avant correction |

## Ordre de traitement recommande

1. **VUL-01** et **VUL-02** — criticite 12, correction courte, effet immediat sur
   l'exposition de donnees personnelles.
2. **VUL-05** — limitation de debit sur l'authentification.
3. **VUL-04** — revocation et rotation des secrets de l'historique. Independant
   du code, ne demande que des acces.
4. **VUL-06**, **VUL-07**, **VUL-08**, **VUL-09**.
5. **VUL-10**, puis **VUL-11** au moment du provisionnement de la cible.

## Gestion de crise

### Perimetre

Cette procedure s'applique a une **attaque avérée ou fortement soupconnee** :
compromission d'un compte a privileges, fuite de donnees, defiguration,
indisponibilite d'origine malveillante, exploitation d'une vulnerabilite
publiee. Elle **prime sur le flux de tickets ordinaire** decrit dans
`gestion_des_tickets.md`.

### 1. Detection

| Source | Nature |
|---|---|
| `HEALTHCHECK` du conteneur et sonde `/healthz` | Indisponibilite |
| Journaux applicatifs (`docker compose logs`) | Erreurs anormales, pics de 4xx/5xx |
| Alertes Dependabot et CodeQL | Vulnerabilite nouvellement publiee |
| Signalement externe via `SECURITY.md` | Decouverte par un tiers |
| Signalement d'un utilisateur | Comportement anormal, compte detourne |

**Limite a connaitre** : il n'existe **ni supervision externe ni alerte
automatique**. La detection repose aujourd'hui sur une consultation manuelle ou
sur un signalement. C'est le maillon faible de cette procedure.

### 2. Qualification — dans l'heure

Quatre questions, dans cet ordre :

1. L'attaque est-elle **en cours** ou terminee ?
2. Quelles **donnees** sont concernees ? Des donnees personnelles ?
3. Quel est le **vecteur** ? Est-il encore ouvert ?
4. Le service doit-il etre **suspendu** pour arreter l'hemorragie ?

Sortie : un niveau de gravite.

| Niveau | Definition | Delai de reaction |
|---|---|---|
| **Critique** | Donnees personnelles exfiltrees, ou acces administrateur obtenu | Immediat |
| **Elevee** | Compte utilisateur compromis, ou vulnerabilite exploitable en production | 4 h |
| **Moderee** | Tentative echouee, ou vulnerabilite non encore exploitee | 1 jour ouvre |

### 3. Escalade et responsabilites

| Role | Responsabilite pendant la crise |
|---|---|
| **Premier repondant** | Qualifie, ouvre le journal d'incident, alerte le responsable technique |
| **Responsable technique** | Decide de suspendre le service, pilote la correction |
| **Porteur du projet** | Decide de la communication externe, tranche les arbitrages |
| **Referent donnees personnelles** | Evalue l'obligation de notification a la CNIL |

<!-- À COMPLÉTER : qui tient chacun de ces roles ? Quel est le canal d'alerte hors horaires, et le numero ou l'adresse a joindre ? Sans nom ni canal, cette procedure ne se declenche pas. -->

### 4. Endiguement

Par ordre de recours :

1. **Revoquer les secrets** exposes : `APP_SECRET`, `JWT_PASSPHRASE`, mots de
   passe de base, `MAILER_DSN`. La rotation de `JWT_PASSPHRASE` invalide **tous
   les jetons emis**, ce qui deconnecte tout le monde : c'est l'effet recherche
   quand un jeton a pu etre forge.
2. **Suspendre le service** si l'attaque est en cours :
   `docker compose stop app`. La base reste intacte pour l'analyse.
3. **Revenir a une version saine** selon `plan_de_deploiement.md`, section 6.
4. **Preserver les preuves** avant tout redemarrage : copier les journaux du
   conteneur et faire une capture de la base. Un redemarrage detruit les journaux
   en memoire.

### 5. Correction

- Corriger la cause, **pas le symptome**, sur une branche `fix/`.
- Le pipeline complet doit etre vert, job `security` compris.
- **Ajouter un test de non-regression** qui echoue sur le code vulnerable.
- Passer par une pull request, meme en urgence : la revue est ce qui empeche une
  correction hative d'ouvrir une autre breche.

### 6. Communication

| Destinataire | Quand | Contenu |
|---|---|---|
| Equipe | Immediatement | Faits etablis, perimetre, qui fait quoi |
| Porteur du projet | Des la qualification | Nature, impact estime, mesures prises |
| Utilisateurs affectes | Apres endiguement | Ce qui s'est passe, donnees concernees, ce qu'ils doivent faire |
| CNIL | **Sous 72 h** si des donnees personnelles sont concernees | Notification de violation, article 33 du RGPD |

Regle : ne rien communiquer d'incertain. Un fait etabli et date vaut mieux
qu'une hypothese rassurante dementie le lendemain.

### 7. Retour a la normale

1. Verifier que le vecteur est ferme — rejouer le scenario d'attaque.
2. Redemarrer et controler `/healthz`, les journaux et les acces.
3. Forcer la reinitialisation des mots de passe des comptes concernes.
4. Surveiller de facon rapprochee pendant 72 h.

### 8. Post-mortem — sous 5 jours ouvres

Ecrit, verse au depot, **sans recherche de responsable individuel** : un
post-mortem qui cherche un coupable produit de la dissimulation, pas de la
securite.

Contenu : chronologie datee ; cause racine ; ce qui a permis la detection ; ce
qui l'a retardee ; mesures correctives avec un ticket par mesure ; mise a jour de
ce document.
