## Middleware and Requests

### EnsureEmailIsVerified
- Path: `app/Http/Middleware/EnsureEmailIsVerified.php`
- Behavior: Blocks requests if user is unauthenticated or has not verified email; returns 409 JSON `{ message: 'Your email address is not verified.' }`.

Usage:
- Apply to routes/groups needing email verification.

### LoginRequest (FormRequest)
- Path: `app/Http/Requests/Auth/LoginRequest.php`
- Rules: `email` required email, `password` required string
- Methods:
  - `authenticate()`: handles login attempt with rate limiting (5 attempts)
  - `ensureIsNotRateLimited()`: throws `ValidationException` with throttle timing
  - `throttleKey()`: email|ip derived key

Example controller usage:
```php
public function login(LoginRequest $request) {
    $request->authenticate();
    $request->session()->regenerate();
    return response()->json(['message' => 'Logged in']);
}
```

