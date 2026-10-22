# Copilot Instructions

This repository is ParkPass.

Use `AGENTS.md` as the canonical project context and follow these defaults:

- Backend uses PHP 8.5 and Fat-Free Framework, not Laravel.
- Frontend uses Vue 3, Composition API, Pinia, PrimeVue and Tailwind CSS.
- Keep 4-space indentation.
- New PHP files must start with `declare(strict_types=1);`.
- Keep controllers thin. Put business rules in services and database access in repositories.
- Use module structure under `frontend/src/modules/[module]/`.
- Use commands from the root `Makefile`.
- Do not suggest `php artisan`.
- Keep Docker configuration in `docker/`.

