# HOYOS E-Commerce — Render Free Demo Deployment Guide

This guide details how to deploy the HOYOS Laravel 12 monolith to a **Render Free Web Service** with a connected **Render PostgreSQL database** in a secure, demo-safe configuration.

---

## 1. Architecture Overview

```
                          Internet / Customers
                                   │
                                   ▼
             Render Edge Load Balancer (SSL Termination)
             [X-Forwarded-Proto: https, X-Forwarded-For]
                                   │
                                   ▼
              ┌────────────────────────────────────────┐
              │  Render Web Service Container          │
              │  (Free Tier: 512MB RAM, Ephemeral FS)   │
              │                                        │
              │   Nginx (Port $PORT, e.g. 10000)       │
              │     │                                  │
              │     ▼                                  │
              │   PHP 8.2-FPM                          │
              │     │                                  │
              │     ├── Laravel 12 Application         │
              │     │   (APP_DEBUG=false)              │
              │     │   (HOYOS_DEMO_MODE=true)         │
              │     └── Pre-bundled Vite CSS/JS        │
              └──────────────────┬─────────────────────┘
                                 │
                                 ▼
                     Render PostgreSQL Database
                     (Free Tier: 1GB Storage, 30 Days)
```

---

## 2. Demo-Only Payment Mode

When `HOYOS_DEMO_MODE=true` is enabled:
1. **Zero External API Calls:** `AbaPaywayService` completely bypasses outbound HTTP requests to ABA PayWay or external bank gateways.
2. **Non-payable demo placeholder:** Renders an unmistakable non-payable simulation card; it does not generate EMVCo/Bakong payloads or banking deeplinks.
3. **Simulated Approval:** Customers and testers click the **"⚡ Simulate Instant ABA Payment Approval"** button on the KHQR screen to trigger instant payment confirmation.
4. **Unsupported Methods Blocked Server-Side:** Cash on Delivery (COD) and arbitrary payment methods are rejected server-side with HTTP 422 / session validation errors. Hiding buttons alone is not relied upon.
5. **No Financial Claims:** All receipts, audit entries, and payments clearly record `'is_simulated' => true` with reference prefix `DEMO-SIMULATION-`.

---

## 3. Render Free Tier Limitations & Operational Strategy

| Limitation | Impact on Free Tier | HOYOS Strategy |
| :--- | :--- | :--- |
| **No Shell / SSH / One-Off Jobs** | Render Free web services **do not provide Shell or SSH console access**, nor do they support pre-deploy commands or one-off background jobs. | Database migrations use an explicit, opt-in startup flag: `RUN_MIGRATIONS_ON_START=true` for initial setup. Once completed, it is changed to `false` to prevent migrations from running on every normal startup. |
| **Ephemeral Filesystem** | Uploads in `storage/app/public` are lost on container restart or redeploy. | Default catalog images are bundled directly in `public/images/products` inside the Docker image and persist across all restarts. For persistent uploads in production, attach a Render Persistent Disk or configure AWS S3 / Cloudflare R2 (`FILESYSTEM_DISK=s3`). |
| **Inactivity Spin-down** | Container sleeps after 15 minutes of zero traffic. | The next request wakes the container within ~30–50 seconds. Health checks ping `/up`. |
| **PostgreSQL Expiration** | Free databases expire after 30 days. | Suitable for temporary demos and grading. Upgrade to a paid database ($7/mo) before the 30-day window closes if long-term persistence is needed. |
| **512MB RAM Limit** | High concurrency may cause OOM. | Nginx and PHP-FPM OPcache are tuned to 256MB memory limit; Vite assets are pre-built during the image build stage to save container memory. |

---

## 4. Required Environment Variables

Configure these variables in your **Render Web Service Settings > Environment**:

