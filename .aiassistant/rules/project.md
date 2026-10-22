---
apply: always
---

# Project Rules

Follow `AGENTS.md` as the primary source of assistant instructions for this repository.

Key constraints:

- Backend: PHP 8.5, Fat-Free Framework, PSR-12, strict types.
- Frontend: Vue 3, Composition API, Pinia, PrimeVue, Tailwind CSS.
- Commands: use root `Makefile`; do not invent Laravel/Artisan commands.
- Architecture: controllers orchestrate, services own business logic, repositories own data access.
- Docker: configuration belongs in `docker/`.
- Preserve user changes and keep edits scoped.

