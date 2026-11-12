---
name: sdlc-start-feature
description: Start new work on ride-stream. Use when the user wants to begin a feature, bug fix, chore or CI change. Syncs main, creates a correctly prefixed branch, and confirms the working tree is clean.
disable-model-invocation: true
---

# Start a feature branch

## Hard rules
- Never push to `main`, never force push, never skip hooks or checks.
- Never commit secrets or `.env` files.
- Ask before any destructive or irreversible step (discarding changes, deleting branches).

## Steps
1. Run `git status --short`. If anything is listed, **stop** and ask the user whether to commit, stash or leave it. Never discard changes without explicit approval.
2. Pick the branch prefix from the user's description. Ask if unclear:
   - `feature/` new behaviour
   - `fix/` bug fix
   - `chore/` maintenance, dependencies, refactor
   - `ci/` anything under `.github/`
3. Build the name: `<prefix><kebab-case-summary>`, lowercase, letters, digits and hyphens only, max ~50 chars. Add the issue number if there is one, e.g. `fix/123-trip-eta-null`.
4. Sync main: `git switch main` then `git pull --ff-only origin main`. If the pull is not a fast-forward, stop and report.
5. `git switch -c <branch>`.
6. Confirm with `git status -sb`: the working tree is clean and the branch is based on the current `origin/main`. Report the branch name.
