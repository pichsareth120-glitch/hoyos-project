# HOYOS — Gen Z Streetwear & Dark Gothic Fashion

HOYOS is an e-commerce platform blending early-2000s Y2K nostalgia, modern streetwear, and dark gothic aesthetics with demon-inspired elements. Built on Laravel 12, Tailwind CSS, Alpine.js, and Vite.

---

## Getting Started & Fresh Clone Setup

Follow these steps to run HOYOS locally from a fresh repository clone:

### 1. Install Dependencies
```bash
composer install
npm install
npm run build
```

### 2. Environment Configuration
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Configure your database connection in `.env` (PostgreSQL or SQLite):
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hoyos
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
*(For quick local development using SQLite, set `DB_CONNECTION=sqlite` and create `database/database.sqlite`)*

### 3. Database Migration & Seeding
Run migrations and seed the initial catalog, categories, demo users, orders, and image records:
```bash
php artisan migrate:fresh --seed
```

### 4. Create Public Storage Symlink (Critical)
Connect `public/storage` to `storage/app/public` so product photography and WebP assets are served directly:
```bash
php artisan storage:link
```

### 5. Start the Development Server
```bash
php artisan serve
```
Visit the store at [http://127.0.0.1:8000](http://127.0.0.1:8000).

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

---

## Automated Testing & Visual Regression

### Feature & Unit Tests
Run the complete test suite (56 tests covering authentication, cart, checkout, payment simulation, and order lookup):
```bash
php artisan test
```

### Visual Regression Screenshots
Capture high-resolution 1400px desktop and 390px mobile viewport screenshots of key customer journeys:
```bash
node scripts/take_audit_screenshots.js
```
Screenshots are written to `storage/app/screenshots/`.
