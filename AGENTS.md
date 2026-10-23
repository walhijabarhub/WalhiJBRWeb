# 🤖 AI Agent Guidelines & Navigation System

**Repository:** `walhijabarhub/WalhiJBRWeb`  
**Platform:** Official Web Platform of WALHI Jawa Barat (Wahana Lingkungan Hidup Indonesia — Jawa Barat)  
**Primary Language:** PHP 8.4+ / Laravel 13.x  
**Target Environments:** Local (macOS/Linux/Docker), Production (Ubuntu VPS / Docker Compose, deployed automatically from branch `production`)  
**Branches:** `production` = source of truth & live (open PRs against it); `main` = automatic mirror of `production`; `prod` = deprecated, do not use.

This document is the **primary entrypoint and operating manual** for modern AI coding agents (Claude Code, OpenAI Codex, Gemini CLI, Cursor, and similar systems). It defines verified architectural patterns, directory structures, business invariants, security constraints, and change protocols.

---

## 1. Verified Technology Stack

| Layer | Technology | Verified Version | Notes |
| :--- | :--- | :--- | :--- |
| **Runtime** | PHP | `^8.3` (Production image: `php:8.4.27-fpm-alpine3.24`, digest-pinned; CI enforces `MIN_PHP_VERSION`) | Typed parameters, return types, enums. |
| **Framework** | Laravel | `^13.8` (Locked: `13.38.0`) | Modern callable `bootstrap/app.php`. `composer.json` pins `config.platform.php` to the production PHP (8.4.27); change it together with the Dockerfile `FROM php:`. |
| **Testing** | PHPUnit | `^12.5.12` | 307 automated tests (1333 assertions). |
| **Frontend Build** | Vite | `^8.0.0` (Installed: `8.1.3`) | `laravel-vite-plugin ^3.1`. |
| **Styling** | Tailwind CSS | `3.4.19` | PostCSS pipeline, Neo-Brutalist design tokens. |
| **Reactivity** | Alpine.js **CSP build** | `@alpinejs/csp ^3.17.5` | No `eval`; inline handlers replaced by `resources/js/csp-actions.js`. |
| **Icons** | Lucide Icons | `lucide 0.460.0` (npm, bundled) / Inline SVG | Data attributes: `data-lucide="..."`; only icons imported in `resources/js/icons.js` render. |
| **Database** | SQLite / MySQL | SQLite (local/tests), MySQL 8.0 (prod, also tested in CI) | SoftDeletes on Content, Comment, Donation, Subscriber. |
| **Cache / Queue / Session** | Redis 7 | Production only | Local uses `database`/`file` drivers. |
| **2FA** | pragmarx/google2fa + bacon-qr-code | `^8.0` / `^3.0` | TOTP, mandatory for admin/editor. |
| **CAPTCHA** | Cloudflare Turnstile | optional | Enabled only when both keys are set. |
| **Security Sanitizer**| HTMLPurifier | `^4.19` | Configured on `Content::getSanitizedBodyAttribute()`. |

> [!IMPORTANT]
> Always write code and configuration for **Laravel 13**. Note that Laravel 13 refuses to unserialize objects from the cache (`cache.serializable_classes=false`): cache arrays, or use `App\Services\Content\ContentCache`.

---

## 2. Directory Structure & Semantic Responsibilities

