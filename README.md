# RezinaSerp

## Project structure

- `/public` — frontend (HTML/CSS/JS)
- `/api` — backend JSON API (PHP + PDO)
- `/storage` — logs/uploads

## How to run (XAMPP)

Open: http://localhost/RezinaSerp/public/

User site files were placed in `/public` with original paths: `/public/css`, `/public/js`, `/public/images`.

## Database migrations (phpMyAdmin)

1. Create database (example: `rezinaserp`).
2. Import schema: open phpMyAdmin → select DB → **Import** → choose `api/migrations/001_schema.sql` → **Go**.
3. Import seed: `api/migrations/002_seed.sql`.

## Auth mode (session / jwt)

Edit `api/config/env.php`:

- `AUTH_MODE = 'session'` (default) — uses PHP session cookie.
- `AUTH_MODE = 'jwt'` — returns JWT token on login; use `Authorization: Bearer <token>`.
- `JWT_SECRET` — secret for JWT signature.

Session cookie params are in `SESSION_COOKIE`.

## API usage examples

Base URLs:

- `http://localhost/RezinaSerp/api/...`
- `http://localhost/RezinaSerp/api/v1/...`

### Registration (email or phone)

```bash
curl -X POST http://localhost/RezinaSerp/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret123","full_name":"Ivan Petrov"}'
```

### Login (session)

```bash
curl -X POST http://localhost/RezinaSerp/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret123"}' \
  -c cookie.txt
```

### Profile

```bash
curl http://localhost/RezinaSerp/api/profile/me -b cookie.txt
```

### Orders

```bash
curl http://localhost/RezinaSerp/api/orders/my -b cookie.txt
```

### Booking services

```bash
curl http://localhost/RezinaSerp/api/booking/services
```

### Booking create

```bash
curl -X POST http://localhost/RezinaSerp/api/booking \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{"service_id":1,"start_at":"2024-12-10 12:00:00","car_info":"Kia Rio","comment":""}'
```

## Notes

- All endpoints return JSON in `{ ok, data }` or `{ ok, error }` format.
- CORS is disabled by default; enable via `CORS_ENABLED` in `api/config/env.php` for future mobile clients.
