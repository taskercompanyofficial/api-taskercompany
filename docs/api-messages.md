## Messages Endpoints

Defined in `routes/messages-routes.php` under `Route::prefix('messages')`.

### Authenticated (Sanctum)
- apiResource `/api/messages/chat-rooms`

REST actions:
- `GET /api/messages/chat-rooms` — list rooms
- `POST /api/messages/chat-rooms` — create room
- `GET /api/messages/chat-rooms/{id}` — show room
- `PUT/PATCH /api/messages/chat-rooms/{id}` — update room
- `DELETE /api/messages/chat-rooms/{id}` — delete room

Example list:
```bash
curl -H "Authorization: Bearer $TOKEN" "$API_BASE/api/messages/chat-rooms"
```

