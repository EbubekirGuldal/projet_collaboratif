# Veille technologique

Ce document decrit **la methode** de veille de l'equipe et son outillage. Les
exemples concrets sont a fournir par l'equipe : ils sont marques
`<!-- À COMPLÉTER -->` et ne doivent pas etre inventes — une veille inventee ne
resiste pas a la premiere question posee en soutenance.

## 1. Pourquoi une veille sur ce projet

Le besoin n'est pas theorique, il est documente dans ce depot :

- `composer audit` a remonte **37 avis de securite affectant 14 paquets**, dont
  1 critique et 8 elevees, sur des dependances qui n'avaient jamais ete auditees ;
- trois de ces avis n'etaient corriges dans **aucune version de la branche 7.3**
  utilisee : il fallait suivre le cycle de vie des versions de Symfony pour le
  savoir ;
- la montee en Symfony 7.4 a revele une rupture de comportement d'AssetMapper
  qui rendait le rendu des pages impossible — un point signale dans les notes de
  version, pas dans le code.

Une veille absente ne se voit pas jusqu'au jour ou elle coute une remise en
etat en urgence.

## 2. Perimetre

| Axe | Ce qui est suivi | Pourquoi |
|---|---|---|
| **Securite** | Avis sur PHP, Symfony, Twig, Doctrine, API Platform, dependances npm | Un avis critique impose une action sous 30 jours (`SECURITY.md`) |
| **Versions** | Cycle de vie de PHP et de Symfony, dates de fin de support | Une branche non supportee ne recoit plus de correctif de securite |
| **Ecosysteme** | Notes de version des bundles utilises | Anticiper les ruptures de comportement |
| **Outillage** | GitHub Actions, Docker, images de base | Depreciations d'environnement d'execution |
| **Reglementaire** | Recommandations CNIL, RGPD | Le projet traite des donnees personnelles |

## 3. Sources suivies

| Source | Type | Frequence |
|---|---|---|
| [Symfony Blog](https://symfony.com/blog/) | Notes de version, avis de securite | Hebdomadaire |
| [Symfony Releases](https://symfony.com/releases) | Calendrier de fin de support | Mensuelle |
| [PHP.net — versions supportees](https://www.php.net/supported-versions.php) | Cycle de vie de PHP | Trimestrielle |
| [GitHub Advisory Database](https://github.com/advisories) | Avis de securite, source de `composer audit` | Continue, via l'outillage |
| [FriendsOfPHP/security-advisories](https://github.com/FriendsOfPHP/security-advisories) | Avis specifiques a l'ecosysteme PHP | Continue, via l'outillage |
| [Changelog GitHub Actions](https://github.blog/changelog/) | Depreciations de l'environnement d'execution | Mensuelle |
| [CNIL — actualites](https://www.cnil.fr/fr/actualites) | Recommandations, sanctions | Mensuelle |
| [OWASP Top 10](https://owasp.org/www-project-top-ten/) | Reference de l'analyse de risques | A chaque revision majeure |

<!-- À COMPLÉTER : l'equipe suit-elle d'autres sources (newsletters, comptes, podcasts, forums) ? Les lister ici avec leur frequence reelle de consultation. -->

## 4. Outillage : la veille automatisee d'abord

L'essentiel de la veille de securite ne repose pas sur une lecture manuelle mais
sur des automatismes deja en place dans le depot. C'est ce qui la rend fiable :
elle ne depend pas de la disponibilite d'une personne.

| Outil | Ce qu'il surveille | Ou | Ce qu'il produit |
|---|---|---|---|
| **Dependabot** | Nouvelles versions composer, npm, actions | `.github/dependabot.yml` | Une pull request par mise a jour, chaque lundi |
| **`composer audit`** | Avis sur les dependances PHP | Job `security` | Echec du pipeline sur haute ou critique |
| **`npm audit`** | Avis sur les dependances front | Job `security` | Idem |
| **CodeQL** | Motifs de vulnerabilite | `.github/workflows/codeql.yml` | Alertes hebdomadaires dans Security |
| **PHPStan** | Defauts de typage nouveaux | Job `security` | Echec du pipeline |

Une PR Dependabot arrive deja etiquetee `type:dette-technique` et
`statut:a-qualifier` : elle entre donc directement dans le flux de tickets, sans
etape manuelle de creation.

## 5. La veille manuelle : ce que l'automatisme ne voit pas

Trois choses echappent aux outils, et justifient une revue humaine :

1. **Les fins de support.** Aucun outil n'alerte parce qu'une branche cessera
   d'etre maintenue dans six mois. C'est pourtant ce qui a rendu impossible la
   correction de trois CVE sans changer de version mineure.
2. **Les ruptures de comportement.** Une montee de version peut passer les tests
   et casser un usage non couvert — comme la rupture AssetMapper rencontree ici.
   Les notes de version le disent ; le pipeline, non.
3. **Le contexte reglementaire.** Une recommandation CNIL ne declenche aucune
   alerte automatique.

### Rythme propose

| Frequence | Qui | Quoi |
|---|---|---|
| **Hebdomadaire** | Chacun | Revue des PR Dependabot et des alertes de securite |
| **Mensuelle** | Un membre, par rotation | Revue des notes de version et des fins de support ; compte rendu ecrit |
| **Trimestrielle** | Equipe | Revue des risques : mise a jour de `plan_de_securisation.md` |

<!-- À COMPLÉTER : ce rythme est-il celui reellement pratique ? Une rotation est-elle en place, et depuis quand ? -->

## 6. De la veille au correctif

Une veille qui ne produit pas de changement dans le depot n'est pas une veille.
Le circuit est le meme que pour toute autre demande, decrit dans
[`gestion_des_tickets.md`](gestion_des_tickets.md).

```
Signal          ->  Ticket           ->  Qualification  ->  Branche       ->  PR             ->  Merge
(avis, note,        formulaire           gravite et         fix/ ou           pipeline vert      trace dans
 alerte, PR         d'issue ou PR        priorite           chore/            + revue            l'historique
 Dependabot)        Dependabot
```

### Regles

- **Un signal, un ticket.** Un avis de securite lu et non transforme en ticket
  est perdu. Le formulaire `vulnerability.yml` sert a cela.
- **Une vulnerabilite exploitable ne passe pas par une issue publique** : avis de
  securite prive, selon `SECURITY.md`.
- **La gravite vient de l'exposition reelle**, pas de la note du CVE. Un avis
  critique sur un composant non utilise dans ce projet n'est pas critique ici ;
  un avis moyen sur le chemin d'authentification l'est davantage.
- **Toute correction est accompagnee d'un test** quand elle porte sur le
  comportement, ou de la preuve du pipeline quand elle porte sur une dependance.

### Exemple vecu

<!-- À COMPLÉTER : decrire un cas reellement vecu par l'equipe, avec ses dates. Trame : quelle source a donne le signal, a quelle date ; comment il a ete qualifie ; quel ticket a ete ouvert ; quelle correction a ete apportee ; quel delai entre le signal et le merge. Le cas des 37 avis de securite corriges le 27/08/2026 peut servir de premier exemple, si l'equipe s'approprie la demarche. -->

## 7. Tracabilite

La veille laisse une trace verifiable dans le depot lui-meme, sans document
separe a tenir a jour :

- l'historique des PR Dependabot fusionnees ;
- les tickets `type:securite` et `type:dette-technique` ;
- les revisions de `plan_de_securisation.md` ;
- les comptes rendus de revue mensuelle.

<!-- À COMPLÉTER : ou sont consignes les comptes rendus de revue mensuelle ? Les verser dans docs/ les rend consultables par le jury et par le client. -->
