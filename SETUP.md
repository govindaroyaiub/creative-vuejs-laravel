# 🛠️ Setup Guide

Complete setup instructions for the Creative Vue.js Laravel application, including base installation, Laravel Reverb (real-time notifications), and Laravel Pulse (system monitoring).

---

## ⚙️ Prerequisites

- **PHP**: 8.2+
- **Node.js**: 18.x+
- **Composer**: Latest version
- **Database**: MySQL or PostgreSQL
- **Web Server**: Nginx or Apache
- **Redis**: For caching and sessions (recommended for production)

---

## 🚀 Base Installation

### 1. Clone the repository
```bash
git clone https://github.com/govindaroyaiub/creative-vuejs-laravel.git
cd creative-vuejs-laravel
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Install Node.js dependencies
```bash
npm install
```

### 4. Set up environment
```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure database and Redis in `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=creative_laravel
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 6. Run database migrations
```bash
php artisan migrate
```

### 7. Build assets
```bash
npm run build
```

### 8. Start development server
```bash
# Terminal 1: Laravel development server
composer run dev

# Terminal 2: Reverb WebSocket server (for real-time notifications)
php artisan reverb:start
```

---

## 📡 Laravel Reverb (Real-Time Notifications)

### Add these lines to your `.env` file:

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

### Start the Reverb WebSocket Server:

```bash
php artisan reverb:start
```

### Build Frontend Assets:

```bash
npm run dev
# or for production
npm run build
```

### Testing Real-time Notifications:

1. Open two browser windows/tabs with different users logged in
2. Perform an action that creates a notification (e.g., create a preview, approve feedback)
3. The other user should receive the notification instantly without refreshing!

### Production Deployment:

For production, you should:

1. Run Reverb as a background service using supervisor or systemd
2. Use HTTPS (set `REVERB_SCHEME=https`)
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

### Reverb Troubleshooting:

- **Connection refused**: Make sure Reverb server is running (`php artisan reverb:start`)
- **401 Unauthorized**: Check that user is logged in and `channels.php` authorization is correct
- **No notifications**: Check browser console for WebSocket connection errors
- **Port conflict**: Change `REVERB_PORT` to a different port if 8080 is already in use

---

## 🎯 Laravel Pulse (System Monitoring)

Laravel Pulse monitors your application in real-time. The package is installed, database tables are created, and the dashboard is available at `/pulse`.

### 🔐 Granting Access

Only users with the `'pulse'` permission in their permissions array can access the dashboard.

```bash
php artisan tinker
```

```php
$user = User::find(YOUR_USER_ID);
$user->permissions = array_merge($user->permissions ?? [], ['pulse']);
$user->save();
```

### 📊 Accessing the Dashboard

1. **Grant yourself access** (see above)
2. **Visit**: `http://localhost:8000/pulse`
3. **View real-time metrics** of your application!

### 🎯 What Pulse Monitors

- **Slow Requests** - HTTP requests taking longer than 1 second
- **Slow Queries** - Database queries over 1 second
- **Slow Jobs** - Queue jobs taking too long
- **Exceptions** - Application errors and stack traces
- **Cache Performance** - Hit rates and interactions
- **Queue Monitoring** - Job throughput and failures
- **Server Resources** - CPU and memory usage
- **User Activity** - Active users and their requests
- **Outgoing Requests** - External API calls performance

### ⚙️ Configuration (Optional)

Adjust thresholds in `.env`:

```env
# What counts as "slow"
PULSE_SLOW_REQUESTS_THRESHOLD=1000  # 1 second
PULSE_SLOW_QUERIES_THRESHOLD=1000   # 1 second
PULSE_SLOW_JOBS_THRESHOLD=1000      # 1 second

# Data retention
PULSE_STORAGE_KEEP="7 days"

# Sampling (reduce overhead in production)
PULSE_SLOW_REQUESTS_SAMPLE_RATE=1  # 1 = 100%, 0.5 = 50%
```

Edit `config/pulse.php` for advanced options:

- Ignore specific routes or jobs
- Group similar queries
- Configure storage drivers
- Customize recorders

### 📈 Production Tips

#### 1. Run Pulse Worker (Recommended for Production)

Instead of storing data inline with requests:

```bash
php artisan pulse:work
```

This offloads data collection to a background worker for better performance.

#### 2. Add to Supervisor (Production)

```ini
[program:pulse]
command=php /path/to/your/app/artisan pulse:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/pulse.log
```

#### 3. Sampling in Production

Reduce overhead by sampling (e.g., record only 10% of requests):

```env
PULSE_SLOW_REQUESTS_SAMPLE_RATE=0.1
PULSE_SLOW_QUERIES_SAMPLE_RATE=0.1
```

### 🛡️ Pulse Security

- Protected by authentication
- Only users with `canAccess('pulse')` can view
- No public access
- Same permission system as your other features

### 🚨 Pulse Troubleshooting

**No data showing?**
- Use your application to generate some traffic
- Wait a few seconds for data to aggregate
- Check that recorders are enabled in `config/pulse.php`

**Dashboard won't load?**
- Ensure you granted yourself `'pulse'` permission
- Check that you're logged in
- Clear config cache: `php artisan config:clear`

**High database load?**
- Consider running `pulse:work` in background
- Reduce sample rates in production
- Increase aggregation intervals

---

## 📦 Production Deployment

For production deployment:

1. Set `APP_ENV=production` in `.env`
2. Configure proper web server (Nginx/Apache) to serve `/public` directory
3. Set up queue workers (database or Redis)
4. Configure Supervisor for Laravel worker processes:
   ```ini
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=5
   redirect_stderr=true
   stdout_logfile=/path/to/your/app/storage/logs/worker.log
   ```
5. Set up Reverb and Pulse workers with Supervisor (see sections above)
6. Ensure proper file permissions for storage and bootstrap/cache directories
7. Enable SSL/TLS for secure connections

---

## 🛠️ Maintenance Commands

```bash
# Clear all caches
php artisan optimize:clear

# Generate database optimization report
php artisan db:optimization-report

# Monitor cache usage
php artisan cache:monitor

# View system logs
php artisan log:viewer

# Run Pulse worker (for production monitoring)
php artisan pulse:work

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh database with seeders
php artisan migrate:fresh --seed
```
