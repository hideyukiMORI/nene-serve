# Deploying NeNe Serve

> **Status: partial.** This document is being built up as production-deploy
> hardening lands, and it does not yet describe a complete deployment. What is
> written here is written because it is settled and someone can get it wrong
> today; the gaps are named as gaps rather than left to be discovered.
>
> Covered so far: health-check semantics, the rate limit store, the public
> serving stores (tokens and frequency caps).
> Not yet covered: migrations on deploy, HTTPS and secret handling, backup and
> restore.

**Line between this document and private notes** (hub ruling, 2026-07-29): the
**generic procedure is public**; **environment-specific values are private**
(hostnames, credentials, real domains live in the private receptacle). If a
paragraph would only make sense to whoever runs *our* install, it does not
belong here.

## Health checks: `/health` is readiness, not liveness

`GET /health` **depends on the database**. The throttle middleware runs on every
route, so every request — including `/health` — reads the shared rate limit
counter table (#199). When the database is unreachable, `/health` fails.

That is deliberate and it is the useful signal, because the counters live in the
same database the application needs for everything else. If the database is
gone, this instance cannot serve a placement, an admin call, or anything else,
and taking it out of rotation is the correct response.

| Probe | Use `/health`? | Why |
| --- | --- | --- |
| Load balancer / reverse proxy **readiness** | **Yes** | A failing instance stops receiving traffic — correct during a database outage |
| Process **liveness** (supervisor, systemd, container restart policy) | **No** | A restart does not fix an unreachable database. Restarting healthy instances during an outage turns a database problem into a thundering-herd problem |

Watch the **process** for liveness (systemd, the container runtime's own process
supervision, or an external process check). Do not point a restart policy at
`/health`.

If you need a probe that answers "is this PHP process alive" without touching
the database, that endpoint does not exist yet — file an issue rather than
repurposing `/health`.

## Rate limit store

The public surface is rate-limited per client (ADR 0019 §5, api-security §63),
and the counters must be **shared** across every process and host serving the
install. The framework's in-memory store cannot do this: serve builds its
container once per request, so an in-memory counter is recreated empty every
time and the limit is never reached (#199 — it shipped that way and could not
deny anything).

`NENE_SERVE_RATE_LIMIT_STORE` selects the store:

| Value | Behaviour |
| --- | --- |
| unset / `database` | **Default.** Counters in the `rate_limit_counters` table, shared across processes and hosts |
| `memory` | Per-process counters. **Production refuses to boot with this** — it cannot enforce a limit |

Leave it unset in production. It exists so a local install can run without a
database; setting it anywhere real is how the limiter becomes decorative again.

An unrecognised value is a **boot error**, not a fallback to the default — a
typo must not land a deployment on a store nobody chose.

### What the deployment has to provide

- The `rate_limit_counters` table (migration `0034`), and the grant from
  `database/grants.sql`: `SELECT, INSERT, UPDATE` — **no `DELETE`** (ADR 0022).
- Nothing else. There is no Redis or Memcached requirement; the store uses the
  database the application already has.

### Maintenance

Rows are reused in place, so the table grows with the number of **distinct
clients**, not with request volume. The application cannot delete rows, so
pruning windows that expired long ago is a **privileged maintenance task**, run
under the same kind of role as the governed retention purge — never under the
application role, and never on a request path.

## Public serving stores (tokens and frequency caps)

The public flow keeps two pieces of state that outlive a single request: the
opaque tokens (ADR 0019) and how many impressions a consent-gated visitor has
already been shown. `NENE_SERVE_PUBLIC_STORE` selects where they live:

| Value | Behaviour |
| --- | --- |
| unset / `database` | **Default.** Tokens in `public_tokens`; caps counted from the impression events themselves |
| `file` | `var/tokens.json` + `var/frequency.json`. **Production refuses to boot with this** |

One variable covers both because they hold two halves of the same flow, and
there is no deployment where one should be shared and the other not.

**Why production refuses `file`.** Unlike the rate limit case, the file stores
are not broken on a single host — they work. The problem is the failure they
have when you add the second one, which is silent: each host writes its own
`var/tokens.json`, so a **click token issued by host A and redeemed on host B
does not exist**, and the redirect fails closed on a token that was perfectly
valid. Nothing errors. The visitor simply does not arrive, and the click is not
counted. Refusing at boot turns that from a production incident into a
configuration error.

### What the deployment has to provide

- `public_tokens` (migration `0035`) and the index from migration `0036`, plus
  the grants from `database/grants.sql`: `SELECT, INSERT, UPDATE` — **no
  `DELETE`** (ADR 0022). `UPDATE` is needed only for the one-way flips
  (`recorded_at`, `consumed_at`).
- No Redis or Memcached. Both stores use the database you already have.

### Notes worth knowing before an incident

- **Tokens are stored as SHA-256 hashes**, never raw. Read access to
  `public_tokens` does not yield usable click tokens, and there is no way to
  recover a token from the table — that is intended.
- **Frequency caps are counted from the `impressions` table**, not from a
  counter. So the cap can never disagree with the numbers billing and reporting
  read (ADR 0015). It also means a cap added to a placement takes effect against
  impressions already delivered today, rather than starting from zero.
- **Expired token rows are not deleted by the application.** Same privileged
  maintenance story as the rate limit counters above (#205).
