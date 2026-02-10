# Laravel Reverb Configuration Instructions

## Add these lines to your .env file:

```env
# Broadcasting Configuration
BROADCAST_CONNECTION=reverb
VITE_BROADCAST_CONNECTION=reverb

# Reverb Server Configuration
REVERB_APP_ID=creative-app
REVERB_APP_KEY=creative-key
REVERB_APP_SECRET=creative-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Reverb Frontend Configuration (for Vite)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Start the Reverb WebSocket Server:

```bash
php artisan reverb:start
```

## Build Frontend Assets:

```bash
npm run dev
# or for production
npm run build
```

## Testing Real-time Notifications:

1. Open two browser windows/tabs with different users logged in
2. Perform an action that creates a notification (e.g., create a preview, approve feedback)
3. The other user should receive the notification instantly without refreshing!

## Production Deployment:

For production, you should:

1. Run Reverb as a background service using supervisor or systemd
2. Use HTTPS (set REVERB_SCHEME=https)
3. Consider using a reverse proxy like Nginx

Example supervisor configuration:

```ini
[program:reverb]
command=php /path/to/your/app/artisan reverb:start
directory=/path/to/your/app
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

## Troubleshooting:

- **Connection refused**: Make sure Reverb server is running (`php artisan reverb:start`)
- **401 Unauthorized**: Check that user is logged in and channels.php authorization is correct
- **No notifications**: Check browser console for WebSocket connection errors
- **Port conflict**: Change REVERB_PORT to a different port if 8080 is already in use
