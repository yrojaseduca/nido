#!/bin/sh
set -e

cd /app

# El volumen anónimo de node_modules puede quedar desincronizado tras dev-down.
if [ ! -d node_modules/vue ] || [ ! -f node_modules/.dev-deps-ready ] \
    || [ package.json -nt node_modules/.dev-deps-ready ] \
    || { [ -f package-lock.json ] && [ package-lock.json -nt node_modules/.dev-deps-ready ]; }; then
    echo "[vue-dev] Instalando dependencias del frontend..."
    npm install
    touch node_modules/.dev-deps-ready
fi

echo "[vue-dev] Iniciando Vite con hot reload (solo desarrollo)..."
exec npm run dev -- --host
