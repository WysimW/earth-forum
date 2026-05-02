# Earth Forum - Frontend React

Frontend React 19+ pour Earth Forum, une application de forum de roleplay.

## Technologies

- React 19+
- React Router DOM
- Axios
- CSS Modules
- Variables CSS centralisées

## Structure du projet

```
frontend/
├── public/              # Fichiers statiques
├── src/
│   ├── components/     # Composants réutilisables
│   ├── contexts/       # Contexts React (AuthContext, etc.)
│   ├── hooks/          # Hooks personnalisés
│   ├── pages/          # Pages principales
│   ├── services/       # Services API (Axios)
│   ├── styles/         # Styles globaux et variables CSS
│   ├── App.jsx         # Composant racine
│   └── index.js        # Point d'entrée
├── Dockerfile          # Configuration Docker
├── nginx.conf          # Configuration Nginx
└── package.json        # Dépendances
```

## Installation

```bash
cd frontend
npm install
```

## Développement

```bash
npm start
```

L'application sera accessible sur `http://localhost:3000`

## Variables d'environnement

Créer un fichier `.env` à la racine du dossier `frontend` :

```
REACT_APP_API_URL=http://localhost:8000
```

## Build de production

```bash
npm run build
```

Les fichiers compilés seront dans le dossier `build/`.

## Docker

### Build de l'image

```bash
docker build -t earth-forum-frontend .
```

### Exécution

```bash
docker run -p 80:80 earth-forum-frontend
```

## Règles de style

- Toutes les variables CSS sont définies dans `src/styles/variables.css`
- Chaque composant utilise son propre fichier `.module.css`
- Pas de `box-shadow` dans les styles
- Pas de `transform: translate()` sur les états hover
- Utiliser les variables CSS pour toutes les valeurs (couleurs, espacements, etc.)

## API Backend

Le frontend communique avec le backend Symfony via les endpoints suivants :

- `POST /api/auth/login` - Connexion
- `POST /api/auth/register` - Inscription
- `POST /api/auth/refresh` - Rafraîchir le token
- `GET /api/auth/me` - Utilisateur actuel
- `GET /api/categories` - Liste des catégories
- `GET /api/forums/:id` - Détail d'un forum
- `GET /api/threads/:id` - Détail d'un thread
- Etc.

