#!/bin/bash
set -euo pipefail

APP_DIR=/var/www/earth-forum
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/jwt-keys.sh
source "$SCRIPT_DIR/lib/jwt-keys.sh"
# shellcheck source=lib/nginx-prerender.sh
source "$SCRIPT_DIR/lib/nginx-prerender.sh"
DB_NAME="${MYSQL_DATABASE:-app}"
DB_USER="${MYSQL_USER:-app}"
DB_PASS="${MYSQL_PASSWORD:-EarthForum2026!}"
DB_ROOT="${MYSQL_ROOT_PASSWORD:-RootEarthForum2026!}"

echo "=== Arrêt Docker et libération disque ==="
if command -v docker &>/dev/null; then
  sudo docker compose -f "$HOME/earth-forum/docker-compose.prod.yml" down -v 2>/dev/null || true
  sudo docker system prune -af --volumes 2>/dev/null || true
  sudo apt-get remove -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin 2>/dev/null || true
fi
sudo apt-get autoremove -y -qq
sudo apt-get clean
df -h /

echo "=== Paquets système ==="
sudo apt-get update -qq
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
  nginx mariadb-server \
  php-fpm php-cli php-mysql php-xml php-mbstring php-intl php-gd php-zip php-curl php-apcu \
  certbot python3-certbot-nginx \
  rsync unzip git curl ca-certificates gnupg

echo "=== Node.js 20 ==="
if ! command -v node &>/dev/null || [[ $(node -v) != v20* ]]; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
  sudo apt-get install -y -qq nodejs
fi
node -v

if ! command -v composer &>/dev/null; then
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
fi

PHP_FPM_SOCK=$(ls /run/php/php*-fpm.sock | head -1)
echo "PHP-FPM socket: $PHP_FPM_SOCK"

echo "=== MariaDB ==="
sudo systemctl enable --now mariadb
sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || \
  sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS'; GRANT ALL ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null || \
  sudo mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS'; GRANT ALL ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"

echo "=== Répertoire application ==="
sudo mkdir -p "$APP_DIR"
preserve_jwt_keys "$APP_DIR"
sudo rsync -a --delete \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude 'var/cache' \
  --exclude 'var/log' \
  --exclude '.git' \
  "${RSYNC_JWT_EXCLUDE[@]}" \
  "$HOME/earth-forum/" "$APP_DIR/"
restore_jwt_keys "$APP_DIR"
sudo chown -R admin:www-data "$APP_DIR"
sudo chmod -R g+w "$APP_DIR/backend/var"

echo "=== Backend .env.local ==="
AWS_REG="${AWS_REGION:-eu-west-3}"
AWS_BUCKET="${AWS_S3_BUCKET:-comicsforum-bucket}"
AWS_ACL="${AWS_S3_ACL:-private}"
# Prod EC2 : pas de clés statiques — le rôle IAM de l'instance fournit les credentials
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
REACT_APP_API_URL=https://api.comics-earth.fr REACT_APP_BACKOFFICE_URL=https://backoffice.comics-earth.fr npm run build

echo "=== Build frontend-backoffice ==="
cd "$APP_DIR/frontend-backoffice"
npm ci --prefer-offline --no-audit
REACT_APP_API_URL=https://api.comics-earth.fr npm run build

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

echo "=== Nginx ==="
PHP_FPM_SOCK=$(ls /run/php/php*-fpm.sock | head -1)
deploy_prerender_nginx "$APP_DIR"
sudo cp "$APP_DIR/deploy/nginx-native/comics-earth.fr.conf" /etc/nginx/sites-available/comics-earth.fr
sudo cp "$APP_DIR/deploy/nginx-native/backoffice.comics-earth.fr.conf" /etc/nginx/sites-available/backoffice.comics-earth.fr
sudo cp "$APP_DIR/deploy/nginx-native/api.comics-earth.fr.conf" /etc/nginx/sites-available/api.comics-earth.fr
sudo sed -i "s|unix:/run/php/php8.4-fpm.sock|unix:$PHP_FPM_SOCK|" /etc/nginx/sites-available/api.comics-earth.fr
sudo ln -sf /etc/nginx/sites-available/comics-earth.fr /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/backoffice.comics-earth.fr /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/api.comics-earth.fr /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl enable --now nginx mariadb
PHP_FPM_SERVICE=$(systemctl list-units --type=service --all 'php*-fpm.service' --no-legend | awk '{print $1}' | head -1)
sudo systemctl enable --now "$PHP_FPM_SERVICE"
sudo systemctl reload nginx

echo "=== SSL ==="
if sudo certbot certificates 2>/dev/null | grep -q "Certificate Name: comics-earth.fr"; then
  sudo certbot install --cert-name comics-earth.fr --nginx --redirect --non-interactive \
    || echo "Certbot install: échec — relancer certbot manuellement"
else
  sudo certbot --nginx \
    -d comics-earth.fr \
    -d www.comics-earth.fr \
    -d backoffice.comics-earth.fr \
    -d api.comics-earth.fr \
    --expand \
    --non-interactive \
    --agree-tos \
    --register-unsafely-without-email \
    --redirect || echo "Certbot: ouvrir ports 80/443 dans AWS Security Group puis relancer certbot"
fi

echo "=== Terminé ==="
curl -s -o /dev/null -w "frontend: %{http_code}\n" -H "Host: comics-earth.fr" http://127.0.0.1/
curl -s -o /dev/null -w "backoffice: %{http_code}\n" -H "Host: backoffice.comics-earth.fr" http://127.0.0.1/
curl -s -o /dev/null -w "api: %{http_code}\n" -H "Host: api.comics-earth.fr" http://127.0.0.1/
