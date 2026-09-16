#!/bin/sh
#
# Entrypoint do SIGEA em produção.
# Executa em todos os serviços (web, worker e cron) antes do comando principal.
#
set -e

: "${PORT:=8080}"
export PORT

echo "[sigea] preparando container (PORT=${PORT})"

# ── nginx: injeta a porta atribuída pelo Railway ───────────────────────
if [ -f /etc/nginx/http.d/default.conf.template ]; then
    envsubst '${PORT}' \
        < /etc/nginx/http.d/default.conf.template \
        > /etc/nginx/http.d/default.conf
fi

# ── storage: o volume persistente monta VAZIO sobre /app/storage, ──────
#    então a árvore de diretórios do Laravel precisa ser recriada.
mkdir -p \
    /app/storage/app/privado \
    /app/storage/app/public \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

# ── caches de produção (variáveis já disponíveis em runtime) ───────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link público do storage (ignora erro se já existir no volume)
php artisan storage:link 2>/dev/null || true

chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R ug+rwX /app/storage /app/bootstrap/cache

echo "[sigea] container pronto — iniciando: $*"

exec "$@"
