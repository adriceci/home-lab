<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Home Lab

A Laravel application for managing and monitoring a home lab environment with security scanning, torrent search, and comprehensive audit logging.

## Installation

### Prerequisites

-   PHP >= 8.2
-   Composer
-   Node.js & npm
-   Docker & Docker Compose (optional, for containerized setup)

### Setup

1. **Clone the repository:**

    ```bash
    git clone <repository-url>
    cd home-lab
    ```

2. **Install PHP dependencies:**

    ```bash
    composer install
    ```

    Or install Audit Center separately:

    ```bash
    composer require adriceci/audit-center
    ```

3. **Publish Audit Center assets:**

    This will publish:
    - Configuration file (`config/audit-center.php`)
    - Database migrations
    - Vue.js components (source files)

    ```bash
    php artisan vendor:publish --provider="AdriCeci\AuditCenter\Providers\AuditCenterServiceProvider"
    php artisan vendor:publish --tag=audit-center-vue-source
    ```

4. **Run migrations:**

    ```bash
    php artisan migrate
    ```

5. **Configure the middleware (if needed):**

    The Audit Center middleware can be auto-registered. Check `config/audit-center.php` to enable it:

    ```php
    'middleware' => [
        'auto_register' => true, // Set to true to auto-register middleware
        // ...
    ],
    ```

    Or manually register it in `bootstrap/app.php`:

    ```php
    use AdriCeci\AuditCenter\Http\Middleware\AuditLogMiddleware;
    
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            AuditLogMiddleware::class,
        ]);
    })
    ```

6. **Configure Vue.js frontend:**

    In `resources/js/app.js`, configure the Audit Center:

    ```javascript
    import ApiService from "@/services/apiService";

    window.auditCenterConfig = {
        apiPrefix: '/api/audit-logs',
        apiService: ApiService,
    };
    ```

7. **Install JavaScript dependencies:**

    ```bash
    npm install
    ```

8. **Build assets:**
    ```bash
    npm run build
    ```

### Docker Setup

The package is automatically installed via Composer from Packagist. Simply run:

```bash
docker-compose up --build
```

Or use the alias:

```bash
homelab-restart
```

## Using Audit Center

Once installed, Audit Center provides:

- **Automatic API request logging** via middleware
- **Manual audit logging** in your controllers
- **Vue.js dashboard** for viewing audit logs
- **Statistics and analytics** for audit data

### Manual Logging

You can manually log audit events in your controllers:

```php
use AdriCeci\AuditCenter\Models\AuditLog;

AuditLog::log(
    action: 'custom_action',
    description: 'Description of what happened',
    userId: auth()->id(),
);
```

### Viewing Audit Logs

Navigate to `/audit-logs` in your application to view the Audit Center dashboard. You'll see:
- Statistics cards (total logs, logins, failed logins, active users)
- Filterable audit log table
- Detailed information about each audit entry

### Configuration

Customize Audit Center by editing `config/audit-center.php`:

- Exclude specific routes from logging
- Configure sensitive fields to exclude
- Adjust route prefixes and middleware
- Customize User model reference

For more details, see the [Audit Center documentation](https://packagist.org/packages/adriceci/audit-center).

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com)**
-   **[Tighten Co.](https://tighten.co)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Redberry](https://redberry.international/laravel-development)**
-   **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
