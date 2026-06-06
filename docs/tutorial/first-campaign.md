# Tutorial — your first ad, end to end

A guided, persona-driven walk-through: from signing in, through building and
approving an ad, embedding it on a publisher page, **seeing it render**, checking
the results, and signing out. Pair it with the
[console reference](../reference/admin-console.md) when you want to look up what a
screen means.

## Who's in this story

- **Mei Tanaka** — ad-operations lead at **Acme Media** (role `org_admin`). She
  sets everything up.
- **Ken Sato** — a colleague Mei invites as a **reviewer** (role `editor`). He
  provides the second pair of eyes that approves the ad.
- **news.example** — a publisher site that will embed the ad.

> **Four-eyes:** whoever submits a creative cannot approve it. That's why we need
> both Mei and Ken.

## Before you start (local dev)

Bring the stack up and start the console:

```bash
docker compose up -d            # api 8010 · db · phpMyAdmin 8011 · Mailpit 8013 · ClamAV
cd frontend && npm run dev      # console on http://localhost:5180
```

Seeded starter account (dev only — change in production):

| Field | Value |
| --- | --- |
| Console | `http://localhost:5180` |
| Organization | `acme` |
| Email | `admin@acme.test` |
| Password | `password123` |
| Caught email (Mailpit) | `http://localhost:8013` |

---

## Step 1 — Mei signs in

1. Open `http://localhost:5180` → you land on **Login**.
2. Enter **Organization** `acme`, **Email** `admin@acme.test`, **Password**
   `password123`, and submit.
3. You arrive at **Placements** with the full nav (Placements · Creatives ·
   Review · Metrics · Marketplace · Users · Settings).

✅ *Expected:* you're signed in as Mei (`org_admin`).

## Step 2 — Configure outbound email (so invites can be sent)

1. Go to **Settings**.
2. Fill the SMTP form for the dev mail catcher:
   - **Host** `mailpit` · **Port** `1025` · **Encryption** `none`
   - **From address** `no-reply@acme.test` · **From name** `Acme Media`
   - Username/Password: leave blank.
3. **Save**, then **Send test email**.
4. Open Mailpit at `http://localhost:8013`.

✅ *Expected:* a "NeNe Serve — SMTP test" email appears in Mailpit. (The stored
password, if any, is encrypted at rest and never shown back.)

## Step 3 — Invite Ken, the reviewer

1. Go to **Users** → **Invite a user**.
2. Email `ken@acme.test`, Role `editor`, send the invite.
3. The banner confirms the invitation email was sent. Open Mailpit — there's a
   "set your password" email to Ken with a link like
   `http://localhost:5180/set-password?token=…`.
4. Open that link (as Ken), choose a password (≥ 8 chars, e.g. `ken-password`),
   submit, then **Go to sign in**.

✅ *Expected:* Ken can now sign in with `acme` / `ken@acme.test` /
`ken-password`. The invite token is single-use and expires in 72 hours.

## Step 4 — Mei sets up the marketplace

Go to **Marketplace** and create, in order:

1. **Advertiser** — name `Globex`.
2. **Pricing rule** — name `CPM standard`, model `cpm`, **Rate (cents)** `50000`
   (= ¥500 per 1,000 impressions).
3. **Campaign** — name `Spring launch`, pick advertiser `Globex` and pricing rule
   `CPM standard`, **Budget (cents)** `100000` (= ¥1,000).

✅ *Expected:* each appears in its table. Money is net minor units; spend will be
derived and never exceeds the budget.

## Step 5 — Mei uploads a creative

1. Go to **Creatives** → **New image creative**.
2. **Image file:** choose any PNG/JPEG (it's uploaded and malware-considerations
   apply to bundles; images are type-checked). Wait for "Uploaded."
3. **Destination URL** `https://news.example/landing`, **Width** `300`,
   **Height** `250`. Create.

✅ *Expected:* a new row with **Review status** `draft`. Note its **ID** (e.g.
`cr-…`) — you'll need it in Step 7.

## Step 6 — Submit the creative for review

> The "submit for review" button is a UI follow-up; today you submit through the
> API. Mei submits (so Ken can approve — four-eyes).

