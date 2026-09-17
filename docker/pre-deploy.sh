#!/bin/sh
#
# Comando de pré-deploy do SIGEA (Railway).
#
# Existe como script porque o preDeployCommand do Railway é executado em forma
# exec, sem shell — operadores como `&&` chegariam como argumento literal ao
# artisan. E `migrate --seed` não serve: o Laravel só dispara o seeder quando
# alguma migration efetivamente roda, de modo que deploys sem migrations
# pendentes ficariam sem semear.
#
# Os seeders usam findOrCreate/firstOrCreate, portanto são idempotentes e podem
# ser executados em todo deploy sem duplicar registros.
#
set -e

cd /app

echo "[pre-deploy] aplicando migrations"
php artisan migrate --force

echo "[pre-deploy] executando seeders"
php artisan db:seed --force

echo "[pre-deploy] concluído"
