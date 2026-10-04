# Implementation Plans — Execution Convention

Plans in this directory use GitHub-style checkboxes (`- [ ]` / `- [x]`) to track
progress. To keep a plan an accurate, resumable record of what has actually been
done, follow these rules when executing one.

## The rule: check off boxes as you go, in the same commit

When executing a plan task-by-task:

1. Complete a task's steps and run its verification.
2. **The instant verification passes, edit the plan file** to change that task's
   step checkboxes from `- [ ]` to `- [x]`.
3. **Stage the plan file with the task's code changes** so the checkbox update
   lands in the same commit as the work it records:
   ```bash
   git add <code paths> docs/superpowers/plans/<this-plan>.md
   git commit -m "<task commit message>"
   ```
4. A task is not "done" until its boxes are checked in the doc. When every step
   in a task is checked, the task is complete.

When a whole plan is finished, add a status banner at the top:

```markdown
> **STATUS: ✅ COMPLETED** (YYYY-MM-DD). <one line: what passed / commit range>
```

## Why in the same commit

- The plan file stays truthful at every commit — `git checkout <sha>` shows the
  exact progress at that point.
- Execution is resumable after an interruption: unchecked boxes are the
  remaining work, no separate tracking needed.
- Reviewers see the plan and the code change together.

## Note for agents

Keeping an ephemeral in-session todo list (e.g. a `todowrite` list) is helpful,
but it is NOT a substitute for updating the plan file — the doc is the durable,
shared record. Update both.
