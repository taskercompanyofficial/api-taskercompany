## Application Endpoints (`/api/app/*`)

Defined in `routes/application-routes.php` under `Route::prefix('app')`.

### Auth (guest)
- POST `/api/app/auth/register`
- POST `/api/app/auth/login`
- POST `/api/app/auth/check-credentials`
- POST `/api/app/auth/verify-otp`
- POST `/api/app/auth/send-otp`

Example login:
```bash
curl -X POST "$API_BASE/api/app/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"secret"}'
```

### Authenticated
- POST `/api/app/auth/logout`
- GET `/api/app/app/services`

Note: The path includes `app/app/services` by definition.

Example services fetch:
```bash
curl -H "Authorization: Bearer $TOKEN" "$API_BASE/api/app/app/services"
```

