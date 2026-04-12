# Getting Started

## Requirements

- PHP 8.3+
- Composer
- Node.js + npm
- MySQL
- Redis

## First Run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

## Environment Setup

Set at minimum the following in `.env`:

```env
APP_URL=http://localhost:8000
DB_DATABASE=saas_starter

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

## Run Locally

```bash
composer run dev
```

This typically starts the Laravel app, queue worker, logs, and Vite workflow defined by the project.
