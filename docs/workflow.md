# Workflow

Inherits [NENE2 workflow](https://github.com/hideyukiMORI/NENE2/blob/main/docs/workflow.md).

1. Issue → 2. branch `type/issue-number-summary` → 3. implement → 4. update docs → 5. `docs/review/` → 6. verify → 7. commit `(#n)` → 8. PR `Closes #n` → 9. merge.

Do not commit to `main` directly.

For UI changes: update all six `locales/*.json` keys in the same PR.

Last updated: 2026-06-03
