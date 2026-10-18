# MK Company Data

Laravel app that pulls North Macedonia company listings from the public registry (zk.mk), stores them locally, and supports drafting outreach offers—including optional AI-generated copy—through an admin UI.

## Tech stack

- PHP 8.2+, Laravel 12
- Filament 4 (`/admin`)
- Symfony BrowserKit + HttpClient for HTML scraping
- SQLite by default (MySQL supported via `.env`)
- Laravel AI + OpenAI for structured offer text
- Database-backed queues, Pest, Vite

## What it does

- Scrapes companies by sector from zk.mk (one queued job per sector, robots.txt-aware) and records per-run metrics
- Normalizes company names (legal suffixes, Cyrillic → Latin) and merges duplicates
- Enriches missing e-mails via the company website, a search query and pattern generation; every address stores its source and pattern-generated ones are flagged as **inferred** (unverified)
- Ranks companies with a transparent activity index `0.5·E + 0.3·P + 0.2·F`
- AI (OpenAI): offer text tailored to sector/city/activity, target recommendations, and data-quality flags
- Sends offers idempotently (never twice to the same company) with `pending / sent / failed / opened` tracking; with `MAIL_MAILER=log` this is a test mode
- Filament 4 admin panel in Macedonian: companies, offers, scrape runs, dashboard

See [docs/PAPER_ALIGNMENT.md](docs/PAPER_ALIGNMENT.md) for what is implemented, partial or planned relative to the paper.

## Useful commands

- `php artisan app:scrape-companies-command [--sync] [--sector=PROGRAMMING]` — queue (or run) sector scraping; recalculates the activity index at the end
- `php artisan queue:work` — process queued scraping jobs
- `php artisan app:deduplicate-companies [--dry-run]` — fill `normalized_name` and merge duplicates
- `php artisan app:enrich-company-emails --limit=100` — find missing e-mails (slow: one company can take about a minute)
- `php artisan app:backfill-email-source` / `app:clean-junk-emails` — classify legacy e-mails / clear tracker addresses
- `php artisan app:calculate-activity-index` — recompute the index
- `php artisan app:detect-invalid-companies` — AI data-quality flags (batches of 20)
- `php artisan app:company-statistics [--csv=table2.csv]` — Table II metrics computed from the database
- `php artisan app:send-scheduled-offers` — send offers whose scheduled time has come (the scheduler runs it every minute: `php artisan schedule:work`)
- `php artisan app:set-user-role user@example.com operator` — roles: `admin` (everything) or `operator` (companies and offers, no pipeline, no deletions)
- `php artisan app:add-company-email "Name" "info@domain.mk"` — set an e-mail manually

## Read-only API

Set `API_TOKEN` in `.env`, then send `Authorization: Bearer <token>`:

- `GET /api/companies` — filters: `sector`, `city`, `has_email`, `has_website`, `min_index`, `q`, `sort` (`index`|`name`), `per_page` (max 100)
- `GET /api/companies/{id}`

Every e-mail carries `email_source` and `email_is_inferred`, so a client never treats a guessed address as a found contact. Without a token the API answers 503.

## Setup

1. `composer run setup` — installs PHP deps, creates `.env` if needed, generates the app key, runs migrations, installs and builds front-end assets  
2. Set `OPENAI_API_KEY` in `.env` if you use AI offer generation  
3. `composer run dev` — local server, queue worker, and Vite together  

Admin login lives at `/admin` after you create a user (e.g. `php artisan make:filament-user`). Optional: `php artisan app:scrape-companies-command` to refresh company data from zk.mk.
