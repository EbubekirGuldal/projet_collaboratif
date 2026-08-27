# Gestion des tickets — methodologie prestataire / client

Ce document decrit le processus de suivi des demandes entre l'equipe de
developpement (prestataire) et le porteur du projet (client). Il ne decrit pas
un processus theorique : chaque etape ci-dessous correspond a un element
reellement configure dans le depot, dont le chemin est indique.

## 1. Outil retenu

**GitHub Issues**, dans le depot du projet.

Ce choix n'ajoute aucun outil tiers : le code, les tickets, les revues, le
pipeline et la documentation vivent au meme endroit. Un ticket est donc relie
mecaniquement au commit qui le corrige (`Refs #12`), a la pull request qui le
porte et au run de CI qui la valide. Cette continuite est ce qui rend le suivi
verifiable plutot que declaratif.

| Besoin | Element configure |
|---|---|
| Saisie cadree des demandes | [`.github/ISSUE_TEMPLATE/`](../.github/ISSUE_TEMPLATE) — 3 formulaires |
| Interdiction des tickets vides | [`config.yml`](../.github/ISSUE_TEMPLATE/config.yml) — `blank_issues_enabled: false` |
| Classement et filtrage | [`.github/labels.yml`](../.github/labels.yml) — 19 labels, 4 dimensions |
| Cadre de la revue | [`.github/PULL_REQUEST_TEMPLATE.md`](../.github/PULL_REQUEST_TEMPLATE.md) |
| Regles de contribution | [`CONTRIBUTING.md`](../CONTRIBUTING.md) |
| Canal prive pour les failles | [`SECURITY.md`](../SECURITY.md) et avis de securite GitHub |

## 2. Qui ouvre un ticket

Trois origines, toutes traitees dans le meme flux :

| Origine | Canal | Formulaire |
|---|---|---|
| **Client** — anomalie constatee en usage, demande d'evolution | Issue publique | `bug_report.yml` ou `feature_request.yml` |
| **Equipe** — anomalie detectee en developpement, dette technique, veille | Issue publique | formulaire correspondant |
| **Automatismes** — vulnerabilite de dependance | Alerte Dependabot ou echec du job `security` | reprise en `vulnerability.yml` |

Une **faille exploitable** ne passe jamais par une issue publique : la decrire
la divulgue avant correction. Le formulaire `vulnerability.yml` impose une case
a cocher confirmant que la faiblesse est deja publique ou non exploitable, et
`config.yml` renvoie explicitement vers l'avis de securite prive.

## 3. Qualification

Un ticket arrive en `statut:a-qualifier`, pose automatiquement par le
formulaire. La qualification repond a quatre questions :

1. **Est-ce reproductible ?** Sinon, le ticket retourne au demandeur avec la
   question precise qui bloque — pas un « impossible a reproduire » seul.
2. **Quel est le perimetre reel ?** Un ticket qui couvre plusieurs sujets est
   scinde : un ticket, une correction, une pull request.
3. **Quelle gravite ?** Confirmee ou corrigee par rapport a l'estimation du
   demandeur, qui juge depuis son usage et non depuis le code.
4. **Est-ce deja couvert ?** Rapprochement avec les tickets ouverts.

Sortie : `statut:qualifie` et un label `gravite:*`.

## 4. Niveaux de gravite et delais

La **gravite** mesure l'impact sur le service rendu. Elle est distincte de la
**priorite**, qui est une decision d'ordonnancement : une anomalie mineure tres
visible peut passer avant une majeure qui ne touche qu'un cas rare.

| Gravite | Definition | Prise en compte | Contournement ou correction |
|---|---|---|---|
| **Bloquante** | Fonctionnalite inutilisable, aucun contournement. Perte ou exposition de donnees. | 4 h ouvrees | 1 jour ouvre |
| **Majeure** | Fonctionnalite degradee, contournement existant mais couteux. | 1 jour ouvre | 5 jours ouvres |
| **Mineure** | Gene sans impact sur le service rendu. | 5 jours ouvres | Prochaine iteration |

