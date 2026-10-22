# Cargar variables de entorno desde el .env
ifneq (,$(wildcard ./.env))
    include .env
    export
endif

PHPUNIT = ./vendor/bin/phpunit --testdox --colors=always
COMPOSE_PROD = docker compose -f docker-compose.yaml -f docker-compose.prod.yaml

.PHONY: dev-up dev-down dev-down-hard dev-watch dev-logs-vue prod-up prod-down prod-build logs status clean

.PHONY: help check pre-push install-hooks test test-unit test-gitops test-host test-filter test-docker test-docker-filter phpstan php-cs-fixer-dry-run php-cs-fixer-fix rector rector-dry-run frontend-build composer-install npm-install db-migrate db-import-json db-create-user db-assign-user-group

help:
	@echo "Desarrollo: make dev-up, make dev-watch, make dev-down"
	@echo "Calidad: make check — verificaciones previas a subir la rama"
	@echo "Git: make install-hooks — activar hook pre-push"
	@echo "Tests (local = CI):"
	@echo "  make test        — todas las suites tests/*/run.sh"
	@echo "  make test-unit   — tests/unit/run.sh (PHPUnit en Docker, gate antes de push)"
	@echo "  make test-gitops — tests/gitops-prerequisites/run.sh"
	@echo "  make test-docker — PHPUnit con stack levantado (docker compose exec)"
	@echo "  make test-host   — PHPUnit en el host (legacy)"
	@echo "  make phpstan     — análisis estático de PHP"
	@echo "  make php-cs-fixer-dry-run — revisar formato PHP sin cambios"
	@echo "  make php-cs-fixer-fix — aplicar formato PHP"
	@echo "  make rector      — aplicar refactorizaciones Rector"
	@echo "  make rector-dry-run — revisar cambios Rector sin aplicarlos"

check: php-cs-fixer-dry-run phpstan rector-dry-run test-docker frontend-build
	@echo "✅ Todas las verificaciones han pasado. La rama está lista para revisar o subir."

pre-push: check

install-hooks:
	@chmod +x .githooks/pre-push
	@git config core.hooksPath .githooks
	@echo "✅ Hook pre-push activado en .githooks"
# ==========================================
# 🛠️ ENTORNO DE DESARROLLO (DEV)
# ==========================================

# Levanta el entorno de desarrollo (PHP + Nginx + Node con recarga rápida)
dev-up:
	@echo "🚀 Levantando entorno de DESARROLLO para $(PROJECT_NAME)..."
	docker compose --profile dev up -d
	@echo "💡 Tip: usa 'make dev-watch' para ver logs de Vite con hot reload."

# Dev con hot reload: el volumen ./frontend + polling de Vite recargan al guardar.
# (Docker Compose Watch no aplica: choca con el bind mount.)
dev-watch:
	@echo "👀 Hot reload activo en frontend/ (solo dev). Ctrl+C cierra logs; contenedores siguen arriba."
	@docker compose --profile dev up -d
	docker compose logs -f node

# Logs en vivo del contenedor Vue/Vite
dev-logs-vue:
	docker compose logs -f node

# Apaga el entorno de desarrollo
dev-down:
	@echo "🛑 Apagando entorno de DESARROLLO..."
	docker compose --profile dev down

dev-down-hard:
	@echo "🛑 Apagando entorno de DESARROLLO..."
	docker compose --profile dev down --remove-orphans
dev-rebuild:
	@echo "🔄 Recompilando y actualizando entorno de DESARROLLO..."
	docker compose up -d --build
# ==========================================
# 🚀 ENTORNO DE PRODUCCIÓN (PROD)
# ==========================================

# Compila el frontend localmente y levanta producción (PHP + Nginx estático)
prod-up:
	@echo "📦 Compilando Frontend de Vue para producción..."
	docker compose run --rm node sh -c "npm install && npm run build"
	@echo "🚀 Levantando entorno de PRODUCCIÓN..."
	$(COMPOSE_PROD) up -d

# Apaga el entorno de producción
prod-down:
	@echo "🛑 Apagando entorno de PRODUCCIÓN..."
	$(COMPOSE_PROD) down

# Recompila el frontend y reinicia contenedores de producción si cambiaste código
prod-build:
	@echo "🔄 Recompilando y actualizando producción..."
	docker compose run --rm node sh -c "npm install && npm run build"
	$(COMPOSE_PROD) restart nginx

# ==========================================
# 📊 COMANDOS DE CONTROL
# ==========================================

# Ver logs en tiempo real
logs:
	docker compose logs -f

# Ver el estado de los contenedores
status:
	docker compose ps

# Limpieza profunda de Docker (Borra todo lo que esté colgado)
clean:
	@echo "🧹 Limpieza profunda de contenedores y volúmenes huerfanos..."
	docker compose --profile dev down --volumes --remove-orphans

test:
	@found=0; \
	for suite in tests/*/run.sh; do \
		[ -f "$$suite" ] || continue; \
		found=1; \
		echo "==> Ejecutando $$suite"; \
		bash "$$suite"; \
	done; \
	if [ "$$found" -eq 0 ]; then echo "ERROR: no hay tests/*/run.sh" >&2; exit 1; fi

test-unit:
	bash ./tests/unit/run.sh

test-gitops:
	bash ./tests/gitops-prerequisites/run.sh

test-host:
	cd backend && $(PHPUNIT)

test-filter:
	cd backend && $(PHPUNIT) --filter "$(t)"

test-docker:
	docker compose exec php $(PHPUNIT)

test-docker-filter:
	@echo "🧪 Ejecutando tests de PHPUnit con filtro..."
	docker compose exec php $(PHPUNIT) --filter "$(t)"

phpstan:
	@echo "🔎 Ejecutando PHPStan..."
	docker compose exec php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M

php-cs-fixer-dry-run:
	@echo "🎨 Revisando formato PHP..."
	docker compose exec php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --diff

php-cs-fixer:
	@echo "🎨 Aplicando formato PHP..."
	docker compose exec php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php

rector:
	@echo "🔧 Aplicando refactorizaciones Rector..."
	docker compose exec php vendor/bin/rector process --config=rector.php

rector-dry-run:
	@echo "🔍 Revisando cambios Rector sin aplicarlos..."
	docker compose exec php vendor/bin/rector process --dry-run --config=rector.php

frontend-build:
	@echo "📦 Validando build del frontend..."
	docker compose exec node npm run build

composer-install:
	@echo "📦 Instalando dependencias PHP..."
	docker compose exec php composer install --no-dev --optimize-autoloader --no-interaction

npm-install:
	@echo "📦 Instalando dependencias del frontend..."
	docker compose exec node npm install
