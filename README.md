# Secure Complaint Management System

Project foundation for a secure web-based complaint management system with AES-256 data encryption and application-layer intrusion prevention planned for later modules.

## Stack

- Laravel 13
- Laravel Sanctum
- PostgreSQL
- Vue 3
- PrimeVue
- Pinia
- Vue Router
- Axios
- Vite

## Current Foundation

- Laravel application initialized with Sanctum installed.
- PostgreSQL environment defaults configured in `.env` and `.env.example`.
- Local file/sync session, cache, and queue defaults so the foundation can run before PostgreSQL is created.
- Sanctum API authentication route available at `/api/user`.
- Vue 3 SPA mounted through `resources/views/app.blade.php`.
- PrimeVue, Pinia, Vue Router, Axios, and Vite configured.
- Layouts scaffolded for app and auth pages.
- Role-based frontend route placeholders scaffolded for admin, staff, complainant, and security users.
- Initial landing page created.

## Setup

Install dependencies:

```bash
composer install
npm install
```

Create and configure the environment:

```bash
cp .env.example .env
php artisan key:generate
```

Create a PostgreSQL database named `complaint_management`, then update these values in `.env` if your local credentials differ:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=complaint_management
DB_USERNAME=postgres
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

Start development servers:

```bash
composer run dev
```

Or run them separately:

```bash
php artisan serve
npm run dev
```

## Verification

Build frontend assets:

```bash
npm run build
```

List backend routes:

```bash
php artisan route:list
```

## Scope Notes

Business modules are intentionally not implemented yet. Encryption services, complaint workflows, reporting, and intrusion prevention logic should be added after this foundation is approved.