| Variable | Recommended Value | Description |
| :--- | :--- | :--- |
| `APP_NAME` | `HOYOS` | Application brand name |
| `APP_ENV` | `production` | Production environment mode |
| `APP_DEBUG` | `false` | **Crucial:** Never expose detailed stack traces in production |
| `APP_KEY` | *(Auto-generated)* | 32-character encryption key (e.g., `base64:...`) |
| `APP_URL` | `https://your-service.onrender.com` | Public URL assigned by Render |
| `RUN_MIGRATIONS_ON_START` | `true` *(Initial only)* / `false` *(Normal)* | Explicit opt-in flag to run `php artisan migrate --force` during container startup. Must be toggled to `false` after the initial deploy. |
| `HOYOS_DEMO_MODE` | `true` | **Enforces demo-only payments** and blocks external gateways |
| `HOYOS_ALLOW_PAYMENT_SIMULATION` | `true` | Activates `/payment/{orderNumber}/simulate` |
| `DB_CONNECTION` | `pgsql` | PostgreSQL driver |
| `DATABASE_URL` | *(Linked from DB)* | `postgresql://user:password@host/database` |
| `SESSION_DRIVER` | `database` | Stores sessions in PostgreSQL (survives container restarts) |
| `CACHE_STORE` | `database` | Stores application cache in PostgreSQL |
| `QUEUE_CONNECTION` | `sync` | Synchronous queue processing (no background worker needed) |
| `LOG_CHANNEL` | `stderr` | Routes logs directly to Render's live log viewer |
| `LOG_LEVEL` | `info` | Avoids debug log bloat |
| `TRUSTED_PROXIES` | `*` | Ensures Laravel recognizes HTTPS from Render's proxy |

---

## 5. Deployment Options

### Option A: Using the Render Blueprint (`render.yaml`) — Recommended