```bash
# Get Mei's token, then submit the draft creative (replace cr-XXXX with its ID).
TOKEN=$(curl -s -X POST http://localhost:8010/admin/login -H 'Content-Type: application/json' \
  -d '{"organization":"acme","email":"admin@acme.test","password":"password123"}' | sed 's/.*"token":"//;s/".*//')
curl -s -X POST http://localhost:8010/admin/creatives/cr-XXXX/submit \
  -H "Authorization: Bearer $TOKEN" | head -c 200; echo
```

✅ *Expected:* the response shows `review_status` `submitted`. The creative now
appears in the **Review** queue.

## Step 7 — Ken approves (the second pair of eyes)

1. Sign out (top bar), then sign in as **Ken** (`acme` / `ken@acme.test` /
   `ken-password`).
2. Go to **Review**. The creative is in the queue.
3. Click **Start review**, then **Approve**.

✅ *Expected:* approval succeeds. (Had *Mei* tried to approve her own submission,
it would be refused 403 — that's four-eyes.) Sign out; sign back in as Mei.

## Step 8 — Create the placement and bind the creative

1. As Mei, go to **Placements** → **New placement**.
2. **Public placement key** `news_home_top`.
3. **Allowed origins** `http://localhost:8080` (the origin we'll serve the test
   publisher page from in Step 9 — this lets the browser fetch the ad).
4. **Default creative id** = the approved creative's ID from Step 5 (`cr-…`).
5. Create.

✅ *Expected:* a placement `news_home_top`, status `active`, default creative set.

## Step 9 — Embed serve.js and see it render

Create a tiny publisher page and serve it on the allowed origin:

```bash
mkdir -p /tmp/news && cat > /tmp/news/index.html <<'HTML'
<!doctype html>
<html><head><meta charset="utf-8"><title>news.example</title></head>
<body>
  <h1>news.example — homepage</h1>
  <!-- The NeNe Serve embed: one script tag, no other code. -->
  <script src="http://localhost:8010/serve.js" data-placement="news_home_top" async></script>
</body></html>
HTML
php -S localhost:8080 -t /tmp/news
```

Open `http://localhost:8080` in a browser.

✅ *Expected:* the ad image renders on the page, wrapped in a link. Under the
hood serve.js:
- called `GET /public/placements/news_home_top/serve` (origin-checked),
- rendered the approved image,
- fired a **viewable impression** beacon,
- wrapped the click through a single-use `/public/clicks/{token}` redirect (no
  open redirect). Clicking the ad opens `https://news.example/landing`.

Prefer the command line? You can see the serve payload directly (no browser):

```bash
curl -s "http://localhost:8010/public/placements/news_home_top/serve" | head -c 300; echo
```

It returns the creative payload with opaque tokens only — no internal ids, no
PII. (An empty/204 response means nothing eligible: not approved, opted out, or
capped.)

## Step 10 — Mei checks the results

1. Back in the console as Mei, open **Metrics**.
2. Reload the publisher page (and click the ad) a few times to generate events.

✅ *Expected:* the KPI cards (Impressions · Clicks · CTR · Fill rate) and the
daily table reflect your activity. Figures are aggregate only — no visitor
identifiers. (Counts are idempotent: refreshing the same beacon won't inflate
impressions.)

## Step 11 — Sign out

Click **Sign out** in the top bar (do the same for Ken if still signed in
elsewhere). The session token is held in memory only, so it's gone immediately.

---

## What you exercised

Login → SMTP setup → invite & onboard a teammate → advertiser/pricing/campaign →
upload creative → submit → **four-eyes approval** → placement → **embed &
render** via serve.js → impression/click measurement → metrics → logout — the
whole operating loop, on the locked-down, audited production-equivalent backend.

### Notes & current limits

- **Submit-for-review** is an API step today (Step 6); a console button is a
  planned follow-up.
- **Video / HTML5-bundle** upload from the console is planned; HTML5 bundles are
  ClamAV-scanned and only submit when clean.
- Credentials and ports here are **local dev** defaults — production sets real
  secrets and origins.

See also: [console reference](../reference/admin-console.md) ·
[serve.js embed spec](../explanation/serve-embed-spec.md) ·
[measurement spec](../explanation/measurement-spec.md).
