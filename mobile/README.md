# Prototype mobile

## Objectif

Ce dossier fournit un prototype mobile-first minimal pour couvrir l attente "application mobile" du sujet.

## Fonctionnalites

- consultation du catalogue via `GET /api/resources.json`
- connexion via `POST /auth/login`
- affichage du profil connecte
- structure mobile simple en trois vues: fil, connexion, aide

## Lancement

```bash
cd mobile
npm install
npm run start
```

## Prerequis

- serveur Symfony lance sur `http://127.0.0.1:8000`
- API web disponible

## Limite volontaire

Le prototype reste minimal pour la soutenance: il couvre le parcours principal sans embarquer toute la logique du back-office.