```text
WalhiJBRWeb/
├── .agents/rules/secure_coding.md # Enforced coding & security rules (read before editing)
├── .github/workflows/             # ci.yml, security.yml, deploy.yml (production), sync-main.yml, reviewbot.yml
├── app/
│   ├── Console/Commands/          # walhi:create-admin, walhi:reset-2fa, walhi:donations-reconcile, walhi:flush-views,
│   │                              # walhi:prune-personal-data, walhi:migrate-documents, import commands
│   ├── Enums/                     # ContentCategory (22), ContentStatus, DonationStatus, UserRole
│   ├── Http/
│   │   ├── Controllers/           # Public controllers (content, pages, documents, donation, comments, newsletter)
│   │   │   ├── Admin/             # Backoffice CMS controllers
│   │   │   └── Auth/              # Login (custom path), password reset, TwoFactor(Challenge)Controller
│   │   ├── Middleware/            # SecurityHeaders (CSP), CheckRole (`role:`), EnsureTwoFactorEnabled (`two-factor`)
│   │   └── Requests/              # FormRequests (Admin, Auth, Comment, Donation, Newsletter)
│   ├── Models/                    # Content, Comment, Donation, Subscriber, User, AuditLog
│   ├── Notifications/             # ConfirmNewsletterSubscription (queued)
│   ├── Observers/                 # ContentObserver (cache invalidation)
│   ├── Policies/                  # ContentPolicy, CommentPolicy, DonationPolicy, SubscriberPolicy
│   ├── Rules/                     # SafeUrl, Turnstile
│   └── Services/                  # Auth/TwoFactorService, Content/{HtmlSanitizer,ContentCache,ViewCounter,SlugService},
│                                  # Donation/{DonationService,MidtransService}, AuditLogService, CsvSanitizer
├── bootstrap/app.php              # Routing, middleware aliases, trustProxies, CSRF exceptions, exception handling
├── config/                        # + midtrans.php, privacy.php, services.php (turnstile), logging.php (audit channel)
├── database/
│   ├── migrations/
│   └── seeders/                   # AdminUserSeeder (idempotent, never overwrites passwords), ContentSeeder
├── docker/                        # nginx templates (http/https), realip, php-fpm pool, mysql config
├── docs/                          # domain-rules, database, frontend, testing, configuration, operations, admin-guide
├── resources/
│   ├── js/app.js                  # Alpine CSP build + Alpine.data components
│   ├── js/csp-actions.js          # data-on-click / data-confirm / ... (replacement for inline handlers)
│   └── views/                     # Blade views
├── routes/
│   ├── web.php                    # Public pages, document download, donation, newsletter, admin
│   ├── auth.php                   # Login at ADMIN_LOGIN_PATH, password reset, 2FA
│   └── console.php                # Schedule (reconcile 15 min, flush-views 5 min, prune monthly)
├── storage/app/private/documents/ # SEC-008 private documents
├── storage/app/public/uploads/    # Public media
├── tests/Feature/                 # 307 tests; tests/TestCase.php disables Vite
├── deploy.sh                      # Production deploy with migrate-before-swap, health check, rollback
├── docker-backup.sh / docker-restore.sh
└── docker-compose.prod.yml        # app, worker, scheduler, db, redis, web
```

---

## 3. High-Level Request Flow

```text
HTTP Request
    ↓
nginx container (TLS, static files, overwrites client X-Forwarded-*, optional Cloudflare real IP, script block in /storage/)
    ↓
bootstrap/app.php (TrustProxies, TrustHosts, CSRF Verification)
    ↓
Global Middleware: SecurityHeaders (CSP nonce injection, X-Frame-Options, X-Content-Type-Options)
    ↓
Routing (routes/web.php / routes/auth.php)
    ├─ Parameter Validation (e.g. {category} constrained by ContentCategory enum regex)
    └─ Route Middleware (throttle, auth, verified, two-factor, role:admin,editor)
    ↓
Form Request Validation (e.g. StoreContentRequest, positive URL allowlist)
    ↓
Controller Action (e.g. ContentController, DocumentDownloadController)
    ↓
Policy Authorization ($this->authorize('view'/'update'/'delete', $content))
    ↓
Service Layer (DonationService, MidtransService, HtmlSanitizer, ContentCache, ViewCounter, AuditLogService)
    ↓
Eloquent Model (Content, Donation, User) & DB::transaction
    ↓
Storage Resolution (Private disk 'local' vs Public disk 'public')
    ↓
View Render (Blade + Alpine.js) OR Streamed Download / JSON Response
```

---

## 4. Key Business Invariants & Rules

An AI agent must preserve the following business rules:

### A. Content Domain
1. **Lifecycle States:** `draft` → `published` → `archived`.
2. **Access Control:**
   - `published` content is accessible to anyone (public guests and members).
   - `draft` or `archived` content is restricted to authenticated users with `canManageContent()`.
   - Guests requesting unpublished content MUST receive **`404 Not Found`** (anti-resource enumeration).
   - Sensitive categories (`kontak`, `kampanye-darurat`, `donasi`) require `UserRole::Admin`.
3. **Category Integrity:**
   - 21 allowed categories defined strictly in `App\Enums\ContentCategory`.
   - Arbitrary category strings are rejected at the HTTP route boundary with `404`.
4. **Category-Specific Tags Encoding:**
   - Category `isu-kritis`: tags encoded as `icon|badge` (e.g. `Icon-4.svg|Isu Lingkungan`).
   - Category `regulasi`: tags encoded as `kategori, penerbit, status` (e.g. `undang-undang, Pemerintah RI, berlaku`).

