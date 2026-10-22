# AGENTS.md

## Proyecto

## Reglas Generales

- Mantener 4 espacios de indentacion en PHP, Vue, JavaScript y CSS.
- No inventar comandos de framework que no existan en este repo. Este proyecto no usa Laravel ni Artisan.
- Antes de proponer o ejecutar comandos, revisar el `Makefile` de la raiz.
- Mantener los cambios acotados al modulo afectado.
- No hacer refactors amplios si no son necesarios para la tarea.
- No tocar configuracion de Docker, Nginx, PHP o Node fuera de `docker/` salvo que la tarea lo pida explicitamente.
- Preservar cambios existentes del usuario en el working tree.

## Comandos del Proyecto

- Levantar desarrollo: `make dev-up`
- Hot reload frontend: `make dev-watch`
- Ver logs: `make logs`
- Estado de contenedores: `make status`
- Migraciones: `make db-migrate`
- Seeders: `make db-seed`
- Inicializar BD: `make db-init`
- Tests backend host: `make test`
- Tests backend Docker: `make test-docker`
- Build frontend Docker: `make frontend-build`
- Sincronizar workspaces AnythingLLM: `make sync-workspaces`

## Backend

- Ruta base: `backend/`.
- Framework: Fat-Free Framework (`bcosca/fatfree-core`).
- PHP minimo: 8.5.
- Autoload PSR-4: `App\` apunta a `backend/src/`.
- Todos los archivos PHP nuevos deben iniciar con `declare(strict_types=1);`.
- Usar tipos en parametros, retornos y propiedades.
- Preferir promoted properties en constructores.
- Usar `readonly class` para DTOs, mappers o formatters inmutables cuando aplique.
- Preferir `match`, `??`, `??=`, `?->` y named arguments cuando mejoren claridad.

Flujo esperado de backend:

1. `Routes`: definen endpoints y middlewares.
2. `Validators`: validan entrada.
3. `Controllers`: orquestan, sin logica de negocio ni SQL.
4. `Services`: contienen reglas de negocio.
5. `Repositories`: acceso a datos y consultas.
6. `Models`: entidades/tablas.
7. `Mappers`: transformacion de datos.
8. `Formatters` o `Resources`: salida API consistente.
9. `Exceptions`: errores de dominio o sistema.

## Frontend

- Ruta base: `frontend/`.
- Stack: Vue 3 con Composition API y `<script setup>`, Pinia, PrimeVue 4, Tailwind CSS 4, Vite.
- Mantener los modulos bajo `frontend/src/modules/[modulo]/`.
- Estructura preferida por modulo: `components/`, `views/`, `store/`, `services/`, `mappers/`.
- Usar stores Pinia por modulo cuando el estado sea compartido o persistente en la vista.
- Usar servicios por modulo para llamadas HTTP.
- Usar mappers para adaptar payloads de API al formato de UI.
- Priorizar utilidades Tailwind y passthrough de PrimeVue sobre CSS nativo.
- Respetar el preset visual `mypreset` si se trabaja con PrimeVue o temas.

## Infraestructura

- Desarrollo previsto en Docker sobre WSL.
- El backend tiene Xdebug configurado.
- Docker vive en `docker/` y el orquestador principal es `docker-compose.yaml`.

## Verificacion

- Para cambios PHP, ejecutar tests relevantes con `make test` o `make test-docker` si el entorno Docker esta activo.
- Para cambios frontend, validar con `make frontend-build` cuando el cambio toque componentes, stores, servicios o build.
- Si se crean o modifican migraciones, el siguiente paso operativo es `make db-migrate`.

