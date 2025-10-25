# Cache Management System

## Overview

The automated cache management system provides comprehensive cleanup functionality for your Laravel application with a beautiful dashboard interface and scheduled automation.

## Features

### 🧹 Automated Cleanup Types

- **Laravel Cache**: Application cache, config cache, route cache, view cache
- **Storage Temp**: Temporary files older than 24 hours
- **Logs**: Log files older than 7 days
- **Temp Uploads**: Upload temporary files older than 12 hours
- **Preview Images**: Preview images older than 30 days
- **All**: Complete system cleanup

### 📊 Dashboard Features

- Real-time cache statistics
- Manual cleanup controls
- Recent activity tracking
- System information display
- Disk usage monitoring

### ⏰ Scheduled Automation

- Daily cleanup at 4:30 AM (configurable)
- Multiple cleanup types available
- Configurable frequency (daily/weekly/monthly)
- Background processing with logging

## Usage

### Dashboard Access

Navigate to `/cache-management` to access the dashboard

### Manual Cleanup

Use the quick action buttons on the dashboard to run specific cleanup types:

- **Clean All**: Complete system cleanup
- **Laravel Cache**: Framework-specific cache only
- **Storage**: Temporary storage files
- **Logs**: Old log files
- **Temp Files**: Upload temporary files

### Command Line Usage

```bash
# Run complete cleanup
php artisan cache:auto-cleanup

# Run specific cleanup types
php artisan cache:auto-cleanup --type=laravel
php artisan cache:auto-cleanup --type=storage
php artisan cache:auto-cleanup --type=logs
php artisan cache:auto-cleanup --type=temp
```

### Schedule Configuration

1. Access **Schedule Settings** from the dashboard
2. Enable/disable automatic cleanup
3. Set preferred time (server timezone)
4. Choose cleanup type and frequency
5. Save configuration

## File Structure

```
app/
├── Console/Commands/
│   └── AutoCacheCleanup.php          # Main cleanup command
├── Http/Controllers/
│   └── CacheManagementController.php  # Dashboard controller
resources/js/Pages/CacheManagement/
├── Dashboard.vue                      # Main dashboard interface
└── Schedule.vue                       # Schedule configuration
bootstrap/
└── app.php                           # Scheduled task registration
routes/
└── web.php                           # Cache management routes
```

## Automatic Scheduling

The system automatically runs daily cleanup at 4:30 AM server time. This is configured in `bootstrap/app.php`:

```php
$schedule->command('cache:auto-cleanup --type=all')
    ->dailyAt('04:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cache_cleanup_cron.log'));
```

## Logs and Monitoring

### Cleanup Logs

- **Location**: `storage/logs/cache_cleanup.log`
- **Format**: JSON entries with cleanup statistics
- **Includes**: Timestamp, file counts, size freed, cleanup duration

### Cron Logs

- **Location**: `storage/logs/cache_cleanup_cron.log`
- **Content**: Output from scheduled cleanup runs

### Dashboard Monitoring

- Real-time statistics display
- Recent cleanup activity
- System disk usage
- Server information

## Security

- Protected by authentication middleware
- User permission checking via `CheckUserPermission`
- CSRF protection on all POST requests
- Background processing prevents UI blocking

## Customization

### Adding New Cleanup Types

1. Add cleanup method to `AutoCacheCleanup` command
2. Update validation rules in controller
3. Add UI option in dashboard components

### Modifying Schedule

- Update `bootstrap/app.php` for code-level changes
- Use dashboard interface for user-configurable settings
- Schedule settings stored in `storage/app/cache_schedule.json`

## Troubleshooting

### Command Not Found

Ensure the command is registered by running:

```bash
php artisan list | grep cache
```

### Permission Issues

Check file permissions on storage directories:

```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### Schedule Not Running

Verify cron is configured to run Laravel's scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Dashboard Access Issues

- Ensure routes are registered
- Check user permissions
- Verify controller exists and is working

## Performance Notes

- Cleanup runs in background to prevent UI blocking
- File operations use Laravel's optimized file handling
- Statistics are cached for dashboard performance
- Large cleanups are logged for monitoring

## Support

For issues or feature requests, check the application logs and verify all components are properly installed and configured.
