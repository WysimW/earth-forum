#!/bin/bash
set -e

# Installer les dépendances Composer si vendor est vide
if [ ! -d "/var/www/html/vendor" ] || [ -z "$(ls -A /var/www/html/vendor)" ]; then
    echo "Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Exécuter la commande passée en argument
exec "$@"






