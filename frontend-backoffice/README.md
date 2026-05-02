# Earth Forum - Backoffice

Frontend React pour l'administration d'Earth Forum, utilisant Ant Design comme bibliothèque UI.

## Technologies

- React 19+
- React Router DOM
- Ant Design 5.x
- Axios
- CSS Modules

## Structure du projet

```
frontend-backoffice/
├── public/              # Fichiers statiques
├── src/
│   ├── components/     # Composants réutilisables
│   │   └── Layout/     # Layout principal avec sidebar
│   ├── contexts/       # Contexts React (AuthContext)
│   ├── pages/          # Pages principales
│   │   ├── Login/      # Page de connexion
│   │   ├── Dashboard/  # Tableau de bord
│   │   ├── Users/      # Gestion des utilisateurs
│   │   └── Forums/     # Gestion des forums
│   ├── services/       # Services API (Axios)
│   ├── styles/         # Styles globaux
│   ├── App.jsx         # Composant racine
│   └── index.js        # Point d'entrée
├── Dockerfile          # Configuration Docker
├── nginx.conf          # Configuration Nginx
└── package.json        # Dépendances
```

## Installation

```bash
cd frontend-backoffice
npm install
```

## Développement

```bash
npm start
```

L'application sera accessible sur `http://localhost:3000`

## Variables d'environnement

Créer un fichier `.env.local` à la racine du dossier `frontend-backoffice` :

```
REACT_APP_API_URL=http://localhost:8050
```

## Build de production

```bash
npm run build
```

Les fichiers compilés seront dans le dossier `build/`.

## Docker

Le backoffice est intégré au docker-compose principal et sera accessible sur `http://localhost:3004`

## Fonctionnalités

- Authentification avec JWT
- Tableau de bord avec statistiques
- Gestion des utilisateurs
- Gestion des forums
- Interface responsive avec Ant Design

