# Database migrations

NeNe Serve applies schema in two layers. Understand the split before adding a
migration.

## 1. Baseline — raw SQL (`database/migrations/NNNN_*.sql`)

The numbered files `0001_create_organizations.sql` … are the **historical
baseline** and the single source of truth for the existing schema.

- **Immutable.** Do not edit or renumber a shipped baseline file. They are the
  MySQL SSOT.
- Applied on a fresh MySQL database by a plain loop (see `.github/workflows/ci.yml`):
  ```sh
  for f in database/migrations/*.sql; do mysql … < "$f"; done
  ```
- Translated to PostgreSQL DDL by `php scripts/mysql-to-pgsql.php` (globs `*.sql`
  only, so it never touches phinx PHP migrations).
- `database/grants.sql` applies the least-privilege app role (no DELETE/TRUNCATE,
  ADR 0022).

## 2. Future changes — phinx (`phinx.php`)

From now on, **new** schema changes go through phinx, wired via `phinx.php`
(config-only; ports the sibling NENE2 convention through the shared NENE2
`ConfigLoader`, so it targets whatever DB `.env` points at).

```sh
composer migrations:create -- CreateWidgetsTable   # scaffold a PHP migration
composer migrations:status                         # show applied/pending
composer migrations:migrate                        # apply pending
composer migrations:rollback                       # roll back the last batch
composer migrations:seed                            # run seeds (database/seeds)
```

Phinx only ever scans `*.php`, so the `.sql` baseline files above are invisible
to it and coexist safely in the same directory. New migrations are timestamp-named
PHP classes (`version_order: creation`) and are tracked in the `phinxlog` table.

### Bootstrap order on a fresh database

1. Apply the raw-SQL baseline (loop above) — brings the DB to the shipped schema.
2. `composer migrations:migrate` — applies any phinx migrations added since.

Phinx records only its own migrations in `phinxlog`; the baseline is a one-time
bootstrap, not a phinx migration. Converting the 33 baseline files into phinx
migrations was deliberately **not** done (large, and it would risk the SSOT for no
functional gain).

### Note on the phinx dependency

`robmorgan/phinx` is currently in `require-dev`. Running migrations from a
production `--no-dev` install would therefore need phinx present. If/when
production deploys run `composer migrations:migrate` directly, move phinx to
`require` (NENE2 documents the same caveat for its `DatabaseSchemaApplier`).
Today baseline application uses the raw-SQL loop, so require-dev is sufficient.
