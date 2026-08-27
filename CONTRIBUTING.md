# Contribuer au projet

Ce document decrit les regles de travail sur le depot. Il s'applique a tous les
contributeurs, membres de l'equipe comme intervenants ponctuels.

## 1. Regle fondamentale : pas de push direct sur `master`

`master` est la branche de reference. Elle ne recoit de code que par
**pull request fusionnee**, jamais par un `git push` direct.

Cette regle a une raison mecanique : le pipeline `.github/workflows/ci.yml` ne
peut proteger que ce qu'il voit avant le merge. Une modification poussee
directement sur `master` est deja integree quand la CI la constate — le fait
s'est deja produit sur ce depot et a laisse la branche de reference cassee.

> **A configurer sur GitHub** — Settings > Branches > Add rule sur `master` :
> exiger une pull request, exiger que les verifications `build`, `tests`,
> `deploy-check` et `docker` soient au vert, interdire le push direct.
> <!-- À COMPLÉTER : cette protection de branche a-t-elle ete activee ? Par qui, a quelle date ? -->

## 2. Nommage des branches

Une branche par sujet, prefixee par sa nature, en minuscules avec des tirets.
Quand la branche traite un ticket, son numero est rappele en fin de nom.

| Prefixe | Usage | Exemple |
|---|---|---|
| `feat/` | nouvelle fonctionnalite | `feat/moderation-file-attente-42` |
| `fix/` | correction d'anomalie | `fix/register-firstname-17` |
| `docs/` | documentation seule | `docs/plan-de-securisation` |
| `chore/` | outillage, dependances, menage | `chore/hygiene` |
| `refactor/` | reecriture sans changement de comportement | `refactor/resource-repository` |

Une branche reste courte : plus elle vit, plus elle diverge et plus la revue
devient couteuse.

## 3. Messages de commit

Format [Conventional Commits](https://www.conventionalcommits.org/) :

```
<type>(<perimetre>): <resume a l'imperatif, sans majuscule initiale, sans point final>

<corps facultatif : pourquoi ce changement, pas ce qu'il fait — le diff le dit deja>

Refs #<numero de ticket>
```

Types utilises : `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `ci`.

```
fix(api): aligner le champ firstName sur l'entite User

Le point d'entree d'inscription lisait firstname alors que l'entite expose
firstName : le prenom arrivait vide en base sans qu'aucune erreur ne remonte.

Refs #17
```

Ce que ne doit pas etre un message de commit : `.`, `fix`, `update`, `wip`.
L'historique de ce depot en contient — c'est precisement ce qu'il faut cesser,
car un tel message rend impossible de comprendre une regression a posteriori.

## 4. Cycle de vie d'un ticket

```
Ouverture -> Qualification -> Priorisation -> Developpement -> Revue -> Merge -> Recette -> Cloture
```

| Etape | Qui | Ce qui se passe | Label de sortie |
|---|---|---|---|
| **Ouverture** | Demandeur (client, membre de l'equipe, utilisateur) | Un formulaire de `.github/ISSUE_TEMPLATE` est rempli. Les champs obligatoires garantissent que le ticket est instruisable. | `statut:a-qualifier` |
| **Qualification** | Mainteneur | Le ticket est reproduit, son perimetre precise, sa gravite confirmee. Un ticket non reproductible est renvoye a son auteur avec la question precise qui bloque. | `statut:qualifie` + `gravite:*` |
| **Priorisation** | Equipe | La priorite est posee au regard de la gravite, du nombre d'utilisateurs touches et de l'existence d'un contournement. Gravite et priorite sont distinctes. | `priorite:*` |
| **Developpement** | Developpeur assigne | Branche creee depuis `master` a jour, ticket assigne. | `statut:en-cours` |
| **Revue** | Relecteur (un autre membre) | Pull request ouverte avec le gabarit rempli. Le pipeline doit etre vert. Au moins une approbation. | `statut:en-revue` |
| **Merge** | Relecteur | Fusion dans `master`. Le pipeline de `master` doit rester vert. | `statut:a-recetter` |
| **Recette** | Demandeur | Le demandeur constate la correction et le confirme dans le ticket. | — |
| **Cloture** | Mainteneur | Le ticket est ferme, en general automatiquement par `Closes #n`. | fermeture |

Un ticket qui ne peut pas avancer passe en `statut:bloque`, avec un commentaire
indiquant ce qui est attendu et de qui. Un ticket ecarte passe en
`statut:refuse` avec la raison ecrite — jamais ferme sans explication.

## 5. Roles

| Role | Responsabilite |
|---|---|
| **Demandeur** | Ouvre le ticket, fournit les elements de reproduction, valide la correction en recette |
| **Mainteneur** | Qualifie, priorise, arbitre le perimetre, ferme les tickets |
| **Developpeur** | Realise la correction ou l'evolution, ecrit les tests, ouvre la pull request |
| **Relecteur** | Verifie le code, la couverture de test et l'absence de secret, approuve et fusionne |

Un contributeur peut porter plusieurs roles, mais **le relecteur d'une pull
request n'est jamais son auteur**.

<!-- À COMPLÉTER : qui tient quel role dans l'equipe (Ebubekir, Baptiste, Wylson) ? Y a-t-il une rotation ? -->

## 6. Labels

Le jeu de labels est decrit dans [`.github/labels.yml`](.github/labels.yml), avec
sa procedure d'application. Quatre dimensions independantes : `type:`,
`priorite:`, `gravite:`, `statut:`. Un ticket porte au plus un label par
dimension.

## 7. Avant d'ouvrir une pull request

- Le pipeline passe : `build`, `tests`, `deploy-check`, `docker`.
- Une anomalie corrigee est accompagnee d'un **test de non-regression** : un test
  qui echoue sur le code d'avant et passe sur celui d'apres.
- Une evolution du schema est accompagnee d'une **migration**, dont le `down()`
  a ete relu.
- **Aucun secret** n'est ajoute, meme a titre d'exemple, meme en environnement de
  test. Les valeurs par defaut partagees vivent dans `.env.dist` et n'y sont que
  des placeholders ; les vraies valeurs vivent dans `.env.local`, ignore par git.

## 8. Mise en place de l'environnement

Voir le [`README.md`](README.md) pour l'installation locale et
[`docs/conteneurisation.md`](docs/conteneurisation.md) pour l'execution en
conteneur.