### B. Document Storage & Download Pipeline (SEC-008)
1. **Physical Isolation:**
   - Documents (`pdf`, `doc`, `docx`, `xls`, `xlsx`) MUST be stored on disk `'local'` (`storage/app/private/documents/`). They MUST NOT be placed on the public disk.
   - Display images (covers, banners, Quill uploads) are stored on disk `'public'` (`storage/app/public/uploads/`).
2. **Authorized Download Endpoint:**
   - Route: `GET /dokumen/{content:slug}/unduh` via `DocumentDownloadController`.
   - Access governed by `ContentPolicy@view`.
   - Responses MUST include:
     - `Content-Disposition: attachment; filename="<sanitized-title>.<ext>"`
     - `X-Content-Type-Options: nosniff`
     - Cache-Control: `private, no-cache` for drafts.
3. **No External URL Redirection:**
   - `DocumentDownloadController` MUST NOT redirect external URLs (prevents open-redirect attacks).
   - External documents are rendered as direct external links in Blade views via `$item->download_url`.
4. **Header Injection Prevention:**
   - Filenames for download MUST be sanitized by stripping CRLF (`\r`, `\n`), quotes, and non-alphanumeric ASCII characters. The file extension MUST be extracted from the verified physical file on disk.

### C. Donation & Webhook Domain (SEC-009)
1. **Status State Machine (`DonationStatus`):**
   - `pending` → `success` | `failed` | `expired`.
   - `success` is **terminal and immutable** (cannot be altered by late/out-of-order webhooks).
   - `failed` and `expired` are terminal.
   - Replay of the identical status is idempotent (allowed, no-op).
2. **Concurrency Protection:**
   - Status updates in `DonationService::processWebhook()` MUST run inside a `DB::transaction()` with pessimistic locking (`lockForUpdate()`).
3. **Webhook Verification (`POST /donasi/webhook`):**
   - Optional source-IP allowlist (`MIDTRANS_WEBHOOK_IP_WHITELIST`, default **off**).
   - SHA512 signature verified: `hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey)`.
   - Gross amount in webhook payload MUST match the stored database amount.
   - The signature does not cover `transaction_status`/`fraud_status`, so the final status is fetched from the Midtrans Status API (`MIDTRANS_VERIFY_WEBHOOK_STATUS=true`). Status API unreachable → `503` so Midtrans retries.
   - Status mapping goes only through `DonationStatus::fromMidtrans($status, $fraudStatus)` (`capture`+`challenge` stays `pending`, `fraud_status=deny` → `failed`).
4. **Reconciliation:** `walhi:donations-reconcile` (every 15 min) checks stale `pending` donations against the Status API and expires them after 24 h.

### D. Authentication & Administration
1. **Custom Login Path:**
   - Admin login path defaults to `/portal-jabar` (configurable via `ADMIN_LOGIN_PATH` in `.env`).
   - Default route `/login` is intentionally mapped to `abort(404)`.
   - Public user registration is disabled (`abort(404)`).
2. **Admin User Creation:**
   - CLI command `walhi:create-admin` does not accept a `--password` parameter.
   - Passwords must be entered via double masked prompt and validated with `PasswordRule::defaults()` (min 12 chars, mixed case, numbers, symbols, not breached).
   - `AdminUserSeeder` never overwrites an existing password (unless `FORCE_ADMIN_PASSWORD_RESET=true`).
3. **Login:** exact email, or username = local part on an official domain. No `LIKE`/name matching. Throttled per account + IP.
4. **Two-Factor Authentication:** TOTP is mandatory for `admin`/`editor` when `AUTH_2FA_ENFORCED=true` (default). The `two-factor` middleware redirects unenrolled accounts to `/two-factor`. Reset with `php artisan walhi:reset-2fa <email>`.

### E. Newsletter, Comments & Privacy
1. Newsletter uses double opt-in (signed link valid 7 days); only confirmed subscribers are exported. The link is signed over the path only and checked by `ValidateSignatureAnyHost`, because it is built in the queue worker from `APP_URL` and the bare domain redirects to `www`.
2. Comments and newsletter: honeypot + rate limit + optional Cloudflare Turnstile (`Turnstile::rules()` in the FormRequest, `<x-turnstile />` in the form).
3. `walhi:prune-personal-data` (monthly) anonymises donors/commenters older than `PRIVACY_RETENTION_MONTHS` and deletes unconfirmed subscribers.

