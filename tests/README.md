# Tests del proyecto

Convencion GitOps (igual que SECE y el kit en gitops-template).

## Estructura

- **`tests/gitops-prerequisites/run.sh`**: Dockerfiles, nginx, render init-gitops, validate-compose.
- **`tests/unit/run.sh`**: PHPUnit en contenedor PHP CLI (sin stack levantado).

El job `test_unit` en `gitops/ci/gitops.yml` ejecuta cada `tests/*/run.sh` en orden lexicografico.

## Ejecucion local

```bash
make test          # todas las suites (como CI)
make test-unit     # solo PHPUnit
make test-gitops   # solo GitOps
make test-docker   # PHPUnit con docker compose exec (stack levantado)
```

Gate recomendado antes de push: `make test-unit` o `make test`.
