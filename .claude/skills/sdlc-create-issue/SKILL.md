---
name: sdlc-create-issue
description: Turn a rough task description into a well-formed GitHub issue on ride-stream. Use when the user wants to define, refine or file a task. Analyzes the codebase in plan mode, classifies it as a feature or bug, and creates the issue with gh only after the user approves the draft.
disable-model-invocation: true
argument-hint: <task description>
---

# Create a GitHub issue from a task

## Hard rules
- This repo is public. Never put secrets, `.env` values, tokens, internal hostnames or personal data in an issue.
- Do not create the issue until the user has approved the draft through plan mode. Approval of an earlier draft does not cover a changed one.
- Read-only until approval: no file edits, no branches, no commits.
- Never create duplicates: search existing issues first.

## Steps
1. Take the task from the arguments. If it is empty, ask the user to describe it.
2. Call `EnterPlanMode`. Everything until step 7 happens in plan mode.
3. Analyze the task against the codebase (Glob, Grep, Read, or an Explore agent for broad searches):
   - Find the modules, classes, routes, jobs or Kafka consumers involved.
   - For a bug, locate the likely cause and the conditions that trigger it.
   - For a feature, note where it fits and which existing code to reuse.
   - Run `gh issue list --state all --search "<keywords>" --limit 10` and flag likely duplicates or related issues.
4. Classify the task:
   - **bug**: existing behaviour is wrong, crashes, or regresses. Label `bug`, title prefix `fix:`.
   - **feature**: new or changed behaviour. Label `enhancement`, title prefix `feat:`.
   - If it is truly ambiguous, ask the user with `AskUserQuestion`. Also ask about any missing detail you cannot find in the code (expected behaviour, repro steps, scope).
5. Draft the issue:
   - **Title**: imperative, under 70 chars, with the prefix from step 4.
   - **Body**: read `.github/ISSUE_TEMPLATE/bug.md` or `.github/ISSUE_TEMPLATE/feature.md` and fill in every section, keeping the headings exactly. Drop the front matter and HTML comments. Remove a section only if the template marks it optional and there is nothing to say.
   - Mention related or duplicate issues as `#<number>`.
6. Present the draft as the plan: classification and why, label, title, full body. Call `ExitPlanMode` so the user can approve or request changes. If they ask for changes, revise and present again.
7. After approval, write the body to a file in the scratchpad directory and create the issue:
   `gh issue create --title "<title>" --label "<label>" --body-file <path>`
   Never interpolate the body into the shell command.
8. Report the issue URL and number. Suggest `/sdlc-start-feature` to begin work, using the issue number in the branch name.