### F. Caching & Counters
1. Never cache Eloquent objects; use `ContentCache::remember()` (stores arrays). `ContentObserver` flushes content caches on save/delete.
2. View counts are buffered in the cache by `ViewCounter` and written by `walhi:flush-views`.

---

## 5. Security Guardrails for AI Agents

Whenever making changes, an AI agent MUST uphold:

1. **SQL Injection:** Always use Eloquent or query builder parameter bindings. Never concatenate variables into raw SQL. Refer to `.agents/rules/secure_coding.md`.
2. **XSS Prevention:**
   - In Blade views, use `{{ $variable }}` for automatic escaping.
   - For rich-text HTML bodies, use `{!! $item->sanitized_body !!}` (sanitized by HTMLPurifier). Never use `{!! $item->body !!}`.
3. **CSRF Protection:**
   - All `POST`, `PUT`, `PATCH`, `DELETE` routes require `@csrf` or `X-CSRF-TOKEN`.
   - Only `donasi/webhook` is exempted from CSRF verification in `bootstrap/app.php`.
4. **CSP Nonce & Inline Handlers:**
   - Dynamic cryptographic nonce is generated via `Vite::cspNonce()`.
   - Any inline script tag MUST include `nonce="{{ Vite::cspNonce() }}"`.
   - Inline event handlers (`onclick=` etc.) are blocked (`script-src-attr 'none'`). Use `data-on-click` / `data-confirm` (see `resources/js/csp-actions.js`) or Alpine directives.
   - Never add `'unsafe-inline'` / `'unsafe-eval'` to `script-src` or allow a whole CDN host.
5. **URL Allowlisting (SEC-006):**
   - Media URLs must reject protocol-relative URLs (`//evil.com`, `///evil.com`) and dangerous schemes (`javascript:`, `data:`, `vbscript:`).
   - Only allow registered prefixes: `/storage/`, `/uploads/`, `/documents/`, `/assets/`, or valid `http://`/`https://` URLs.
   - Link URLs entered by admins (banner buttons, campaign links) MUST pass `App\Rules\SafeUrl` (`http(s)://`, relative `/…`, `#…`).
6. **Audit Trail (AuditLogService):**
   - Any create, update, delete, status change or export on `Content`, `Comment`, or `Subscriber`, and any 2FA change, MUST trigger `AuditLogService::log()` (written to the `audit_logs` table and the `audit` log channel).
7. **Caching:** Never put Eloquent objects in the cache (Laravel 13 refuses to unserialize them → HTTP 500). Use `ContentCache::remember()`.
8. **Secrets:** Read settings through `config()`, never `env()` outside `config/`. Never print or commit credentials; gitleaks runs in CI.

---

## 6. Testing Guide & Verification Protocol

The application contains **307 automated tests (1333 assertions)**, all passing. CI runs them on SQLite and MySQL 8.0.

### Running Tests:
```bash
# Run all tests
php artisan test

# Run a specific domain test suite
php artisan test --filter=DocumentStorageTest
php artisan test --filter=RbacMatrixTest
php artisan test --filter=SecurityTest
php artisan test --filter=DonationTest
php artisan test --filter=AdminTest
php artisan test --filter=AuthenticationTest
php artisan test --filter=AuthHardeningTest
php artisan test --filter=DonationGatewayHardeningTest
php artisan test --filter=ContentSecurityHardeningTest
php artisan test --filter=LoggingPerformanceDataTest
php artisan test --filter=TurnstileCaptchaTest
```

