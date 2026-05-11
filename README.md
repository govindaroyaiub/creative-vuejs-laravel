# 🎨 Creative Vue.js Laravel Application

A comprehensive creative project management platform built with Laravel + Vue.js for agencies and creative teams.

## 📋 Overview

This application provides a complete solution for managing client projects, creative assets, team collaboration, and business operations including:
- Preview management system
- Content management (banners, videos, GIFs, social media)
- Client & project management
- File transfer system with 3D interface
- Billing & invoicing
- Media library
- Real-time notifications via Laravel Reverb
- System monitoring with Laravel Pulse
- API documentation

## ⚙️ Prerequisites

- **PHP**: 8.2+
- **Node.js**: 18.x+
- **Composer**: Latest version
- **Database**: MySQL or PostgreSQL
- **Web Server**: Nginx or Apache
- **Redis**: For caching and sessions (recommended for production)

## 🚀 Installation

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

# Laravel Reverb for real-time notifications
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
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

### 9. (Optional) Start Pulse monitoring worker
For production performance monitoring:
```bash
php artisan pulse:work
```

## 🔧 Configuration

### Environment Variables
Key variables to set in `.env`:
- Database connection details
- Redis configuration
- Laravel Reverb settings for WebSocket notifications
- Pulse monitoring thresholds (optional)

### Permissions
The system uses Laravel's gate/permission system:
- Grant specific permissions to users for accessing features like Pulse dashboard
- Use `php artisan tinker` to assign permissions:
  ```php
  $user = User::find(YOUR_USER_ID);
  $user->permissions = array_merge($user->permissions ?? [], ['pulse']);
  $user->save();
  ```

## 📊 Features

### Core Functionality
- **Preview Management**: Branded client portals with hierarchical organization
- **Content Management**: Banners, videos, GIFs, social media processing
- **Client Portal**: Dedicated spaces per client with role-based access
- **File Transfer**: Secure sharing with immersive 3D interface (Three.js)
- **Billing**: Invoice generation with PDF support
- **Media Library**: Centralized asset management
- **Notifications**: Real-time updates via WebSocket with smart batching

### System Features
- **Cache Management**: Storage analytics and optimization tools
- **Log Viewer**: Real-time log monitoring and management
- **API Documentation**: Interactive documentation interface
- **Pulse Monitoring**: Performance monitoring dashboard (at `/pulse`)

## 🌐 Access

After installation:
- Application: `http://localhost:8000`
- Pulse Dashboard: `http://localhost:8000/pulse` (requires 'pulse' permission)
- API Documentation: `http://localhost:8000/lazyDoc`

## 🔐 Security

- Authentication protected routes
- Role-based permission system
- Secure file handling
- Environment-based configuration
- CSRF protection
- Input validation and sanitization

## 📈 Performance

- Database indexing on critical tables
- Redis caching configuration
- TypeScript strict mode for frontend
- Optimized queries with eager loading
- Asset minimization and bundling

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

## 📦 Deployment

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
5. Set up Pulse worker with Supervisor (optional but recommended)
6. Ensure proper file permissions for storage and bootstrap/cache directories
7. Enable SSL/TLS for secure connections

## 🤝 Support

For questions or support, contact: [govindaroy.ofc94@gmail.com](mailto:govindaroy.ofc94@gmail.com)

---

_Built with ❤️ using Laravel, Vue.js, and modern web technologies_