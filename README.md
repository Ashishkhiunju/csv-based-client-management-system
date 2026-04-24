<!-- <p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p> -->

# CSV Based Client Management System

## Project overview and features implemented

This is a Laravel (jeroennoten / Laravel-AdminLTE) admin panel app to manage clients stored in a database, with CSV import/export support.

### Features

- Authentication (Laravel UI) with AdminLTE UI
- Client management (CRUD)
- DataTables listing (Yajra DataTables)
- CSV import with:
  -    ⚫Validation (Form Request)
  -    ⚫Duplicate detection (file + database)
  -    ⚫Review screen + confirm/cancel flow
- CSV export (streamed)
- Test runner page (local-only) to run selected Unit/Feature tests and show output in the UI

## Prerequisites

- PHP **8.2+**
- Composer
- Database: MySQL
- Node.js + npm (for Vite assets)
- Node **20.0+**
- Npm **10.8+**

## Step-by-step installation instructions

```bash
git-repo https://github.com/Ashishkhiunju/csv-based-client-management-system.git
git clone <your-repo-url>
cd csv-based-client-management-system
composer install
copy .env.example .env
php artisan key:generate
```

## Database setup and migration commands

1) Update `.env`:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

2) Run migrations:

```bash
php artisan migrate
```

## Frontend assets (Vite)

```bash
npm install
npm run build
```

For development (hot reload):

```bash
npm run dev
```

## How to run the application

Using Laravel built-in server:

```bash
php artisan serve
```

Then open the app in your browser (example): `http://127.0.0.1:8000`

### Demo login

- Username: `Administrator@admin.com`
- Password: `administrator`

## How to run tests

Run everything:

```bash
php artisan test
```

Run a single Feature test:

```bash
php artisan test tests/Feature/ClientManagementTest.php
```

Run selected Unit tests:

```bash
php artisan test tests/Unit/ClientCsvServiceTest.php tests/Unit/CsvTemplatesTest.php tests/Unit/ImportClientsRequestTest.php
```

### Notes about test database

- The UI test-runner page runs tests with SQLite overrides (so it does not require MySQL to be running).

## API documentation / Postman

This project has a small API in `routes/api.php` (prefix: `/api`).

### Routes (requires `auth:sanctum`)

#### Public

<!-- - `POST /api/register` -->
- `POST /api/login`

#### Protected 

- `GET /api/clients`
- `POST /api/logout`


