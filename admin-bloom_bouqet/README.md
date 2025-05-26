<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Database Migrations

The database migrations have been cleaned and optimized for smooth operation. Key improvements include:

- **Consistent Column Types**: All foreign keys use `unsignedBigInteger` to match primary key types
- **Proper Foreign Key Constraints**: Added after table creation to prevent constraint issues
- **Order Items Integration**: Stored as JSON in orders table for simpler data structure
- **Strategic Indexing**: Added to columns frequently used in queries

To run migrations:
```
php artisan migrate
```

See `database/migrations/README.md` for detailed documentation on the migration structure.

# Bloom Bouquet Admin Panel

This repository contains the admin panel for the Bloom Bouquet e-commerce application.

## Updates and Optimizations

### Database Schema Changes
- Updated all ID columns from `bigint` to `int` for better performance and consistency
- Standardized foreign key constraints using `unsigned int` throughout the application
- Fixed orders table to properly use JSON for order_items
- Fixed carousels table structure

### User Interface Improvements
- Fixed category deletion confirmation modals to ensure proper display
- Added support for deletion with product relocation
- Optimized modal z-index and backdrop management

## Installation

1. Clone this repository
2. Install dependencies: `composer install && npm install`
3. Copy `.env.example` to `.env` and configure your database connection
4. Generate application key: `php artisan key:generate`
5. Run migrations: `php artisan migrate`
6. Seed the database: `php artisan db:seed`
7. Build assets: `npm run dev`
8. Start the development server: `php artisan serve`

## Verification Tools

### Database Column Verification
You can verify that the database columns are using the correct types by running:

```bash
php database/verify_column_types.php
```

This will check all tables and confirm that ID columns are using `int` instead of `bigint`.

## Development

### Migration Best Practices
When creating migrations, follow these guidelines:
- Use `increments('id')` for primary keys (creates an unsigned int)
- Use `integer('column_name')->unsigned()` for foreign keys
- Add foreign key constraints after table creation to prevent constraint issues

Example:
```php
Schema::create('products', function (Blueprint $table) {
    $table->increments('id');
    $table->integer('category_id')->unsigned();
    // Other columns...
    
    // Add indexes
    $table->index('category_id');
});

// Add foreign key constraints after table creation
Schema::table('products', function (Blueprint $table) {
    $table->foreign('category_id')
        ->references('id')
        ->on('categories')
        ->onDelete('cascade');
});
```
