## General API

These endpoints are defined in `routes/api.php`.

### POST /api/send-crm-notification
- Sends a CRM notification.
- Auth: not explicitly guarded here; use server-side policy as applicable.

Example:
```bash
curl -X POST "$API_BASE/api/send-crm-notification" -H "Content-Type: application/json" -d '{"title":"Hello","message":"World"}'
```

### GET /api/user
- Returns the authenticated user.
- Auth: Sanctum

```bash
curl -H "Authorization: Bearer $TOKEN" "$API_BASE/api/user"
```

### POST /api/health-check
- Basic health check.

```bash
curl -X POST "$API_BASE/api/health-check"
```

### POST /api/whatsapp/recieved-new
- Verifies webhook via `verify_token`.

```bash
curl -X POST "$API_BASE/api/whatsapp/recieved-new" -H 'Content-Type: application/json' -d '{"verify_token":"<token>"}'
```

### POST /api/download/files
- Initiates a file download by reference.

```bash
curl -X POST "$API_BASE/api/download/files" -H 'Content-Type: application/json' -d '{"path":"/path/to/file"}'
```

### POST /api/upload-image
- Uploads an image.
- Auth: Sanctum

```bash
curl -X POST "$API_BASE/api/upload-image" \
  -H "Authorization: Bearer $TOKEN" \
  -F file=@/path/to/image.jpg
```

### POST /api/save-push-token
- Stores a device push token.
- Auth: Sanctum

```bash
curl -X POST "$API_BASE/api/save-push-token" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"token":"<push-token>"}'
```

### POST /api/send-notification
- Sends a notification to the authenticated user.
- Auth: Sanctum

### POST /api/update-profile-image
- Updates the authenticated staff profile image.
- Auth: Sanctum

