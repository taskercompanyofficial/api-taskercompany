## Overview

This repository provides a Laravel-based backend API for CRM, worker, messaging, and application-facing functionality.

### Base URL

- Local: `http://localhost:8000`
- Production: as configured by your deployment. All routes below are relative to `/api` unless noted.

### Authentication

- Most endpoints require Sanctum Bearer tokens.
- Send `Authorization: Bearer <token>` header on protected routes.
- Rate limits may apply on certain auth endpoints (see `LoginRequest`).

### Content Type

- Use `Content-Type: application/json` unless uploading files (then use `multipart/form-data`).

### Conventions

- Timestamps use ISO-8601 strings.
- Standard REST semantics for `resource`/`apiResource` routes: `index`, `show`, `store`, `update`, `destroy`.

### Quick Auth Example

```bash
curl -X POST "$API_BASE/api/app/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"secret"}'
```

Use the returned token in subsequent requests:

```bash
curl -H "Authorization: Bearer $TOKEN" "$API_BASE/api/user"
```