Pour les vulnerabilites, les delais de [`SECURITY.md`](../SECURITY.md) prevalent.

> Ces valeurs sont une **proposition de l'equipe**, pas un engagement
> contractuel constate. Elles doivent etre validees avec le client avant d'etre
> opposables.

<!-- À COMPLÉTER : ces delais ont-ils ete valides avec le porteur du projet ? Existe-t-il un contrat de service ecrit ? Quelles sont les heures ouvrees convenues ? -->

## 5. Priorisation

La priorite est posee par l'equipe au regard de trois criteres : gravite, nombre
d'utilisateurs touches, existence d'un contournement acceptable.

Un point de priorisation reunit l'equipe et le client a intervalle regulier ; il
tranche l'ordre de la file et acte les tickets ecartes.

<!-- À COMPLÉTER : a quelle frequence ce point se tient-il reellement ? Sous quelle forme (reunion, echange ecrit) ? Qui y participe ? -->

## 6. Circuit d'escalade

L'escalade se declenche sur un fait, pas sur une impression :

| Declencheur | Action |
|---|---|
| Une anomalie **bloquante** depasse son delai de correction | Le mainteneur informe le client par ecrit dans le ticket : cause, ce qui bloque, nouvelle echeance. |
| Un ticket reste en `statut:bloque` plus de 5 jours ouvres | Il est remonte au point de priorisation suivant, avec l'element externe attendu et de qui il depend. |
| Une vulnerabilite **critique** est confirmee | Le processus de gestion de crise de [`plan_de_securisation.md`](plan_de_securisation.md) prend le relais : il prime sur le flux de tickets ordinaire. |
| Un desaccord persiste sur le perimetre ou la priorite | Arbitrage par le porteur du projet, trace en commentaire du ticket. |

Regle commune : **toute escalade est ecrite dans le ticket**. Un echange verbal
ou par messagerie qui n'y est pas reporte n'a pas eu lieu du point de vue du
suivi.

## 7. Suivi par le client

Le client suit l'avancement sans avoir a demander d'etat des lieux :

- **Le label `statut:*`** donne la position exacte de chaque ticket dans le
  cycle. Un filtre suffit : `is:issue is:open label:"statut:en-cours"`.
- **Le label `gravite:*`** isole ce qui est bloquant :
  `is:issue is:open label:"gravite:bloquante"`.
- **La notification automatique** : le client est abonne aux tickets qu'il ouvre
  et recoit chaque changement.
- **Le lien ticket / code** : la pull request qui corrige le ticket y est liee
  automatiquement, avec son diff et le resultat du pipeline. Le client constate
  donc *ce qui* a ete change, pas seulement *qu'il* a ete change.
- **La cloture automatique** : `Closes #n` dans la pull request ferme le ticket
  au merge, ce qui garantit qu'aucun ticket corrige ne reste ouvert par oubli.

L'etape de **recette** est explicite : apres le merge, le ticket passe en
`statut:a-recetter` et c'est le demandeur — pas l'equipe — qui confirme la
correction avant fermeture.

<!-- À COMPLÉTER : le client dispose-t-il d'un compte GitHub sur ce depot ? Avec quel niveau d'acces (lecture, triage) ? -->

## 8. Ce qui n'est pas encore en place

Ces elements renforceraient le suivi mais ne sont pas configures a ce jour ; ils
sont cites pour ne pas laisser croire le contraire :

- **Protection de branche sur `master`** — la regle « pas de push direct » est
  ecrite dans `CONTRIBUTING.md` mais n'est pas encore imposee techniquement.
- **Vue projet (GitHub Projects)** — les labels permettent le filtrage, mais il
  n'existe pas de tableau de suivi visuel partage avec le client.
- **Jalons (milestones)** — aucun rattachement des tickets a une version ou a une
  iteration datee.
- **Historique de tickets** — le depot ne comporte pas encore de tickets ouverts
  selon ce processus. Les formulaires et les labels sont en place ; leur usage
  reste a demarrer.

<!-- À COMPLÉTER : ces quatre points sont-ils prevus ? Lesquels avant la soutenance ? -->
