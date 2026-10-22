#!/bin/bash
set -euo pipefail

# Bootstrap manual de imagenes :dev antes del primer stack Portainer.
# Construye php y nginx con el mismo naming que el pipeline CI.

REGISTRY=dr.epc-app.com
PROJECT_NAMESPACE=yrojas
PROJECT_NAME=nido
VERSION=$(date +"%Y.%m.%d-%H.%M")

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}"

build_service() {
    local service=$1
    local dockerfile=$2
    local image="${REGISTRY}/${PROJECT_NAMESPACE}/${PROJECT_NAME}-${service}"

    echo "=== Build ${image} ==="
    docker build -f "${dockerfile}" \
        -t "${PROJECT_NAME}-${service}" \
        -t "${image}:${VERSION}" \
        -t "${image}:dev" \
        .
}

build_service php docker/php/Dockerfile.prod
build_service nginx docker/nginx/Dockerfile

read -t 600 -p "¿Subir imagenes al registry? (S/N) [N]: " response
response=${response:-N}

if [[ "${response^^}" == "S" ]]; then
    docker login "${REGISTRY}"
    for service in php nginx; do
        image="${REGISTRY}/${PROJECT_NAMESPACE}/${PROJECT_NAME}-${service}"
        docker push "${image}:${VERSION}"
        docker push "${image}:dev"
    done
    echo "Push completado."
else
    echo "Imagenes construidas localmente; no se subieron al registry."
fi
