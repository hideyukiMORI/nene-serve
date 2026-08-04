# Deploying NeNe Serve

> **Status: partial.** This document is being built up as production-deploy
> hardening lands, and it does not yet describe a complete deployment. What is
> written here is written because it is settled and someone can get it wrong
> today; the gaps are named as gaps rather than left to be discovered.
>
> Covered so far: health-check semantics, the rate limit store.
> Not yet covered: migrations on deploy, HTTPS and secret handling, the shared
> token and frequency stores for multi-host, backup and restore.

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
