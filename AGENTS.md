# 🤖 AI Agent Guidelines & Navigation System

**Repository:** `walhijabarhub/WalhiJBRWeb`  
**Platform:** Official Web Platform of WALHI Jawa Barat (Wahana Lingkungan Hidup Indonesia — Jawa Barat)  
**Primary Language:** PHP 8.4+ / Laravel 13.x  
**Target Environments:** Local (macOS/Linux/Docker), Production (Ubuntu VPS / Docker Compose)

This document is the **primary entrypoint and operating manual** for modern AI coding agents (Claude Code, OpenAI Codex, Gemini CLI, Cursor, and similar systems). It defines verified architectural patterns, directory structures, business invariants, security constraints, and change protocols.

---

## 1. Verified Technology Stack

| Layer | Technology | Verified Version | Notes |
| :--- | :--- | :--- | :--- |
| **Runtime** | PHP | `^8.3` (Installed: `8.4` / `8.5`) | Typed parameters, return types, enums. |
| **Framework** | Laravel | `^13.8` (Installed: `13.18.1`) | Modern callable `bootstrap/app.php`. |
| **Testing** | PHPUnit | `^12.5.12` | 109 automated tests in `tests/Feature/`. |
| **Frontend Build** | Vite | `^8.0.0` (Installed: `8.1.3`) | `laravel-vite-plugin ^3.1`. |
| **Styling** | Tailwind CSS | `3.4.19` | PostCSS pipeline, Neo-Brutalist design tokens. |
| **Reactivity** | Alpine.js | `^3.4.2` (Installed: `3.15.12`) | Minimalist UI state in Blade templates. |
| **Icons** | Lucide Icons | CDN / Inline SVG | Data attributes: `data-lucide="..."`. |
| **Database** | SQLite / MySQL | SQLite (local/tests), MySQL 8.0+ (prod) | SoftDeletes enabled on Content, Donation, Subscriber. |
| **Security Sanitizer**| HTMLPurifier | `^4.19` | Configured on `Content::getSanitizedBodyAttribute()`. |

> [!IMPORTANT]
> **Version Discrepancy Notice:**  
> Legacy documentation may reference "Laravel 12". The codebase has been verified and upgraded to **Laravel 13.x**. Always write code and configuration adhering to Laravel 13 conventions.

---

## 2. Directory Structure & Semantic Responsibilities

```text
walhi_larafel/
├── .agents/
│   └── rules/
│       └── secure_coding.md       # Enforced coding & security rules for AI agents
├── app/
│   ├── Console/Commands/          # Artisan CLI commands (walhi:create-admin, walhi:migrate-documents, etc.)
│   ├── Enums/                     # Strongly-typed Enums: ContentCategory, ContentStatus, DonationStatus, UserRole
│   ├── Http/
│   │   ├── Controllers/           # Public & Base HTTP controllers
│   │   │   ├── Admin/             # Backoffice CMS controllers (ContentController, AdminCommentController, etc.)
│   │   │   └── Auth/              # Breeze authentication controllers (custom login path)
│   │   ├── Middleware/            # SecurityHeaders (CSP nonce), CheckRole (RBAC)
│   │   └── Requests/              # FormRequest validation rules (Admin, Auth, Comment, Donation, Newsletter)
│   ├── Models/                    # Eloquent models: Content, Comment, Donation, Subscriber, User
│   ├── Policies/                  # Authorization policies: ContentPolicy
│   └── Services/                  # Business services: DonationService, MidtransService, SlugService, AuditLogService
├── bootstrap/
│   └── app.php                    # Laravel 13 routing, middleware, trustProxies, exception handling
├── config/                        # Laravel config (filesystems, session, security, seotools, etc.)
├── database/
│   ├── migrations/                # Database schema definitions
│   └── seeders/                   # Seeders (AdminUserSeeder, ContentSeeder, DatabaseSeeder)
├── docs/                          # In-depth architectural & domain knowledge for AI agents
│   ├── architecture/              # Detailed system architecture and data flows
│   ├── domain-rules.md            # Verified business invariants and lifecycle states
│   ├── database.md                # Schema documentation, enum casts, and relationships
│   ├── frontend.md                # Design system tokens, Blade structure, and Alpine.js
│   └── testing.md                 # Test suite catalog, execution guide, and mocking protocols
├── public/                        # Public webroot (index.php, static assets, storage symlink)
├── resources/
│   ├── css/app.css                # Base stylesheet, typography (Montserrat & Aspekta), Tailwind layers
│   ├── js/app.js                  # Alpine.js initialization entrypoint
│   └── views/                     # Blade views (admin/, auth/, components/, layouts/, partials/, public pages)
├── routes/
│   ├── web.php                    # HTTP routes (public pages, documents download, admin backoffice)
│   ├── auth.php                   # Authentication routes (customizable /portal-jabar path)
│   └── console.php                # Scheduled commands & console closures
├── storage/
│   ├── app/
│   │   ├── private/documents/     # SEC-008: Private storage for sensitive/internal documents (PDF, DOC, XLS)
│   │   └── public/uploads/        # Public media storage symlinked to public/storage
│   └── logs/                      # Application & daily audit trail logs
└── tests/
    ├── Feature/                   # Integration, RBAC, Donation, Security, and Storage feature tests
    └── TestCase.php               # Base PHPUnit test case configuration
```

