# PlugHub POS (mobile-first)

## Requirements
- PHP 8.1+ (PDO pgsql enabled)
- PostgreSQL (local install is fine)

## Setup
1. Create `.env` from `.env.example` and adjust values if needed.
2. Create database + tables:
   - Recommended (Windows/PowerShell): `powershell -ExecutionPolicy Bypass -File scripts/init-db.ps1`
   - Or manually with a superuser connection: `psql -U postgres -f scripts/create_db.psql`
   - Or if the DB already exists: `psql -U plughub -d plughub_possystem -f scripts/schema.sql`
3. Run the dev server: `php -S 127.0.0.1:8000 -t public`
4. Open: `http://127.0.0.1:8000`

## Quick checks
- DB health: `http://127.0.0.1:8000/api/health.php`
