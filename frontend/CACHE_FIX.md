# Solution définitive pour les problèmes de cache ESLint

## Problème
Le cache ESLint (`node_modules/.cache/.eslintcache`) peut avoir des permissions incorrectes (appartient à `root`), ce qui cause l'erreur :
```
EACCES: permission denied, open 'node_modules/.cache/.eslintcache'
```

## Solutions

### Solution 1 : Utiliser le script sécurisé (Recommandé)
```bash
npm run start:safe
```
Ce script gère automatiquement les permissions avant de démarrer.

### Solution 2 : Corriger les permissions une fois
```bash
npm run fix-cache
```
Puis utilisez normalement :
```bash
npm start
```

### Solution 3 : Correction manuelle
```bash
sudo chown -R $(whoami):$(whoami) node_modules/.cache
sudo chmod -R 755 node_modules/.cache
```

## Prévention
Le script `prestart` supprime automatiquement le cache avant chaque démarrage pour éviter les problèmes. Si vous rencontrez encore des erreurs, utilisez `npm run start:safe`.
