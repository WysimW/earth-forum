# Fonctions partagées pour préserver les clés JWT Lexik lors des rsync --delete

RSYNC_JWT_EXCLUDE=(--exclude 'backend/config/jwt/*.pem')

load_jwt_passphrase() {
  local backend_dir="$1"
  JWT_PASSPHRASE=$(grep -E '^JWT_PASSPHRASE=' "$backend_dir/.env" | cut -d= -f2- | tr -d '\r')
  export JWT_PASSPHRASE
}

preserve_jwt_keys() {
  local app_dir="$1"
  JWT_BACKUP_DIR=$(mktemp -d)
  if [ -f "$app_dir/backend/config/jwt/private.pem" ]; then
    cp -a "$app_dir/backend/config/jwt/"*.pem "$JWT_BACKUP_DIR/"
    echo "JWT keys sauvegardées"
  fi
}

restore_jwt_keys() {
  local app_dir="$1"
  if [ -n "${JWT_BACKUP_DIR:-}" ] && [ -f "$JWT_BACKUP_DIR/private.pem" ]; then
    mkdir -p "$app_dir/backend/config/jwt"
    cp -a "$JWT_BACKUP_DIR/"*.pem "$app_dir/backend/config/jwt/"
    echo "JWT keys restaurées"
  fi
  rm -rf "${JWT_BACKUP_DIR:-}"
}

jwt_keys_valid() {
  local app_dir="$1"
  sudo -u www-data APP_ENV=prod php "$app_dir/backend/bin/console" lexik:jwt:check-config -q 2>/dev/null
}

ensure_jwt_keys() {
  local app_dir="$1"
  cd "$app_dir/backend"
  mkdir -p config/jwt
  load_jwt_passphrase "$PWD"

  if [ -f config/jwt/private.pem ] && jwt_keys_valid "$app_dir"; then
    echo "JWT keys existantes valides"
  else
    echo "JWT keys absentes ou invalides — régénération avec JWT_PASSPHRASE du .env"
    rm -f config/jwt/private.pem config/jwt/public.pem
    APP_ENV=prod php bin/console lexik:jwt:generate-keypair --no-interaction
  fi

  # admin = déploiement, www-data = PHP-FPM : les deux doivent lire la clé privée
  sudo chown admin:www-data config/jwt/*.pem
  sudo chmod 640 config/jwt/private.pem
  sudo chmod 644 config/jwt/public.pem

  if ! jwt_keys_valid "$app_dir"; then
    echo "ERREUR: configuration JWT invalide après régénération" >&2
    exit 1
  fi
  echo "JWT OK"
}

warmup_prod_cache() {
  local app_dir="$1"
  sudo chown -R admin:www-data "$app_dir/backend/var"
  sudo chmod -R g+w "$app_dir/backend/var"
  sudo rm -rf "$app_dir/backend/var/cache/prod/"*
  sudo -u www-data APP_ENV=prod php "$app_dir/backend/bin/console" cache:warmup --no-debug
}
