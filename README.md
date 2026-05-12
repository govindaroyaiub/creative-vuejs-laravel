# 🎨 Creative Vue.js Laravel Application

A comprehensive creative project management platform built with Laravel + Vue.js for agencies and creative teams.

> 📖 **Looking to install the project?** See [SETUP.md](./SETUP.md) for the full installation and configuration guide.

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

## 🧰 Tech Stack

- **Backend**: Laravel 10+ (PHP 8.2+)
- **Frontend**: Vue.js, TypeScript (strict mode)
- **Real-time**: Laravel Reverb (WebSockets)
- **Monitoring**: Laravel Pulse
- **3D Interface**: Three.js
- **Database**: MySQL / PostgreSQL
- **Caching**: Redis
- **Build Tools**: Vite

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

## 🤝 Support

For questions or support, contact: [govindaroy.ofc94@gmail.com](mailto:govindaroy.ofc94@gmail.com)

---

_Built with ❤️ using Laravel, Vue.js, and modern web technologies_
