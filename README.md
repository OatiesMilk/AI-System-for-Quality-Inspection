# CPoint Quality Inspection System

AI-assisted quality inspection system for CPoint Shoe Store Corporation's shoe manufacturing line. Laravel 12 backend with role-based dashboards (Quality Inspector, Product Manager, System Admin, Shoe Constructor), a shared MySQL database reachable over Tailscale, and Chart.js analytics.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL 8.0 (Workbench or any MySQL client recommended)
- [Tailscale](https://tailscale.com/) installed and connected, if you're working across multiple machines on the same shared database

## Adding a teammate to the Tailscale network

Everyone accessing the shared database or API needs to be on the same tailnet, not just have the app cloned.

1. Tailnet owner goes to [login.tailscale.com/admin/users](https://login.tailscale.com/admin/users) and invites the teammate by email.
2. Teammate accepts the invite and signs into Tailscale with their own account (any provider - doesn't need to match the owner's).
3. Teammate installs Tailscale ([tailscale.com/download](https://tailscale.com/download)) and logs in with the account that accepted the invite.
4. Their device appears automatically on the [Machines page](https://login.tailscale.com/admin/machines) with its own `100.x.x.x` address.
5. Verify both directions with `tailscale ping <the-other-machine's-ip>`.

Note: free/personal Tailscale plans cap the number of users per tailnet - check the Users tab if an invite doesn't go through.

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
DB_HOST=100.76.1.106
DB_PORT=3306
DB_DATABASE=cpoint_system
DB_USERNAME=cpoint_remote
DB_PASSWORD=<ask the database host machine's owner>
```

> `.env` is gitignored and machine-specific - these values are never pulled from git. Each teammate sets their own `.env`, but everyone should point `DB_HOST` at the same shared database if you want all dashboards to reflect the same data.

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

Only needs to be run **once**, by whoever is setting up the database for the first time (e.g. the host machine). Everyone else connecting to the same shared database should skip this step - the schema already exists.

```bash
php artisan migrate
```

## 4. Build frontend assets

```bash
npm run build
```

Run this any time you pull changes that touch Blade views, CSS, or JS - Tailwind and Chart.js are compiled at build time, so a stale build means missing styles or a broken chart even if the underlying code is correct. Use `npm run dev` instead if you want live-reloading Vite during active development.

## 5. Serve the app

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000`.

**If you're the shared database host and teammates or the YOLO pipeline need to reach this machine's API over Tailscale**, `php artisan serve` on its own only binds to `127.0.0.1` (localhost) - no other device can connect, even over Tailscale, even though it looks like it's running fine. Bind it to all interfaces instead:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Then open the firewall for port `8000`, scoped to the Tailscale subnet:

```powershell
New-NetFirewallRule -DisplayName "Laravel Tailscale" -Direction Inbound -Protocol TCP -LocalPort 8000 -RemoteAddress 100.64.0.0/10 -Action Allow
```

Keep that exact terminal window open for as long as remote devices need access - closing it stops the server.

## 6. Set the app timezone (optional but recommended)

The app defaults to `Asia/Manila` in `config/app.php`. If you change it, run:

```bash
php artisan config:clear
```

## Role accounts

The system has four roles, each with its own dashboard:

| Role | Route | Capabilities |
|---|---|---|
| Quality Inspector | `/inspector` | Human-in-the-loop validation of AI-flagged defects, pass/rework/reject decisions, filterable review history |
| Product Manager | `/manager` | Defect analytics, AI override diagnostics, batch overview, create production batches |
| System Admin | `/admin` | Create/edit any account (including other admins), full audit log with filters |
| Shoe Constructor | `/constructor` | Rework notifications for batches they assembled, image/defect detail, mark reworks resolved |

Account creation is restricted to System Admin only. There is no public registration page - to create your first account, run `php artisan db:seed`, which creates one demo account per role (see `database/seeders/DatabaseSeeder.php`), or create one directly via `php artisan tinker`.

## Computer Vision Ingestion API

The YOLO detection pipeline posts inspection results into the system via a machine-to-machine API endpoint, authenticated with a [Laravel Sanctum](https://laravel.com/docs/sanctum) token (not a session login).

**Endpoint:** `POST /api/inspections`

**Auth:** `Authorization: Bearer <token>` header, where the token has the `inspections:create` ability.

**Payload** (`multipart/form-data`, since it includes a file):

| Field | Type | Required | Notes |
|---|---|---|---|
| `batch_id` | int | yes | Must reference an existing batch |
| `checkpoint` | string | yes | `preparation` or `pre_assembly` |
| `image` | file | yes | The captured inspection photo, max 10MB |
| `defects[n][defect_type]` | string | no | One of: `scratch`, `cut`, `hole`, `crease`, `glue`, `stitch` |
| `defects[n][confidence_score]` | float | no | YOLO confidence, 0–1 |
| `defects[n][bounding_box][x/y/width/height]` | float | no | Fractional coordinates (0–1) of the image |

**Response:** `201 Created` with `{ "message", "inspection_id", "defect_count" }`. The created inspection lands in the Quality Inspector's "Pending Inspections" queue exactly like any other inspection - no separate code path.

**Image storage:** the uploaded photo is stored as base64 inside the `inspections` table (`image_data`/`image_mime` columns), not on disk. This means every machine connected to the shared MySQL database can see inspection images immediately, with no separate file share, SMB setup, or S3-compatible storage needed - the existing DB connection is the only thing that has to be centralized. Images are served back out via `GET /inspections/{inspection}/image` (any authenticated dashboard role can view), which streams the decoded bytes with the correct `Content-Type`.

### Resolving the current batch automatically

**Endpoint:** `GET /api/batches/latest?checkpoint=preparation`

Lets the capture pipeline target whichever batch is currently open without hardcoding a `batch_id`. Returns the most recently created batch (`latest('id')`, not `created_at`, so it's unambiguous even when two batches are created in the same second) matching the given `checkpoint` (required).

**Response:** `200 OK` with `{ "batch_id", "batch_code", "manufacturing_stage" }`, or `404` if nothing matches. Same auth as the ingestion endpoint above.

`live_feed_to_system.py` uses this automatically when run without `--batch-id` - it re-resolves the latest matching batch right before every capture, so if the Product Manager creates a new batch mid-session, the very next `c` press lands in the new batch with no restart needed.

### Issuing a token for the YOLO service

Run once (e.g. on the machine hosting the model), and give the resulting token to whoever configures the Python pipeline:

```bash
php artisan tinker --execute="
\$service = App\Models\User::firstOrCreate(
    ['email' => 'yolo-service@cpoint.internal'],
    ['name' => 'YOLO Detection Service', 'role' => 'system_admin', 'password' => bcrypt(Illuminate\Support\Str::random(40)), 'email_verified_at' => now()]
);
echo \$service->createToken('yolo-pipeline', ['inspections:create'])->plainTextToken;
"
```

The token is only shown once - store it securely (e.g. in the Python service's own `.env`, never committed to git).

## Troubleshooting

**"Vite manifest not found at .../public/build/manifest.json"**
Run `npm install && npm run build`. This happens after a fresh clone or pull when assets haven't been compiled yet on that machine.

**Styles or buttons look unstyled after pulling new changes**
Same root cause - `npm run build` wasn't re-run after the Blade/CSS changes landed. The compiled `public/build/` output isn't tracked in git, so each machine has to rebuild it locally.

**Database connection errors**
Confirm `.env` has the correct `DB_HOST` (your own machine if running locally, or the shared host's Tailscale IP if using the team database), and that Tailscale shows both machines as `Connected`.

**MySQL: `Host '...' is not allowed to connect to this MySQL server` (error 1130)**
No matching row exists in `mysql.user` for that account/host combo - usually means the remote user was never actually created, or was created with the wrong host. Check with `SELECT user, host FROM mysql.user WHERE user = 'cpoint_remote';` (expect one row with host `%`), and recreate it if missing (see step 2 above).

**A teammate or the YOLO pipeline gets `ConnectTimeoutError`/`Connection timed out` hitting the API or DB, but `tailscale ping` between the two machines succeeds**
Ping succeeding means the Tailscale tunnel itself is fine - the block is happening after the packet lands on the host machine. Check these in order, on the **host** machine:
1. Is `php artisan serve` actually bound to `0.0.0.0`, not just `127.0.0.1`? Check with `netstat -an | findstr :8000` (look for a `0.0.0.0:8000 LISTENING` line). If missing, restart with `--host=0.0.0.0`.
2. Does the relevant firewall rule (`Laravel Tailscale` for port 8000, `MySQL Tailscale` for port 3306) still exist and is it enabled? `Get-NetFirewallRule -DisplayName "<name>" | Format-Table DisplayName, Enabled, Direction, Action`.
3. **Check for an auto-created `php.exe` block rule** - Windows sometimes silently creates one of these the first time `php artisan serve` tries to listen, if the "allow this app" prompt gets dismissed. A specific program-level block overrides a general port-based allow rule. Check with:
   ```powershell
   Get-NetFirewallRule | Where-Object {$_.DisplayName -like "*php*"} | Select-Object DisplayName, Direction, Action, Enabled, Profile
   ```
   If it shows an `Action: Block` entry, remove it: `Get-NetFirewallRule | Where-Object {$_.DisplayName -eq "php.exe" -and $_.Action -eq "Block"} | Remove-NetFirewallRule`.
4. On the host's Tailscale system tray icon → Preferences, confirm **"Allow incoming connections"** is checked (not blocked/"Shields Up").
5. Check the tailnet's [Access Controls](https://login.tailscale.com/admin/acls) policy hasn't been narrowed from the default allow-all.
6. From the connecting machine, `Test-NetConnection -ComputerName <host-ip> -Port <port>` gives a definitive `TcpTestSucceeded` result, isolating the network layer from the app itself.

**YOLO pipeline gets `401 Unauthenticated` after the shared database was migrated fresh or moved to a new host**
Sanctum tokens live in the `personal_access_tokens` table. If the database was recreated (e.g. `migrate:fresh`, or moving the host without carrying over a dump), every previously issued token - including the YOLO service's - stops existing and is rejected. Reissue a new one (see "Issuing a token for the YOLO service" above) and update `CPOINT_API_TOKEN` in every `.env` that uses it.
