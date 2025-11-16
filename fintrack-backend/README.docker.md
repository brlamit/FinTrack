# Docker quickstart

From the `fintrack-backend` directory.

This project uses Supabase (Postgres) for the database. Provide your Supabase credentials
via a local `.env` file (do NOT commit it). Example `.env` entries:

```
# Supabase provided Postgres connection (preferred)
DATABASE_URL=postgres://postgres:password@db.abcd.supabase.co:5432/postgres

# Optional explicit DB_* variables (Laravel will accept DATABASE_URL too)
DB_CONNECTION=pgsql
DB_HOST=db.abcd.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=password

# Supabase client keys (if your app uses Supabase client APIs)
SUPABASE_URL=https://abcd.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJI...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJI...
```

1. Build and start the app container:

```powershell
cd fintrack-backend
docker compose up --build -d
```

2. Run migrations (optional):

```powershell
docker compose exec app php artisan migrate --force
```

3. Stop and remove containers:

```powershell
docker compose down
```

Notes:
- The app serves on port `8000` by default (http://localhost:8000).
- Ensure your `.env` contains `DATABASE_URL` or the `DB_*` vars from Supabase before starting.
- For production deploys, use a hardened setup with `php-fpm` + `nginx` and secrets management.
