# HOYOS — Gen Z Streetwear & Dark Gothic Fashion

HOYOS is an e-commerce platform blending early-2000s Y2K nostalgia, modern streetwear, and dark gothic aesthetics with demon-inspired elements. Built on Laravel 12, Tailwind CSS, Alpine.js, and Vite.

---

## System Requirements & Prerequisites

- **PHP**: 8.2 or higher (with `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `gd` extensions)
- **Database**: PostgreSQL 16+ (or SQLite 3.35+ for lightweight local testing)
- **Node.js**: 18.x or higher & npm
- **Composer**: 2.x

---

## Getting Started & Setup

Follow these steps to set up and run HOYOS locally:

### 1. Install Dependencies
```bash
# Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Frontend setup & build
cd ../frontend
npm install
npm run build
```

Configure your PostgreSQL database connection in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hoyos
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

#### Specialized HOYOS Environment Configuration Keys
Add or customize these application configuration keys in `.env`:
```env
# Grace period in minutes after 5-min QR expiry where customer can retry before auto-cancellation
HOYOS_PAYMENT_GRACE_MINUTES=15

# Enable sandbox payment simulator endpoints (/payment/{order}/simulate). Defaults to true ONLY in local/testing
HOYOS_ALLOW_PAYMENT_SIMULATION=true

# Timezone used for daily, weekly, and monthly aggregation grouping in admin reports
HOYOS_REPORT_TIMEZONE=Asia/Phnom_Penh

# Stock quantity threshold below which alerts are displayed on the admin dashboard and inventory table
HOYOS_LOW_STOCK_THRESHOLD=5
```

### 3. Database Migration & Seeding
Run database migrations and seed default products, categories, and test accounts:
```bash
php artisan migrate
php artisan db:seed
```
> ⚠️ **CAUTION**: Never run `migrate:fresh`, `migrate:reset`, or `db:wipe` on a production or staging database. All schema updates are strictly additive and reversible.

### 4. Create Public Storage Symlink (Critical)
Connect `public/storage` to `storage/app/public` so product photography and WebP assets are served directly:
```bash
php artisan storage:link
```

### 5. Running the Background Task Scheduler
HOYOS requires the scheduler to run in the background to automatically check unpaid orders past their 15-minute grace period (`hoyos:check-payment-expiries`), cancel them, restore variant stock, and log audit entries.

- **For Local Development**:
  ```bash
  php artisan schedule:work
  ```
- **For Production (Crontab)**:
  Add the following entry to your production server crontab (`crontab -e`):
  ```bash
  * * * * * cd /path/to/hoyos && php artisan schedule:run >> /dev/null 2>&1
  ```

### 6. Creating an Administrator Account
To create a dedicated administrator user via CLI:
```bash
php artisan tinker --execute="App\Models\User::factory()->admin()->create(['email' => 'admin@hoyos.com', 'name' => 'Store Admin', 'password' => bcrypt('SecurePassword123!')]);"
```
Or grant the admin role to an existing registered user:
```bash
php artisan tinker --execute="App\Models\User::where('email', 'user@hoyos.com')->update(['role' => 'admin', 'account_status' => 'active']);"
```

### 7. Start the Development Server
```bash
php artisan serve
```
Visit the storefront at [http://127.0.0.1:8000](http://127.0.0.1:8000) and the backoffice at [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin).

---

## Seeded Demo Accounts

The database seeder automatically provides credentials for testing both store manager and customer flows:

| Role | Email | Password | Features / Notes |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@hoyos.com` | `password` | Access to `/admin/shipments` to input J&T tracking codes and update dispatch statuses |
| **Customer** | `customer@hoyos.com` | `password` | Access to `/account`, `/my-orders`, saved addresses, and pre-seeded order `#HOYOS-000123` |

### Pre-Seeded Sample Order
- **Order Number**: `HOYOS-000123`
- **Carrier**: J&T Express
- **Tracking Code**: `JT123456789KH`
- **Guest Access URL**: `/orders/HOYOS-000123?token=sample-guest-token-hoyos-000123`
- **Tracking URL**: `/orders/HOYOS-000123/track?token=sample-guest-token-hoyos-000123`

### ⚠️ Before Deploying
> **Security Checklist**:
> 1. **Change Seeded Passwords**: Update or rotate default credentials for `admin@hoyos.com` and `customer@hoyos.com` to strong, unique passwords.
> 2. **Remove Demo Login Route**: The demo login routes (`/auth/demo` and `/auth/google`) are automatically guarded by `app()->environment('local')` and hidden outside local environments. Ensure `APP_ENV=production` is set in `.env`, or remove the demo login closure from `routes/auth.php` entirely before public release.

---

## Product Image Processing Pipeline

HOYOS includes an automated fashion image pipeline that generates consistent 4:5 (1200x1500) WebP crops with dark radial gradient backgrounds (#2a0006 to #0a0003), foot glows, and distinct `-front`, `-detail` (tight torso/fabric crop), and `-full` framing.

### 1. Local Import Mode
1. Place source photos in `storage/app/imports/{slug}.{jpg,png,webp}` matching the product slug.
2. (Optional) Provide `storage/app/imports/credits.csv` with columns `slug,photographer,source_url`.
3. Run the import command:
```bash
php artisan products:fetch-images --source=local --force
```

### 2. Pexels API Mode
Set your API key in `.env`:
```env
PEXELS_API_KEY=your_pexels_api_key
```
Run the automated search and download command:
```bash
php artisan products:fetch-images --source=api --force
```

### 3. Standalone Python Background Removal & Composition
The post-processing script uses `rembg` (with `pymatting` fallback) and Pillow:
```bash
python scripts/process_product_image.py <input_file> <output_front.webp> <output_detail.webp> <output_full.webp>
```

## Automated Testing & Visual Regression

### Feature & Unit Tests

The test suite covers customer journeys, guest checkout, payment simulation, 15-minute grace period expiry, admin backoffice RBAC, inventory ledgers, and live analytics.

- **PostgreSQL Test Suite (`hoyos_test`)**:
  Execute tests against the dedicated PostgreSQL test database:
  ```bash
  php artisan test -c phpunit.pgsql.xml
  ```
  *(A hard safety guard in `tests/TestCase.php` prevents test execution if connected to the live `hoyos` database).*

- **In-Memory SQLite Fast Test Suite**:
  Run fast unit/feature tests against in-memory SQLite:
  ```bash
  php artisan test
  ```

### Visual Regression Screenshots
Capture high-resolution 1400px desktop and 390px mobile viewport screenshots of key customer journeys:
```bash
node scripts/take_audit_screenshots.js
```
Screenshots are written to `storage/app/screenshots/`.
