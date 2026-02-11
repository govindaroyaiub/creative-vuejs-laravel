# 🎯 Laravel Pulse - Setup Complete!

Laravel Pulse is now installed and monitoring your application in real-time!

## ✅ What's Configured

- ✅ Package installed (`laravel/pulse`)
- ✅ Database tables created
- ✅ **Permission-based access** - Only users with `'pulse'` permission can view
- ✅ Dashboard available at `/pulse`
- ✅ All recorders enabled and ready

---

## 🔐 Granting Access

Only users with the `'pulse'` permission in their permissions array can access the dashboard.

### Grant Access to a User:

```bash
php artisan tinker
```

```php
$user = User::find(YOUR_USER_ID);
$user->permissions = array_merge($user->permissions ?? [], ['pulse']);
$user->save();
```

---

## 📊 Accessing the Dashboard

1. **Grant yourself access** (see above)
2. **Visit**: `http://localhost:8000/pulse`
3. **View real-time metrics** of your application!

---

## 🎯 What Pulse Monitors

### Real-Time Metrics:

- **Slow Requests** - HTTP requests taking longer than 1 second
- **Slow Queries** - Database queries over 1 second
- **Slow Jobs** - Queue jobs taking too long
- **Exceptions** - Application errors and stack traces
- **Cache Performance** - Hit rates and interactions
- **Queue Monitoring** - Job throughput and failures
- **Server Resources** - CPU and memory usage
- **User Activity** - Active users and their requests
- **Outgoing Requests** - External API calls performance

### Perfect for Your System:

- Monitor video processing performance (FFmpeg jobs)
- Track image optimization delays (Tinify)
- Identify slow database queries for previews/categories
- Watch notification broadcast performance (Reverb)
- Catch errors before users report them
- Monitor server health during peak loads

---

## 🚀 How to Use

### No Configuration Needed!

Pulse automatically starts recording data as soon as you:

1. Visit the dashboard once
2. Use your application normally

### View Data:

- **Live Updates** - Dashboard refreshes automatically
- **Historical Data** - Keeps last 7 days by default
- **Drill Down** - Click on any metric to see details

---

## ⚙️ Configuration (Optional)

### Adjust Thresholds in `.env`:

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

### Edit `config/pulse.php` for advanced options:

- Ignore specific routes or jobs
- Group similar queries
- Configure storage drivers
- Customize recorders

---

## 📈 Production Tips

### 1. **Run Pulse Worker** (Recommended for Production)

Instead of storing data inline with requests:

```bash
php artisan pulse:work
```

This offloads data collection to a background worker for better performance.

### 2. **Add to Supervisor** (Production)

```ini
[program:pulse]
command=php /path/to/your/app/artisan pulse:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/pulse.log
```

### 3. **Sampling in Production**

Reduce overhead by sampling (e.g., record only 10% of requests):

```env
PULSE_SLOW_REQUESTS_SAMPLE_RATE=0.1
PULSE_SLOW_QUERIES_SAMPLE_RATE=0.1
```

---

## 🎨 Dashboard Features

### Tabs Available:

- **Usage** - Active users, requests, jobs
- **Servers** - CPU, memory, storage for each server
- **Exceptions** - Recent errors with stack traces
- **Queues** - Job processing rates and failures
- **Slow Queries** - Problematic database queries
- **Slow Requests** - HTTP requests needing optimization
- **Slow Jobs** - Background jobs taking too long
- **Slow Outgoing Requests** - External API delays
- **Cache** - Hit rates and key usage

### Each Card Shows:

- Real-time graphs
- Top offenders
- Aggregated statistics
- Time period filters

---

## 🔍 Common Use Cases

### Find Slow Pages:

1. Go to "Slow Requests" tab
2. See which routes are slow
3. Click to see execution breakdown

### Debug Database Performance:

1. Go to "Slow Queries" tab
2. Identify N+1 queries or missing indexes
3. See exactly where they're called from

### Monitor Queue Health:

1. Go to "Queues" tab
2. Watch throughput in real-time
3. Catch failing jobs immediately

### Track User Activity:

1. Go to "Usage" tab
2. See active users and their requests
3. Identify usage patterns

---

## 🛡️ Security

- ✅ Protected by authentication
- ✅ Only users with `canAccess('pulse')` can view
- ✅ No public access
- ✅ Same permission system as your other features

---

## 💡 Quick Tips

1. **Check it daily** - Pulse helps catch issues early
2. **After deployments** - Watch for new slow queries or errors
3. **Optimize queries** - Use Pulse data to find N+1 problems
4. **Monitor resources** - Ensure server isn't overloaded
5. **Track improvements** - See if your optimizations work

---

## 🎯 Next Steps

1. ✅ **Grant yourself access** to test the dashboard
2. ✅ **Visit** `/pulse` to see it in action
3. ✅ **Use your app** normally to generate some metrics
4. ✅ **Add Pulse access** to your user management UI
5. ⚙️ **Optional**: Run `php artisan pulse:work` in background

---

## 🚨 Troubleshooting

### No data showing?

- Use your application to generate some traffic
- Wait a few seconds for data to aggregate
- Check that recorders are enabled in `config/pulse.php`

### Dashboard won't load?

- Ensure you granted yourself `'pulse'` permission
- Check that you're logged in
- Clear config cache: `php artisan config:clear`

### High database load?

- Consider running `pulse:work` in background
- Reduce sample rates in production
- Increase aggregation intervals

---

Pulse is ready! Visit `/pulse` to start monitoring your application. 🎯
