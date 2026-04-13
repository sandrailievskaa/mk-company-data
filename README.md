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

- Scrapes companies by sector from zk.mk with pagination handled end-to-end
- CRUD for companies and offers in Filament
- AI agent returns a fixed JSON shape (title + body) for offers
- Artisan commands to scrape the registry, batch-enrich missing emails, and set an email on a company by name

## Highlights

- Scraping stays in a dedicated service (`CompanyScraperService`) instead of controllers
- Offer drafting uses a structured-output agent so the UI always gets the same fields
- Email enrichment runs as a console job you can cap or run across the full dataset

## Setup

1. `composer run setup` — installs PHP deps, creates `.env` if needed, generates the app key, runs migrations, installs and builds front-end assets  
2. Set `OPENAI_API_KEY` in `.env` if you use AI offer generation  
3. `composer run dev` — local server, queue worker, and Vite together  

Admin login lives at `/admin` after you create a user (e.g. `php artisan make:filament-user`). Optional: `php artisan app:scrape-companies-command` to refresh company data from zk.mk.
