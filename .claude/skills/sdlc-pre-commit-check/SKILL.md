---
name: sdlc-pre-commit-check
description: Run the same checks as CI (Pint, Larastan, composer audit, PHPUnit) through docker compose before committing or opening a PR, and report failures with suggested fixes.
---

# Pre-commit check (mirrors `.github/workflows/ci.yml`)

> **ASSUMPTION:** the repo has no `docker-compose.yml` yet. The service name `app` below is a placeholder. Once the compose file exists, replace `app` everywhere in this file with the real PHP service name, and confirm the postgres (PostGIS), redis and kafka services are started.

Define `RUN="docker compose exec -T app"`. If the stack is not running, ask before `docker compose up -d`.

## Hard rules
- Never skip a check, never use `--no-verify`, never edit CI or `phpstan.neon` to make a check pass.
- Never commit secrets or `.env`.
- Run Pint auto-fix only after the user agrees, since it rewrites files.

## Steps (CI order; stop at the first failure)
1. **Code style** (job `code-style`): `$RUN vendor/bin/pint --test`
2. **Static analysis** (job `static-analysis`, level 5): `$RUN vendor/bin/phpstan analyse --memory-limit=1G --no-progress`
3. **Security audit** (job `security-audit`): `$RUN composer audit --locked --abandoned=report`
4. **Tests** (job `tests`; needs PostGIS and Redis, `DB_CONNECTION=pgsql`):
   - `$RUN php artisan migrate --force --no-interaction`
   - `$RUN php artisan test --compact` (narrow with a path or `--filter` while iterating, but run the full suite once before the PR)

## Reporting
For each check, print PASS or FAIL. On FAIL, give the file and line, the cause, and a suggested fix:
- Pint: offer `$RUN vendor/bin/pint --dirty` (ask first), then re-run step 1.
- Larastan: fix the type or code. Do not add to a baseline or ignore list without asking.
- Audit: name the advisory and the package; suggest `composer update <pkg>`, and ask before changing `composer.lock`.
- Tests: show the failing test and assertion. Fix code or test, and say which you changed and why.

Re-run only the failed check after a fix, then the remaining ones.
