## Events and Broadcasting

### App\Events\NewNotification
- Implements `ShouldBroadcastNow`.
- Broadcasts on public channel `notification-channel` as event name `notification-event`.
- Payload fields: `title`, `message`, `type`, `link`, `time`.

Broadcast channels are defined in `routes/channels.php`:
```php
Broadcast::channel('notification-channel', function () {
    return true; // Public channel
});
```

Example client (Laravel Echo):
```js
import Echo from 'laravel-echo'

const echo = new Echo({ broadcaster: 'pusher', key: '<key>', cluster: '<cluster>' })

echo.channel('notification-channel').listen('.notification-event', (e) => {
  console.log('Notification', e)
})
```

