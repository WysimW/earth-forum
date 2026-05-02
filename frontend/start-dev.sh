#!/bin/bash

# Script wrapper pour démarrer le serveur de développement
# Gère automatiquement les problèmes de permissions du cache ESLint

FRONTEND_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CACHE_DIR="$FRONTEND_DIR/node_modules/.cache"
ESLINT_CACHE="$CACHE_DIR/.eslintcache"

# Fonction pour corriger les permissions du cache
fix_cache_permissions() {
    echo "🔧 Vérification des permissions du cache ESLint..."
    
    # Supprimer le fichier de cache s'il existe (peu importe les permissions)
    if [ -f "$ESLINT_CACHE" ]; then
        echo "  Suppression du cache ESLint existant..."
        rm -f "$ESLINT_CACHE" 2>/dev/null || sudo rm -f "$ESLINT_CACHE" 2>/dev/null || true
    fi
    
    # S'assurer que le répertoire cache existe et a les bonnes permissions
    if [ ! -d "$CACHE_DIR" ]; then
        echo "  Création du répertoire cache..."
        mkdir -p "$CACHE_DIR" 2>/dev/null || sudo mkdir -p "$CACHE_DIR" 2>/dev/null || true
    fi
    
    # Corriger les permissions si nécessaire
    if [ -d "$CACHE_DIR" ] && [ ! -w "$CACHE_DIR" ]; then
        echo "  Correction des permissions du répertoire cache..."
        sudo chown -R "$(whoami):$(whoami)" "$CACHE_DIR" 2>/dev/null || true
        sudo chmod -R 755 "$CACHE_DIR" 2>/dev/null || true
    fi
    
    echo "✓ Cache ESLint prêt"
}

# Corriger les permissions avant de démarrer
fix_cache_permissions

# Démarrer le serveur de développement
echo ""
echo "🚀 Démarrage du serveur de développement..."
ESLINT_NO_DEV_ERRORS=true react-scripts start
