## Worker Endpoints

Defined in `routes/worker-auth.php`.

### Auth (guest)
- POST `/api/worker/login`

### Authenticated (Sanctum)
- POST `/api/worker/logout`
- Resource `/api/worker/attendance`
- Resource `/api/worker/expenses`
- GET `/api/worker/get-expenses`
- GET `/api/worker/today/attendance`
- POST `/api/worker/check-in`
- POST `/api/worker/check-out`
- GET `/api/worker/monthly-stats`
- Resource `/api/worker/notifications`
- GET `/api/worker/assigned-jobs-count`
- GET `/api/worker/assigned-jobs`
- GET `/api/worker/assigned-jobs/{id}`
- PUT `/api/worker/assigned-jobs/{id}`

Example check-in:
```bash
curl -X POST "$API_BASE/api/worker/check-in" -H "Authorization: Bearer $TOKEN"
```

