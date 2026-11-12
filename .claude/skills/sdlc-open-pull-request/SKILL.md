---
name: sdlc-open-pull-request
description: Review the branch diff, write the PR title and description (what, why, how to test), push the branch, and open a pull request against main with gh. Use when work on a branch is ready for review.
disable-model-invocation: true
---

# Open a pull request

## Hard rules
- Never push to `main`, never force push, never skip hooks or checks.
- Never commit secrets or `.env` files.
- Never approve, review-approve or merge. Only the repository owner reviews. Opening the PR is the end of this skill.
- Ask before any destructive or irreversible step.

## Steps
1. Confirm the current branch is not `main` and has the right prefix (`feature/`, `fix/`, `chore/`, `ci/`). Stop otherwise.
2. Review: `git status`, `git diff origin/main...HEAD --stat`, then read the full diff.
   - Stop if the diff contains `.env`, keys, tokens, passwords or credentials. Report the file and line.
   - Flag unrelated changes and debug leftovers (`dd(`, `dump(`, `ray(`).
3. Run `/sdlc-pre-commit-check`. Do not continue on failures.
4. Commit anything pending only if the user asks, with a clear message, no `--no-verify`.
5. Write the PR (title under 70 chars, imperative mood):
   ```
   ## What
   <1-3 bullets of the change>

   ## Why
   <problem or issue, link `Closes #N` if any>

   ## How to test
   <exact commands or requests, e.g. docker compose exec app php artisan test --filter=...>
   ```
   Show the title and body to the user and wait for approval before pushing.
6. `git push -u origin HEAD` (plain push, no `--force`).
7. `gh pr create --base main --title "<title>" --body "<body>"` (use `--body-file` for long bodies). Add `--draft` if the user wants.
8. Print the PR URL and remind the user that CI runs and that they must review it. Do not merge.