---

## 3. High-Level Request Flow

```text
HTTP Request
    ↓
Nginx / Web Server (SSL, static files, nosniff, script block in /storage/)
    ↓
bootstrap/app.php (TrustProxies, TrustHosts, CSRF Verification)
    ↓
Global Middleware: SecurityHeaders (CSP nonce injection, X-Frame-Options, X-Content-Type-Options)
    ↓
Routing (routes/web.php / routes/auth.php)
    ├─ Parameter Validation (e.g. {category} constrained by ContentCategory enum regex)
    └─ Route Middleware (throttle, auth, role:admin,editor)
    ↓
Form Request Validation (e.g. StoreContentRequest, positive URL allowlist)
    ↓
Controller Action (e.g. ContentController, DocumentDownloadController)
    ↓
Policy Authorization ($this->authorize('view'/'update'/'delete', $content))
    ↓
Service Layer (DonationService, SlugService, AuditLogService)
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
3. **Webhook Verification:**
   - Midtrans IP whitelist enforced in production.
   - SHA512 signature verified: `hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey)`.
   - Gross amount in webhook payload MUST match the stored database amount.

### D. Authentication & Administration
1. **Custom Login Path:**
   - Admin login path defaults to `/portal-jabar` (configurable via `ADMIN_LOGIN_PATH` in `.env`).
   - Default route `/login` is intentionally mapped to `abort(404)`.
   - Public user registration is disabled (`abort(404)`).
2. **Admin User Creation:**
   - CLI command `walhi:create-admin` does not accept a `--password` parameter.
   - Passwords must be entered via double masked prompt and validated with `PasswordRule::defaults()` (min 12 chars, mixed case, numbers, symbols).

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
4. **CSP Nonce:**
   - Dynamic cryptographic nonce is generated via `Vite::cspNonce()`.
   - Any inline script tag MUST include `nonce="{{ Vite::cspNonce() }}"`.
5. **URL Allowlisting (SEC-006):**
   - Media URLs must reject protocol-relative URLs (`//evil.com`, `///evil.com`) and dangerous schemes (`javascript:`, `data:`, `vbscript:`).
   - Only allow registered prefixes: `/storage/`, `/uploads/`, `/documents/`, `/assets/`, or valid `http://`/`https://` URLs.
6. **Audit Trail (AuditLogService):**
   - Any create, update, or delete on `Content`, `Comment`, or `Subscriber` MUST trigger `AuditLogService::log()`.

---

## 6. Testing Guide & Verification Protocol

The application contains **109 automated tests (513 assertions)**, achieving 100% pass status.

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
```

### Test Suite Directory Map:
- `tests/Feature/DocumentStorageTest.php`: SEC-008 private storage, authorized download, header injection, migration.
- `tests/Feature/RbacMatrixTest.php`: SEC-007 / SEC-010 role permissions matrix (Admin, Editor, Subscriber).
- `tests/Feature/SecurityTest.php`: SEC-005, SEC-006, SEC-011, SEC-012 comprehensive security checks.
- `tests/Feature/DonationTest.php`: SEC-009 / SEC-013 state machine, webhook verification, concurrency locking.
- `tests/Feature/AdminUserCommandTest.php`: SEC-003 / SEC-004 CLI admin creation and password complexity.
- `tests/Feature/AdminTest.php`: Admin CRUD operations, filters, status toggling.
- `tests/Feature/Auth/*`: Authentication, password reset, confirmation, and rate limiting.

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
3. **Inspect Related Tests:** Read corresponding test file in `tests/Feature/`.
4. **Verify Authorization:** Ensure Laravel Policy checks (`ContentPolicy`) or middleware checks (`role:...`) are present.
5. **Make Minimal Safe Change:** Do not refactor unrelated files or rewrite working patterns.

After modifying code:

1. **Run Static Checks:** `php -l <modified-file>` to guarantee zero syntax errors.
2. **Run Domain Tests:** `php artisan test --filter=<DomainTest>`.
3. **Run Full Test Suite:** `php artisan test` (must pass 100%).
4. **Inspect Git Diff:** `git diff` to verify no accidental changes, no exposed secrets, and clean formatting.

---

## 8. Definition of Done (DoD)

An AI coding agent must NOT declare a task completed until all criteria are met:

- [ ] **Architectural Compliance:** Changes align with verified Laravel 13 architecture and patterns.
- [ ] **Input Validation:** FormRequest or controller validation covers all inputs with strict rules.
- [ ] **Authorization Checked:** Action is explicitly guarded by Policies or RBAC middleware.
- [ ] **Security Intact:** No SQLi, XSS, CSRF bypass, open redirect, or path traversal vectors introduced.
- [ ] **Storage Discipline:** Documents go to private storage; public media goes to public storage.
- [ ] **Automated Tests:** Relevant feature tests added or updated, and all 109+ tests pass (`php artisan test`).
- [ ] **Zero Secrets:** No API keys, passwords, or tokens hardcoded in source code or committed files.
- [ ] **Documentation Synchronized:** If business rules, endpoints, or configurations change, documentation is updated immediately.
- [ ] **Clean Diff:** `git diff` reviewed and verified free of unintentional edits.
