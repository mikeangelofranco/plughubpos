# Mobile POS (mobile-first)

## Requirements
- PHP 8.1+ (PDO pgsql enabled)
- PostgreSQL (local install is fine)

## Setup
1. Create `.env` from `.env.example` and adjust values if needed.
2. Create database + tables:
   - Recommended (Windows/PowerShell): `powershell -ExecutionPolicy Bypass -File scripts/init-db.ps1`
   - If your `DB_USER` has CREATEDB: `powershell -ExecutionPolicy Bypass -File scripts/create-db-from-env.ps1`
   - Or manually with a superuser connection: `psql -U postgres -f scripts/create_db.psql`
   - Or if the DB already exists: `psql -U plughub -d plughub_possystem -f scripts/schema.sql`
3. Run the dev server: `php -S 127.0.0.1:8000 public/router.php` (or `php -S 127.0.0.1:8000 -t public public/router.php`)
4. Open: `http://127.0.0.1:8000`

## Quick checks
- DB health: `http://127.0.0.1:8000/api/health`

## Login (stub)
- Default user is seeded by `scripts/schema.sql`: `admin` / `Cablet0w` (change/remove for production).
- Login URL: `http://127.0.0.1:8000/login`

## Roles & tenants
- Multi-tenant aware: Admin (cross-tenant), Manager (per-tenant full control), Cashier (sell flow), Readonly (view only).
- Schema seeds helpers for the default tenant: `manager` / `cashier` / `readonly` (all `Cablet0w` by default).
- Admin can switch tenant scopes from the top bar; non-admin roles are pinned to their tenant.

## Branding
- Logo used across screens lives at `public/assets/img/logo.svg` (PNG fallback optional).
  - Replace that file with your provided logo to update branding.
