# Politique de securite

## Versions supportees

Le projet n'a pas encore de versions publiees ni de tags. Une seule ligne de
code est maintenue : la branche `master`, dont chaque commit est valide par le
pipeline d'integration continue et publie sous forme d'image conteneur
`ghcr.io/ebubekirguldal/projet_collaboratif:latest`.

| Version | Supportee |
|---|---|
| `master` (image `:latest`) | Oui |
| Toute autre branche | Non |

Les correctifs de securite sont appliques sur `master`. Il n'existe pas de
retroportage : il n'y a pas de branche de maintenance vers laquelle retroporter.

<!-- À COMPLÉTER : une strategie de tags de version est decrite dans docs/plan_de_deploiement.md mais n'est pas encore appliquee. Une fois les premiers tags poses, ce tableau doit lister les versions reellement supportees. -->

## Signaler une vulnerabilite

### Faille exploitable — canal prive obligatoire

**N'ouvrez pas d'issue publique.** Une issue est visible de tous : y decrire une
faille exploitable revient a la divulguer avant qu'elle ne soit corrigee.

Utilisez le formulaire prive de GitHub :
**Security > Advisories > Report a vulnerability**, ou directement
<https://github.com/EbubekirGuldal/projet_collaboratif/security/advisories/new>.

Le signalement reste visible des seuls mainteneurs jusqu'a publication.

### Faiblesse deja publique ou non exploitable

Un avis de securite sur une dependance, ou un defaut de durcissement sans risque
d'exploitation immediate, peut etre suivi par une issue publique via le
formulaire [`vulnerability.yml`](.github/ISSUE_TEMPLATE/vulnerability.yml).

### Ce qu'un signalement utile contient

- le composant touche, avec sa version ;
- le vecteur : point d'entree, profil requis, pre-requis ;
- les etapes de reproduction ;
- l'impact constate — ce a quoi un attaquant accede, pas seulement le symptome ;
- si vous en avez une, la piste de correction.

Merci de ne pas mener de test destructif, de ne pas exfiltrer de donnee reelle
et de ne pas degrader le service pour demontrer une faille.

## Delais annonces

| Etape | Delai vise |
|---|---|
| Accuse de reception | 3 jours ouvres |
| Qualification et retour sur la criticite | 7 jours ouvres |
| Correctif pour une vulnerabilite critique ou elevee | 30 jours |
| Correctif pour une vulnerabilite moderee ou faible | Prochaine iteration |
| Publication de l'avis | Apres deploiement du correctif |

> Ces delais sont un engagement de l'equipe projet, dans un cadre pedagogique.
> Ils ne constituent pas un contrat de service.
>
> <!-- À COMPLÉTER : qui recoit et qualifie les signalements ? Une adresse de contact dediee est-elle prevue ? -->

## Ce qui est automatise dans le depot

La securite ne repose pas seulement sur les signalements exterieurs :

| Automatisme | Ce qu'il couvre | Ou |
|---|---|---|
| `composer audit` | Vulnerabilites connues des dependances PHP | Job `security` du pipeline |
| `npm audit` | Vulnerabilites connues des dependances front | Job `security` du pipeline |
| PHPStan | Defauts de typage et chemins d'erreur dans `src/` | Job `security` du pipeline |
| PHP_CodeSniffer | Ecarts aux conventions PSR-12 | Job `security` du pipeline |
| CodeQL | Motifs de vulnerabilite dans le front, le mobile et les workflows | [`codeql.yml`](.github/workflows/codeql.yml) |
| Dependabot | Propositions de mise a jour hebdomadaires | [`dependabot.yml`](.github/dependabot.yml) |

Le pipeline echoue sur toute vulnerabilite **haute ou critique**. Les severites
moderees et faibles sont rapportees sans bloquer, pour ne pas immobiliser la
livraison sur des avis sans impact exploitable dans ce contexte.

## Bonnes pratiques imposees par le depot

- **Aucun secret versionne.** `.env` est ignore par git ; les valeurs partagees
  vivent dans `.env.dist` et n'y sont que des placeholders. Le gabarit de pull
  request impose de le verifier.
- **Secrets ephemeres en CI.** Le job `deploy-check` tire ses mots de passe au
  hasard a chaque execution (`openssl rand`) : aucun secret de test n'est stocke.
- **Cles JWT hors depot.** `config/jwt/*.pem` est ignore par git ; les cles sont
  generees au premier demarrage du conteneur.

## Incidents connus

Deux secrets ont ete versionnes puis retires. **Ils restent presents dans
l'historique git** : les retirer du dernier commit ne les revoque pas.

| Secret | Retire par | Action requise |
|---|---|---|
| Mot de passe d'application Gmail (`MAILER_DSN`) | commit `28dc3f9` | **Revocation cote Google** |
| Passphrase JWT (`phpunit.dist.xml`) | PR #4 | **Rotation** si elle a servi ailleurs |

<!-- À COMPLÉTER : ces deux rotations ont-elles ete effectuees ? Par qui, a quelle date ? -->

## Gestion de crise

La procedure a suivre en cas d'attaque avérée — detection, qualification,
escalade, communication, retour a la normale, post-mortem — est decrite dans
[`docs/plan_de_securisation.md`](docs/plan_de_securisation.md).
