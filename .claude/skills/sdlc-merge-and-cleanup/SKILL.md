---
name: sdlc-merge-and-cleanup
description: Squash-merge a ride-stream pull request after the owner has reviewed it, delete the branch and sync local main. Use only when the user explicitly says to merge a specific PR.
disable-model-invocation: true
---

# Merge and clean up

## Hard rules
- **Merge only after the repository owner has reviewed the PR and explicitly told you to merge it in this conversation.** A green CI is not permission.
- Never submit an approving review, never auto-approve, never use `--admin` to bypass checks or branch protection.
- Never push to `main`, never force push, never skip checks.
- Ask before any destructive or irreversible step. Merging and deleting the branch both count, so confirm once more with the PR number and title.

## Steps
1. Identify the PR: `gh pr view <n> --json number,title,state,isDraft,headRefName,reviewDecision,mergeable`. Stop if it is a draft, closed, or conflicting.
2. Confirm the user said to merge this PR. If not, stop and ask.
3. Checks: `gh pr checks <n>`. The required jobs are `Code style (Pint)`, `Static analysis (Larastan)`, `Security audit (Composer)` and `Tests (PHPUnit)`. If any is failing or pending, stop (use `/sdlc-address-ci-failure` for failures).
4. Show PR number, title, and branch, and ask for a final yes.
5. `gh pr merge <n> --squash --delete-branch`. Keep the title as `<title> (#<n>)`.
6. Sync local: `git switch main`, `git pull --ff-only origin main`, `git fetch --prune`.
7. If the local branch still exists and is merged, ask before `git branch -D <branch>` (squash merges need `-D`).
8. Report the merge commit and confirm `git status` is clean on `main`.
