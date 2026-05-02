#!/bin/bash

# Script pour corriger les permissions du cache ESLint
# À exécuter avec: sudo ./fix-cache-permissions.sh

FRONTEND_DIR="/home/wysim/projets/earth-forum/frontend"
CACHE_DIR="$FRONTEND_DIR/node_modules/.cache"

echo "Correction des permissions du cache..."

# Changer le propriétaire du répertoire cache
if [ -d "$CACHE_DIR" ]; then
    chown -R wysim:wysim "$CACHE_DIR"
    chmod -R 755 "$CACHE_DIR"
    echo "✓ Permissions corrigées pour $CACHE_DIR"
else
    echo "Le répertoire cache n'existe pas encore."
fi

# S'assurer que le répertoire parent a les bonnes permissions
chown wysim:wysim "$FRONTEND_DIR/node_modules"
chmod 755 "$FRONTEND_DIR/node_modules"

echo "✓ Permissions corrigées avec succès !"
echo ""
echo "Vous pouvez maintenant relancer: npm start"
