#!/bin/bash
# Mise à jour applicative (sans réinstallation système)
set -euo pipefail

APP_DIR=/var/www/earth-forum
SRC_DIR="${HOME}/earth-forum"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/jwt-keys.sh
source "$SCRIPT_DIR/lib/jwt-keys.sh"
# shellcheck source=lib/nginx-prerender.sh
source "$SCRIPT_DIR/lib/nginx-prerender.sh"
DB_NAME="${MYSQL_DATABASE:-app}"
DB_USER="${MYSQL_USER:-app}"
DB_PASS="${MYSQL_PASSWORD:-EarthForum2026!}"

echo "=== Sync code vers $APP_DIR ==="
sudo mkdir -p "$APP_DIR"
preserve_jwt_keys "$APP_DIR"
sudo rsync -a --delete \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude 'var/cache' \
  --exclude 'var/log' \
  --exclude 'var/forumactif' \
  --exclude '.git' \
  --exclude 'frontend/.env.local' \
  --exclude 'frontend/.env.development.local' \
  --exclude 'frontend/.env.production.local' \
  --exclude 'frontend-backoffice/.env.local' \
  --exclude 'frontend-backoffice/.env.development.local' \
  --exclude 'frontend-backoffice/.env.production.local' \
  "${RSYNC_JWT_EXCLUDE[@]}" \
  "$SRC_DIR/" "$APP_DIR/"
restore_jwt_keys "$APP_DIR"
sudo chown -R admin:www-data "$APP_DIR"
sudo chmod -R g+w "$APP_DIR/backend/var"

echo "=== Backend .env.local ==="
AWS_REG="${AWS_REGION:-eu-west-3}"
AWS_BUCKET="${AWS_S3_BUCKET:-comicsforum-bucket}"
AWS_ACL="${AWS_S3_ACL:-private}"
# Prod EC2 : credentials via rôle IAM instance (pas de clés dans .env.local)
cat > "$APP_DIR/backend/.env.local" << EOF
APP_ENV=prod
DATABASE_URL="mysql://${DB_USER}:${DB_PASS}@127.0.0.1:3306/${DB_NAME}?serverVersion=mariadb-11.4.5&charset=utf8mb4"
CORS_ALLOW_ORIGIN='^https?://(comics-earth\.fr|www\.comics-earth\.fr|backoffice\.comics-earth\.fr|api\.comics-earth\.fr|localhost)(:[0-9]+)?$'
FRONTEND_BASE_URL=https://comics-earth.fr
AWS_REGION=${AWS_REG}
AWS_S3_BUCKET=${AWS_BUCKET}
AWS_S3_ACL=${AWS_ACL}
API_PUBLIC_URL=https://api.comics-earth.fr
MAILER_DSN=null://null
EOF

echo "=== Build frontend ==="
cd "$APP_DIR/frontend"
npm ci --prefer-offline --no-audit
REACT_APP_API_URL=https://api.comics-earth.fr \
REACT_APP_BACKOFFICE_URL=https://backoffice.comics-earth.fr \
  npm run build
rm -rf node_modules

echo "=== Build frontend-backoffice ==="
cd "$APP_DIR/frontend-backoffice"
npm ci --prefer-offline --no-audit
REACT_APP_API_URL=https://api.comics-earth.fr \
REACT_APP_FRONTEND_URL=https://comics-earth.fr \
  npm run build
rm -rf node_modules

echo "=== Backend Symfony ==="
cd "$APP_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction
sudo chown -R admin:www-data var
sudo chmod -R g+w var

echo "=== JWT keys ==="
ensure_jwt_keys "$APP_DIR"

APP_ENV=prod php bin/console cache:clear --no-warmup
APP_ENV=prod php bin/console doctrine:schema:update --force
APP_ENV=prod php bin/console doctrine:migrations:sync-metadata-storage
APP_ENV=prod php bin/console doctrine:migrations:version --add --all --no-interaction 2>/dev/null || true
warmup_prod_cache "$APP_DIR"

PHP_FPM_SOCK=$(ls /run/php/php*-fpm.sock | head -1)
deploy_prerender_nginx "$APP_DIR"
sudo cp "$APP_DIR/deploy/nginx-native/comics-earth.fr.conf" /etc/nginx/sites-available/comics-earth.fr
sudo cp "$APP_DIR/deploy/nginx-native/backoffice.comics-earth.fr.conf" /etc/nginx/sites-available/backoffice.comics-earth.fr
sudo cp "$APP_DIR/deploy/nginx-native/api.comics-earth.fr.conf" /etc/nginx/sites-available/api.comics-earth.fr
sudo sed -i "s|unix:/run/php/php8.4-fpm.sock|unix:$PHP_FPM_SOCK|" /etc/nginx/sites-available/api.comics-earth.fr
sudo nginx -t
PHP_FPM_SERVICE=$(systemctl list-units --type=service --all 'php*-fpm.service' --no-legend | awk '{print $1}' | head -1)
sudo systemctl reload "$PHP_FPM_SERVICE"
sudo systemctl reload nginx

echo "=== Terminé ==="
curl -s -o /dev/null -w "frontend: %{http_code}\n" -H "Host: comics-earth.fr" http://127.0.0.1/
curl -s -o /dev/null -w "backoffice: %{http_code}\n" -H "Host: backoffice.comics-earth.fr" http://127.0.0.1/
curl -s -o /dev/null -w "api: %{http_code}\n" -H "Host: api.comics-earth.fr" http://127.0.0.1/