### Test Suite Directory Map:
- `tests/Feature/DocumentStorageTest.php`: SEC-008 private storage, authorized download, header injection, migration.
- `tests/Feature/RbacMatrixTest.php`: SEC-007 / SEC-010 role permissions matrix (Admin, Editor, Subscriber).
- `tests/Feature/SecurityTest.php`: SEC-005, SEC-006, SEC-011, SEC-012 comprehensive security checks.
- `tests/Feature/DonationTest.php`: SEC-009 / SEC-013 state machine, webhook verification, concurrency locking.
- `tests/Feature/AdminUserCommandTest.php`: SEC-003 / SEC-004 CLI admin creation and password complexity.
- `tests/Feature/AdminTest.php`: Admin CRUD operations, filters, status toggling.
- `tests/Feature/Auth/*`: Authentication, password reset, confirmation, and rate limiting.
- `tests/Feature/Auth/AuthHardeningTest.php`: exact login matching, per-account throttle, 2FA, safe seeder, last-admin protection.
- `tests/Feature/ContentSecurityHardeningTest.php`: SafeUrl, sanitise-on-store, strict CSP, no inline handlers.
- `tests/Feature/DonationGatewayHardeningTest.php`: fraud_status, Status API confirmation, reconciliation, IP allowlist.
- `tests/Feature/LoggingPerformanceDataTest.php`: audit_logs, FULLTEXT search, caching, view buffer, double opt-in, data retention.
- `tests/Feature/TurnstileCaptchaTest.php`, `tests/Feature/TrustedProxyTest.php`, `tests/Feature/QaAuditRegressionTest.php`.
- `tests/Feature/StorageIntegrityAuditTest.php`: shared-file deletion guard, private document references, nginx `/storage/documents/` block, safe legacy migration, sensitive draft preview.
- `tests/Feature/MissingDocumentDisplayTest.php`: a document whose file is missing shows a disabled "Dokumen belum tersedia" button, never a link that ends in 404.

Full catalogue and conventions (fakes for Midtrans/Turnstile, `withoutVite()`): [docs/testing.md](docs/testing.md).

---

## 7. Change Workflow (Step-by-Step for AI Agents)

Before touching any code:

1. **Locate Domain:** Identify which domain is affected (`Content`, `Donation`, `Storage`, `Auth`, `UI`).
2. **Read Relevant Docs:**
   - Architecture: [ARCHITECTURE.md](ARCHITECTURE.md)
   - Domain Invariants: [docs/domain-rules.md](docs/domain-rules.md)
   - Database Schema: [docs/database.md](docs/database.md)
   - Frontend Guidelines: [docs/frontend.md](docs/frontend.md)
   - Security Rules: [.agents/rules/secure_coding.md](.agents/rules/secure_coding.md)
   - Configuration (`.env`): [docs/configuration.md](docs/configuration.md)
   - Operations / deploy: [docs/operations.md](docs/operations.md), [DEPLOYMENT.md](DEPLOYMENT.md)
3. **Inspect Related Tests:** Read corresponding test file in `tests/Feature/`.
4. **Verify Authorization:** Ensure Laravel Policy checks (`ContentPolicy`) or middleware checks (`role:...`) are present.
5. **Make Minimal Safe Change:** Do not refactor unrelated files or rewrite working patterns.
6. **Automatic review:** every PR is checked by `walhijabar_reviewbot` ([docs/reviewbot.md](docs/reviewbot.md)); a blocker fails the check. Run it locally with `python3 .github/reviewbot/walhijabar_reviewbot.py --base origin/production --head HEAD --no-annotations`.
7. **Branch from `production`:** `git switch -c <type>/<name> origin/production`. PRs target `production`; never commit to `main` (it is fast-forwarded automatically) or `prod` (deprecated). Merging to `production` deploys to the live server.

After modifying code:

1. **Run Static Checks:** `php -l <modified-file>` to guarantee zero syntax errors.
2. **Run Domain Tests:** `php artisan test --filter=<DomainTest>`.
3. **Run Full Test Suite:** `php artisan test` (must pass 100%), plus `vendor/bin/pint --test` and `npm run build` when PHP or frontend files change.
4. **Migrations:** must be safe while the previous release is still serving (deploy migrates before swapping containers); guard MySQL-only statements by driver.
5. **Inspect Git Diff:** `git diff` to verify no accidental changes, no exposed secrets, and clean formatting.

---

## 8. Definition of Done (DoD)

An AI coding agent must NOT declare a task completed until all criteria are met:

- [ ] **Architectural Compliance:** Changes align with verified Laravel 13 architecture and patterns.
- [ ] **Input Validation:** FormRequest or controller validation covers all inputs with strict rules.
- [ ] **Authorization Checked:** Action is explicitly guarded by Policies or RBAC middleware.
- [ ] **Security Intact:** No SQLi, XSS, CSRF bypass, open redirect, or path traversal vectors introduced.
- [ ] **Storage Discipline:** Documents go to private storage; public media goes to public storage.
- [ ] **Automated Tests:** Relevant feature tests added or updated, and all 299+ tests pass (`php artisan test`).
- [ ] **Zero Secrets:** No API keys, passwords, or tokens hardcoded in source code or committed files.
- [ ] **Documentation Synchronized:** If business rules, endpoints, or configurations change, documentation is updated immediately.
- [ ] **Clean Diff:** `git diff` reviewed and verified free of unintentional edits.
