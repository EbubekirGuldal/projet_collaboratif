# Architecture

## Stack

- Web front/back: Symfony 7, Twig, EasyAdmin, Webpack Encore, Bootstrap
- API: API Platform + endpoints JWT custom
- Base de donnees: MySQL
- Uploads: VichUploader
- Tests: PHPUnit
- Mobile prototype: Expo / React Native

## MVC

- Model: entites Doctrine dans `src/Entity`
- View: templates Twig dans `templates`
- Controller: controleurs HTTP dans `src/Controller`

## Acteurs

- Citoyen non connecte
- Citoyen connecte et verifie
- Moderateur
- Administrateur catalogue
- Super administrateur

## Regles metier

- Ressource `private`: visible par son auteur et les equipes de moderation
- Ressource `shared`: visible par les utilisateurs connectes et les equipes de moderation
- Ressource `public`: visible par tous
- Ressource `under_review`: visible par son auteur et les equipes de moderation
- Commentaire citoyen: cree en `pending`, publie apres moderation

## Diagramme de classes simplifie

```mermaid
classDiagram
    User "1" --> "*" Resource : cree
    User "1" --> "*" Comment : ecrit
    User "1" --> "*" ModerationLog : declare
    User "*" --> "*" Resource : favorites
    Resource "1" --> "*" Comment : contient
    Resource "*" --> "1" Category : classee
    Resource "*" --> "1" RessourceType : type
    Resource "*" --> "1" RelationKind : public
    Resource "1" --> "*" ModerationLog : signalee
    User "1" --> "*" UserResourceState : suit
    Resource "1" --> "*" UserResourceState : suivi
```

## MCD simplifie

```mermaid
erDiagram
    USER ||--o{ RESOURCE : creates
    USER ||--o{ COMMENT : writes
    USER ||--o{ MODERATION_LOG : files
    USER ||--o{ USER_RESOURCE_STATE : tracks
    RESOURCE ||--o{ COMMENT : has
    RESOURCE ||--o{ MODERATION_LOG : receives
    RESOURCE ||--o{ USER_RESOURCE_STATE : tracked_by
    RESOURCE }o--|| CATEGORY : belongs_to
    RESOURCE }o--|| RESSOURCE_TYPE : has_type
    RESOURCE }o--|| RELATION_KIND : targets
```

## API pour le mobile

- `POST /auth/login`
- `POST /auth/register`
- `POST /api/verify-token`
- `GET /api/resources.json`
- `GET /api/resources/{id}.json`

## Accessibilite et qualite

- interface responsive
- pages legales
- aide utilisateur
- verification email avant publication et interactions
- separation des droits moderation / catalogue / gouvernance
