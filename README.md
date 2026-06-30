# CPoint Quality Inspection System

AI-assisted quality inspection system for CPoint Shoe Store Corporation's shoe manufacturing line. Laravel 12 backend with role-based dashboards (Quality Inspector, Product Manager, System Admin, Shoe Constructor), a shared MySQL database reachable over Tailscale, and Chart.js analytics.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL 8.0 (Workbench or any MySQL client recommended)
- [Tailscale](https://tailscale.com/) installed and connected, if you're working across multiple machines on the same shared database

## 1. Clone and install dependencies

```bash
git clone https://github.com/OatiesMilk/CPoint-System.git
cd CPoint-System

composer install
npm install
```

## 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and point the database connection at MySQL. If the team is sharing one MySQL instance over Tailscale (instead of each machine running its own local SQLite/MySQL), use the host machine's Tailscale IP:

```env
DB_CONNECTION=mysql
DB_HOST=100.65.92.18
DB_PORT=3306
DB_DATABASE=cpoint_system
DB_USERNAME=cpoint_remote
DB_PASSWORD=<ask the database host machine's owner>
```

> `.env` is gitignored and machine-specific — these values are never pulled from git. Each teammate sets their own `.env`, but everyone should point `DB_HOST` at the same shared database if you want all dashboards to reflect the same data.

If you're the one **hosting** the shared MySQL database, instead create a local database and a remote-accessible user:

```sql
CREATE DATABASE cpoint_system;
CREATE USER 'cpoint_remote'@'%' IDENTIFIED BY 'your-password-here';
GRANT ALL PRIVILEGES ON cpoint_system.* TO 'cpoint_remote'@'%';
FLUSH PRIVILEGES;
```

Then open Windows Firewall for port `3306`, scoped to the Tailscale subnet only:

```powershell
New-NetFirewallRule -DisplayName "MySQL Tailscale" -Direction Inbound -Protocol TCP -LocalPort 3306 -RemoteAddress 100.64.0.0/10 -Action Allow
```

## 3. Run migrations

Only needs to be run **once**, by whoever is setting up the database for the first time (e.g. the host machine). Everyone else connecting to the same shared database should skip this step — the schema already exists.

```bash
php artisan migrate
```

## 4. Build frontend assets

```bash
npm run build
```

Run this any time you pull changes that touch Blade views, CSS, or JS — Tailwind and Chart.js are compiled at build time, so a stale build means missing styles or a broken chart even if the underlying code is correct. Use `npm run dev` instead if you want live-reloading Vite during active development.

## 5. Serve the app

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000`.

## 6. Set the app timezone (optional but recommended)

The app defaults to `Asia/Manila` in `config/app.php`. If you change it, run:

```bash
php artisan config:clear
```

## Role accounts

The system has four roles, each with its own dashboard:

| Role | Route | Capabilities |
|---|---|---|
| Quality Inspector | `/inspector` | Human-in-the-loop validation of AI-flagged defects, pass/rework/reject decisions |
| Product Manager | `/manager` | Defect analytics (doughnut chart), batch overview, create inspector/constructor accounts |
| System Admin | `/admin` | Create/edit any account (including other admins), full audit log |
| Shoe Constructor | `/constructor` | Rework notifications for batches they assembled, submit reports |

To create your first account, register normally via `/register`, then have a System Admin (or directly via `php artisan tinker`) set your `role` column to one of: `quality_inspector`, `product_manager`, `system_admin`, `shoe_constructor`.

## Troubleshooting

**"Vite manifest not found at .../public/build/manifest.json"**
Run `npm install && npm run build`. This happens after a fresh clone or pull when assets haven't been compiled yet on that machine.

**Styles or buttons look unstyled after pulling new changes**
Same root cause — `npm run build` wasn't re-run after the Blade/CSS changes landed. The compiled `public/build/` output isn't tracked in git, so each machine has to rebuild it locally.

**Database connection errors**
Confirm `.env` has the correct `DB_HOST` (your own machine if running locally, or the shared host's Tailscale IP if using the team database), and that Tailscale shows both machines as `Connected`.
