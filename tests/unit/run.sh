#!/usr/bin/env bash
# PHPUnit del backend (CI job test_unit y make test-unit). Sin MariaDB.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

echo "==> PHPUnit (backend)"

BACKEND_DIR="${ROOT}/backend"
PHP_WORKDIR="/app"
if command -v uname >/dev/null 2>&1; then
    case "$(uname -s)" in
        MINGW* | MSYS* | CYGWIN*)
            export MSYS_NO_PATHCONV=1
            if BACKEND_WIN="$(cd "${BACKEND_DIR}" && pwd -W 2>/dev/null)"; then
                BACKEND_DIR="${BACKEND_WIN}"
            fi
            PHP_WORKDIR="//app"
            ;;
    esac
fi

docker run --rm \
    -v "${BACKEND_DIR}:${PHP_WORKDIR}" \
    -w "${PHP_WORKDIR}" \
    php:8.2.18-cli-bookworm \
    bash -ec '
        apt-get update -qq
        apt-get install -y -qq --no-install-recommends git unzip curl \
            libzip-dev libsqlite3-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev
        docker-php-ext-configure gd --with-freetype --with-jpeg
        docker-php-ext-install -j"$(nproc)" zip pdo pdo_sqlite gd
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        composer install --no-interaction
        ./vendor/bin/phpunit --testdox --colors=always
    '

echo "Todas las comprobaciones unitarias (PHPUnit) pasaron."
