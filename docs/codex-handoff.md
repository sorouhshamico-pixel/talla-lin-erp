# Project Handoff — Talla Lin ERP

## Latest validated contract phase

```text
Phase: Phase 79A — Prepare Saved View Archiving Contract
Branch: main
Starting commit: 5c3def2
Baseline suite: 1595 passed / 14620 assertions
Pre-commit suite: 1602 passed / 14693 assertions
Runtime changes: none
Database changes: none
Workflow: direct main only
Status: awaiting commit, post-commit test, and push
```

## Selected implementation

Phase 79B will add reversible archive and restore lifecycle management.

## Locked boundaries

- nullable `archived_at`;
- active, archived, and all management modes;
- report-facing queries exclude archived rows;
- archiving clears default status atomically;
- restoring does not restore default status;
- single and bulk archive/restore;
- foreign rows are never changed or disclosed;
- CSV schema and version remain unchanged;
- selected export may explicitly export archived rows;
- existing delete, export, and import behavior remains supported.

## Next phase

Phase 79B — Implement Saved View Archiving.
