# Crewly

Crewly is a Laravel 10 application using Inertia.js + React, built with Vite and styled with Tailwind CSS.

## Tech Stack

- PHP ^8.1
- Laravel ^10
- Inertia.js (Laravel adapter) + React 18
- Vite 4
- Tailwind CSS + PostCSS + Autoprefixer
- Laravel Sanctum (API auth)

## Requirements

- PHP 8.1+
- Composer
- Node.js + npm
- A database (MySQL/MariaDB recommended for local Laragon installs)

## Local Setup

1) Install PHP dependencies:

```bash
composer install
```

2) Create environment file and app key:

```bash
copy .env.example .env
php artisan key:generate
```

On macOS/Linux:

```bash
cp .env.example .env
php artisan key:generate
```

3) Configure `.env`:

- `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

4) Run migrations and seed the base data:

```bash
php artisan migrate --seed
```

By default this runs these seeders (see `database/seeders/DatabaseSeeder.php`):

- Roles (`admin`, `hr`, `manager`)
- Default users (see `database/seeders/UserSeeder.php`)
- Departments
- Leave Types

You can control the seeded users' password via:

```env
SEED_DEFAULT_PASSWORD=password
```

5) Install JS dependencies:

```bash
npm install
```

## Run (Development)

Start Laravel:

```bash
php artisan serve
```

Start Vite (assets + HMR):

```bash
npm run dev
```

## Build (Production Assets)

```bash
npm run build
```

This outputs versioned assets to `public/build`.

## Authorization / Roles

Routes are protected using `can:*` middleware.

Seeded roles (see `DatabaseSeeder` / `RoleSeeder`):

- `admin`
- `hr`
- `manager`

Gate rules are defined in `app/Providers/AuthServiceProvider.php`.

## Leaves Module

Routes are registered in `routes/modules/leaves.php` and loaded from `routes/web.php`.

- **Leave Types**
	- Admin/HR can create and update leave types.
	- Leave Type creation/edit is implemented with a modal in the UI.

- **Leave Requests**
	- Admin/HR can file leave requests.
	- Admin/HR/Manager can approve/deny requests.
	- The UI uses a modal for creating requests.

**Validation / Business Rules**

- Overlap is prevented against already-approved leave requests.
- Some Inertia form submissions will return a `302` on validation failure (expected for Laravel). The modal workflow preserves state and re-opens to show validation errors.

## Seeding

- Seed everything (roles/users/departments/leave types):

```bash
php artisan migrate --seed
```

- Seed leave types only:

```bash
php artisan db:seed --class=Database\\Seeders\\LeaveTypeSeeder
```

### Optional: Developer Bypass (Local Only)

When enabled, users with an email listed in `APP_DEVELOPER_EMAILS` bypass all Gate checks.

Add to `.env` (keep disabled in production):

```env
APP_DEVELOPER_BYPASS=false
APP_DEVELOPER_EMAILS=you@example.com,other@example.com
```

## Frontend Notes

- The sidebar and various UI elements rely on a globally shared Inertia prop named `can` (permission map). Avoid using `can` as a page prop; use a different name (for example: `actions`) to prevent collisions.

## Testing

```bash
php artisan test
```

or

```bash
vendor/bin/phpunit
```

## Notes (Windows / PowerShell)

- If you see `npm.ps1 cannot be loaded because running scripts is disabled`, run npm via:

```bash
npm.cmd run build
```

- This repo sets `"type": "module"` in `package.json` so Node treats the Vite/Tailwind/PostCSS config files as ES modules (prevents module-type warnings during builds).
