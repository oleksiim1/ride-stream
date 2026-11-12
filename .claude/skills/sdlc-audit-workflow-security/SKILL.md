---
name: sdlc-audit-workflow-security
description: Audit every file in .github/workflows and .github/actions of this public repo for token, secret and untrusted-input violations, and report each with a fix. Use after changing any workflow or before adding Claude-driven automation.
---

# Audit workflow security (report only)

This repo is public, so everything from outside contributors is untrusted. **Do not modify any file** (including `ci.yml`, `.github/dependabot.yml`). Report findings and proposed fixes; the user applies them in a separate PR.

## Steps
1. List every file: `.github/workflows/*` and `.github/actions/**/action.yml`. Read each completely. Also check whether `.github/dependabot.yml` exists.
2. Check each against these rules and record file, line, rule, and fix:
   1. **Permissions:** top-level `permissions: contents: read`; extra permissions only per job, and only when needed.
   2. **Trigger:** no `pull_request_target` combined with checking out or running PR code (`ref: ${{ github.event.pull_request.head.* }}`). CI uses plain `pull_request`. Flag `workflow_run` and `issue_comment` triggers that act on PR code too.
   3. **Secrets and forks:** no secrets (Anthropic key, `GITHUB_TOKEN` with write scope) available to fork-triggered runs. Look for `secrets.*` in jobs that run on `pull_request`.
   4. **Claude-driven workflows** (e.g. `anthropics/claude-code-action`): must have an `if:` limiting to the repository owner (`github.actor`/PR author) or to PRs whose `head.repo.full_name == github.repository`. AI steps must be read-only.
   5. **Untrusted input:** no `${{ github.event.* }}` (title, body, branch name, comment, `head_ref`) interpolated directly in `run:` scripts or AI prompts. They must pass through `env:`. Flag `${{ github.head_ref }}` in `run:` too.
   6. **Action pinning:** third-party actions pinned to a full 40-char commit SHA with the version in a comment (`uses: owner/repo@<sha> # v1.2.3`). Flag tags like `@v5`, `@v2`, branches. Check composite actions too.
   7. **Dependabot:** `.github/dependabot.yml` has a `github-actions` ecosystem.
   8. **Secret hygiene:** no `echo`/`printenv`/`set -x` of secrets, no secrets written to files, `upload-artifact` or caches (check `.env` handling, since CI copies `.env.example` only).
3. Print a table: `Severity | File:line | Rule | Problem | Fix`, then a summary count. Say "no violations" per rule when clean.

## Known state at creation (re-verify, do not assume)
`ci.yml` and the `setup-php` action use tag-pinned actions (`actions/checkout@v5`, `actions/cache@v4`, `shivammathur/setup-php@v2`) and there is no `dependabot.yml`. These are known and will be handled in a separate PR.

## Repository settings to recommend (cannot be checked from files)
- Actions > General > Fork pull request workflows: **require approval for first-time contributors** (or all outside collaborators).
- Actions > General > Workflow permissions: **Read repository contents** (read-only default token); do not allow Actions to approve PRs.
- Code security: **secret scanning and push protection** enabled.
