# Déploiement Prerender.io (token via PRERENDER_TOKEN dans .env ou environnement)

deploy_prerender_nginx() {
  local app_dir="$1"
  local token="${PRERENDER_TOKEN:-}"

  if [ -z "$token" ] && [ -f "$app_dir/.env" ]; then
    token=$(grep -E '^PRERENDER_TOKEN=' "$app_dir/.env" | cut -d= -f2- | tr -d '\r')
  fi

  if [ -z "$token" ] && [ -f "$HOME/earth-forum/.env" ]; then
    token=$(grep -E '^PRERENDER_TOKEN=' "$HOME/earth-forum/.env" | cut -d= -f2- | tr -d '\r')
  fi

  sudo cp "$app_dir/deploy/nginx-native/prerender-map.conf" /etc/nginx/conf.d/prerender-map.conf

  if [ -n "$token" ]; then
    sudo tee /etc/nginx/snippets/prerender-proxy.conf > /dev/null << EOF
proxy_set_header X-Prerender-Token ${token};
proxy_set_header X-Prerender-Int-Type nginx;
proxy_set_header Host \$host;
proxy_pass https://service.prerender.io/https://\$host\$request_uri\$is_args\$args;
proxy_ssl_server_name on;
proxy_redirect off;
EOF
    echo "Prerender.io activé"
  else
    sudo tee /etc/nginx/snippets/prerender-proxy.conf > /dev/null << 'EOF'
# PRERENDER_TOKEN non défini — proxy désactivé (fallback SPA)
return 404;
EOF
    echo "Prerender.io: token absent, snippet neutre"
  fi
}