1. Push your repository to GitHub.
2. In the [Render Dashboard](https://dashboard.render.com), click **New +** > **Blueprint**.
3. Connect your repository: `https://github.com/pichsareth120-glitch/hoyos-project`.
4. Render automatically parses [`render.yaml`](file:///d:/Hoyos-project/render.yaml), sets up both the `hoyos-ecommerce` Web Service and `hoyos-db` PostgreSQL database, links `DATABASE_URL`, and generates an `APP_KEY`.
5. Before clicking **Apply** (or in the service settings immediately following blueprint creation), set `RUN_MIGRATIONS_ON_START` to `true`.
6. Click **Apply** to trigger the initial deployment.
7. Follow the **First-Deployment Procedure** in Section 6 below to confirm migration completion and switch the variable back to `false`.

---

### Option B: Manual Setup via Render Dashboard

1. **Create PostgreSQL Database:**
   - Click **New +** > **PostgreSQL**.
   - Name: `hoyos-db`
   - Database: `hoyos`
   - User: `hoyos_user`
   - Plan: **Free**
   - Click **Create Database**. Copy the **Internal Database URL**.

2. **Create Web Service:**
   - Click **New +** > **Web Service**.
   - Connect repository `hoyos-project`.
   - Runtime: **Docker**
   - Plan: **Free**
   - Health Check Path: `/up`
   - Under **Environment Variables**, add all variables from Section 4. Set `DATABASE_URL` to your database's Internal Connection String.
   - For the initial deployment, set `RUN_MIGRATIONS_ON_START` to `true`.
   - Click **Create Web Service**.
   - Follow the **First-Deployment Procedure** in Section 6 below.

---

## 6. Safe Database Migration & First-Deployment Procedure

Because Render Free web services **do not provide Shell/SSH console access or one-off background jobs**, migrations cannot be executed manually from an interactive terminal inside the Render container. 

To solve this safely, the container entrypoint ([`docker/entrypoint.sh`](file:///d:/Hoyos-project/docker/entrypoint.sh)) contains an explicit, opt-in startup hook:

```sh
# Opt-in database migration check for environments without Shell access (e.g. Render Free)
if [ "$RUN_MIGRATIONS_ON_START" = "true" ]; then
    echo "[HOYOS] RUN_MIGRATIONS_ON_START is 'true'. Running database migrations..."
    if php artisan migrate --force; then
        echo "[HOYOS] Database migrations completed successfully."
    else
        echo "[HOYOS] ERROR: Database migration failed! Halting container startup." >&2
        exit 1
    fi
else
    echo "[HOYOS] RUN_MIGRATIONS_ON_START is not 'true' (value: '${RUN_MIGRATIONS_ON_START:-unset}'). Skipping automatic database migrations."
fi
```

### Exact First-Deployment Procedure (Step-by-Step)

Follow this 4-step sequence on the initial deployment (and whenever subsequent code changes include new migrations):

1. **Step 1: Set `RUN_MIGRATIONS_ON_START=true` for the initial deployment**
   - In the Render Dashboard, navigate to your Web Service > **Environment**.
   - Set `RUN_MIGRATIONS_ON_START` = `true`.
   - Trigger the deploy (or save environment variables, which initiates a deploy).

2. **Step 2: Confirm deployment succeeds and migration logs show completion**
   - Open the **Logs** tab for your Web Service.
   - Watch the deployment stream. Verify the container emits:
     ```
     [HOYOS] RUN_MIGRATIONS_ON_START is 'true'. Running database migrations...
     ...
     INFO  Running migrations.
     ...
     [HOYOS] Database migrations completed successfully.
     ```
   - Confirm that the Render deploy status transitions to **"Live"** and the `/up` health check returns HTTP 200.

3. **Step 3: Change the variable to `false` and redeploy**
   - Return to the **Environment** tab in your Web Service settings.
   - Change `RUN_MIGRATIONS_ON_START` from `true` to `false`.
   - Click **Save Changes**. Render will automatically trigger a fresh redeploy with the updated environment variable.

4. **Step 4: Confirm normal startup works without automatically running migrations**
   - Open the **Logs** tab for the new deployment.
   - Verify the startup logs explicitly confirm migrations are bypassed:
     ```
     [HOYOS] RUN_MIGRATIONS_ON_START is not 'true' (value: 'false'). Skipping automatic database migrations.
     [HOYOS] Warming Laravel configuration and route caches...
     [HOYOS] Launching PHP-FPM and Nginx via supervisord...
     ```
   - Confirm the container becomes healthy (**"Live"**), ready to serve traffic and handle sleep/wake cycles without re-running migrations.

---

### Safe Migration Rules

- **Forward-Only Migrations:** All HOYOS migrations are additive and forward-only. The repository includes `sessions`, `cache`, and `cache_locks` tables required for PostgreSQL-backed sessions and cache.
- **Never Run Destructive Commands:** Never execute `php artisan migrate:fresh`, `migrate:reset`, or `db:wipe` in deployment scripts or against production databases.
- **Fail-Safe Startup Halted on Failure:** If `php artisan migrate --force` encounters an error (such as a database connection timeout or schema collision), the entrypoint exits immediately (`exit 1`). Supervisord, Nginx, and PHP-FPM will not start. Render detects the container exit and marks the deployment as failed, preventing the application from serving requests against an unmigrated or corrupted database state.

---

### Initial Demo Data & Seeding Notice

> [!IMPORTANT]
> **Do NOT place seeders in the automated container entrypoint.**
> Seeders are not idempotent. Running `db:seed` on container startup can duplicate data, overwrite customer testing records, or cause startup timeouts.

Because Render Free does not offer an interactive Shell tab to run `php artisan db:seed --force` or `php artisan tinker`:
- **Default Storefront Operation:** The application functions fully without seeders. The default catalog, product images, categories, and payment simulation workflows operate out-of-the-box once schema migrations have run.
- **Optional External Seeding:** If you wish to populate initial demo records or seed an administrator user from your development environment:
  1. In the Render Dashboard, open your PostgreSQL database (`hoyos-db`).
  2. Locate and copy the **External Database URL**.
  3. From your secure local development machine (with project dependencies installed), run:
     ```bash
     DATABASE_URL="<render_external_database_url>" php artisan db:seed --force
     ```
  4. Alternatively, use standard PostgreSQL client tools (e.g., pgAdmin, DBeaver, `psql`) connected via the external connection string to inspect or insert records.

---

## 7. Health Checks and Verification

Render checks container health via:
```http
GET /up
HTTP/1.1 200 OK
```
- Defined in [`bootstrap/app.php`](file:///d:/Hoyos-project/bootstrap/app.php) and handled through Nginx.
- Render marks the deploy healthy only after `/up` returns HTTP 200.

### Verification Checklist After Deploy:
1. **Visit Storefront:** Open `https://<your-service>.onrender.com/`. Verify products, images, and dark styling load correctly without mixed content warnings.
2. **Cart & Checkout:** Add an item to cart, proceed to checkout. Confirm the **"Render Demo Mode Active"** banner is visible.
3. **Verify COD Restriction:** Verify COD is disabled and only **Simulated ABA KHQR** is selectable.
4. **KHQR Page:** Submit checkout. Confirm the non-payable demo placeholder renders with “Do Not Scan” and the 5-minute countdown starts.
5. **Simulate Payment:** Click **"⚡ Simulate Instant ABA Payment Approval"**. Verify instant approval, redirect to `/orders/{orderNumber}`, and confirmation receipt labeled **"Simulated Demo (No Charge)"**.
6. **Order Tracking:** Visit `/track-order`, enter order number and customer phone, verify timeline.

---

## 8. Rollback and Recovery

- **Rollback Deployments:** In Render Dashboard > **Deploys**, find any previous successful deploy and click **Rollback to this deploy**.
- **Container Crashes:** Check logs under the **Logs** tab. If a container fails to start, verify `APP_KEY` is present, `DATABASE_URL` is accessible, and `RUN_MIGRATIONS_ON_START` is either `false` or verified.
- **Cache Invalidation:** Because Render Free has no Shell access, clearing caches is done by triggering a redeploy. Every container boot automatically re-warms configuration, route, and view caches cleanly:
  ```
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
  To force a fresh rebuild of application assets and caches, go to **Manual Deploy** > **Clear build cache & deploy** in the Render Dashboard.

---

## 9. Risks and Operational Limitations of Startup Migrations

Deploying migrations during container startup on Render Free involves operational trade-offs that teams must understand:

1. **Inactivity Sleep & Cold Start Latency (If Left `true`):**
   Render Free containers spin down after 15 minutes of zero traffic. If `RUN_MIGRATIONS_ON_START` remains `true`, every cold-start wake-up executes `php artisan migrate --force`. Even when no new migrations are pending, querying the `migrations` table adds 3–10 seconds to cold-start response times, increasing initial visitor wait times. Setting it to `false` after deployment completely eliminates this penalty.

2. **Render Health Check Timeout Risk:**
   Render monitors `/up` with a strict initial startup deadline. If a large migration takes too long to acquire table locks or process records, the health check will time out before Nginx and PHP-FPM launch. Render will declare the deploy failed and terminate the container. Keep migrations concise, indexed, and additive.

3. **Concurrency & Race Conditions in Scaled Environments:**
   While Render Free runs a single container instance, any horizontal scaling or overlapping rolling deploy with `RUN_MIGRATIONS_ON_START=true` could lead to multiple instances executing migrations concurrently. Although Laravel employs a migration lock table, running startup migrations across parallel instances introduces lock contention risks. The opt-in toggle pattern ensures migrations only run intentionally.

4. **Fail-Closed Application Availability:**
   If a migration fails due to an invalid column definition or database connectivity blip, the entrypoint halts with `exit 1` before starting web services. This fail-closed design prevents serving requests with corrupted or mismatched schemas, but it does mean the web service will be offline until the configuration or database issue is corrected.

5. **No Interactive Shell for Migration Rollbacks or Status:**
   Because Render Free provides no Shell or SSH terminal, commands like `php artisan migrate:status` or `php artisan migrate:rollback` cannot be executed directly on Render. Operators must inspect deployment logs for status and connect via an external PostgreSQL client if manual schema intervention is ever required.
