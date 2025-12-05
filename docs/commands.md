## Console Commands

### attendance:create
- Defined by `App\Console\Commands\CreateDailyAttendance`.
- Purpose: Ensure a daily `StaffAttendence` record exists for each staff member and send push reminders.
- Output: Logs status and fires `App\Events\NewNotification`.

Run:
```bash
php artisan attendance:create
```

Scheduled in `routes/console.php` to run every minute:
```php
// routes/console.php
return function (Schedule $schedule) {
    $schedule->command('attendance:create')->everyMinute();
};
```

### generate:keys
- Defined by `App\Console\Commands\GenerateKeys`.
- Purpose: Generate RSA private/public keys at `storage/keys`.

Run:
```bash
php artisan generate:keys
```

