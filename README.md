# VoiXLib

**Your library, beyond the shelf.**

A production-ready digital reading platform: real public-domain books, an editorial
interface, a distraction-free reader with persistent progress and bookmarks, and
Discord sign-in. PHP backend + Supabase + vanilla JS/SVG. No frameworks.

---

## What's inside

| Piece | Tech |
|---|---|
| Backend | PHP 8.2+ (no Composer), clean Controllers / Services / Repositories split |
| Database | Supabase (PostgREST) — schema in `supabase/schema.sql`, RLS enabled |
| Auth | Discord OAuth2 (server-side flow, state validation, secure sessions) |
| Books | Project Gutenberg via Gutendex API; enrichment + covers via Open Library |
| Reader | Sanitized Gutenberg HTML, chapter splitting, comfort settings, progress sync |
| Frontend | Hand-rolled CSS design system, inline SVG brand/scene/icons, vanilla ES5-safe JS |

## Setup

### 1. Requirements

- PHP 8.2+ with `curl`, `dom`, `mbstring`, `openssl` extensions
- A Supabase project (free tier is fine)
- A Discord application

### 2. Configure

```bash
cp .env.example .env
# fill in every value — see the comments inside
```

Create the Discord app at <https://discord.com/developers/applications> → OAuth2 →
add redirect `{APP_URL}/auth/callback.php`. Copy client id/secret into `.env`.
Put your own Discord user ID into `ADMIN_DISCORD_IDS` to unlock `/admin.php`.

### 3. Create the schema

Open the Supabase dashboard → SQL editor → paste **all** of `supabase/schema.sql`
and run it. This creates the tables, indexes, RLS policies and seeds categories.

### 4. Seed real books

```bash
php tools/seed.php
```

This loads `storage/seed/books.json` — 200+ real Gutenberg titles with verified
covers and Open Library enrichment — into your database. Refresh the catalog any time:

```bash
php tools/build-seed.php   # re-fetches popular books from Gutendex + Open Library
php tools/seed.php
```

### 5. Serve

Point your web server's document root at `public/` (Apache config included via
`.htaccess`). For local dev:

```bash
php -S localhost:8000 -t public tools/router.php
```

Visit `http://localhost:8000`.

> The app degrades gracefully: without Supabase credentials pages render but show
> "library isn't connected" states instead of fake data.

### 6. Deploy to Vercel

Vercel runs PHP through the community `vercel-php` runtime (serverless lambdas).
Auth is stateless (HMAC-signed cookies), so it works there — the repo ships with a
ready `vercel.json`.

1. Push this repo to GitHub.
2. In Vercel: **Add New → Project → Import** your repo. The runtime is picked up
   from `vercel.json` automatically.
3. Add these **Environment Variables** in the Vercel project settings:

   | Key | Value |
   |---|---|
   | `APP_URL` | e.g. `https://vxlib.vercel.app` |
   | `APP_ENV` | `production` |
   | `DISCORD_CLIENT_ID` / `DISCORD_CLIENT_SECRET` | same as local |
   | `DISCORD_REDIRECT_URI` | `https://vxlib.vercel.app/auth/callback.php` |
   | `SUPABASE_URL` / `SUPABASE_ANON_KEY` / `SUPABASE_SERVICE_ROLE_KEY` | same as local |
   | `ADMIN_DISCORD_IDS` | your Discord ID |
   | `APP_SECRET` | long random string (`openssl rand -hex 32`) |

4. In Discord's developer portal add the production redirect URI alongside the
   local one.
5. Deploy. Then run the schema + seed once against Supabase if you haven't:
   SQL editor → `supabase/schema.sql`, then locally `php tools/seed.php`
   (seeding talks straight to Supabase, not to Vercel).

Serverless notes: file caches live under the system temp dir and are per-instance;
external API responses are cached opportunistically. Nothing critical depends on
the filesystem — auth, CSRF and OAuth state all use signed cookies.

## Architecture

```
/public          web root only — thin entry points
  /auth          Discord OAuth redirect + callback
  /api           JSON endpoints (catalog, library, bookmarks, progress, sync)
/app             bootstrap, Config loader
  /Controllers   page + admin controllers
  /Services      SupabaseClient, Gutenberg/OpenLibrary services, cache, auth,
                 prefs, content sanitizer, HTTP helper
  /Repositories  BookRepository, UserRepository, LibraryRepository
  /Security      session boot, CSRF, rate limiter
/config          .env loader (file lives OUTSIDE public root)
/resources/views layout, page templates, components (brand SVGs, icons, cards…)
/storage         cache, rate-limit files, seed snapshot (gitignored except seed)
/supabase        schema.sql with RLS
/tools           build-seed.php, seed.php (CLI)
```

## Security notes

- Secrets live only in `.env`; `SUPABASE_SERVICE_ROLE_KEY` never leaves PHP.
- All writes go through CSRF-checked endpoints; OAuth uses signed one-time state.
- Sessions are HttpOnly/SameSite=Lax cookies with periodic ID rotation.
- External HTML is sanitized against an allowlist before it reaches the reader.
- Rate limiting on auth, API mutations and external fetches.
- Public tables are readable via anon key; user-owned tables have **no** public
  policies (RLS denies everything without the service role).

## Data sources & attribution

- Texts & metadata: [Project Gutenberg](https://www.gutenberg.org) (via [Gutendex](https://gutendex.com))
- Enrichment & covers: [Open Library](https://openlibrary.org)
- Covers missing from sources are generated as bespoke SVGs from each book's own metadata.

## CLI cheat sheet

```bash
php tools/build-seed.php     # refresh seed snapshot from live APIs
php tools/seed.php           # push snapshot into Supabase
```

### Dev helpers

```bash
php -S localhost:8000 -t public tools/router.php   # pretty 404s under the built-in server
php tools/enrich.php 60                            # backfill years/pages via Open Library
php tools/check-db.php                             # verify schema is applied
```
