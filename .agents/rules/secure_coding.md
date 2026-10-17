# Secure Coding Rules & Architectural Guardrails

This rule is **Always On** across all development, refactoring, and feature additions in this repository. AI coding agents and human engineers must adhere to these defensive patterns to prevent regressions against audited security findings (SEC-001 through SEC-014 and the October 2026 audit, issues #60–#94).

---

## 1. Database Operations & SQL Injection Prevention
* **Mandatory Prepared Statements**: Always use Eloquent ORM or Query Builder parameter binding (`where()`, `find()`, `insert()`, `update()`).
* **Raw Queries**: Minimize `DB::raw()`, `whereRaw()`, etc. If required, bindings are mandatory:
  * ❌ **INCORRECT**:
    ```php
    $items = Content::whereRaw("title LIKE '%" . $query . "%'")->get();
    ```
  * ✅ **CORRECT**:
    ```php
    $items = Content::where('title', 'like', "%{$query}%")->get();
    // Or if raw query is strictly necessary:
    $items = Content::whereRaw("title LIKE ?", ["%{$query}%"])->get();
    ```
* **Exception Handling**: Never leak raw SQL query strings, column names, or connection credentials to API or Blade responses. Let exceptions bubble to the global handler in `bootstrap/app.php` or log securely via `Log::error()`.

---

## 2. File Storage & Document Access Control (SEC-008)
* **Storage Separation**:
  * **Public Media**: Images (`jpg`, `jpeg`, `png`, `webp`, `gif`) are stored on disk `'public'` under `storage/app/public/uploads/`.
  * **Private Documents**: Downloadable document files (`pdf`, `doc`, `docx`, `xls`, `xlsx`, `csv`) **MUST** be stored on the `'local'` disk under `storage/app/private/documents/`.
* **Zero Public Symlink for Documents**: Never store downloadable documents in `storage/app/public/` or generate direct symlink URLs (`asset('storage/documents/...')`).
* **Serving Documents**:
  * Documents must be served exclusively through `DocumentDownloadController` via `/dokumen/{content:slug}/unduh`.
  * Controller MUST check authorization (`$this->authorize('view', $content)`).
  * Controller MUST send `X-Content-Type-Options: nosniff` header and force `Content-Disposition: attachment`.

---

## 3. Redirects & Positive URL Allowlisting (SEC-006)
* **Open Redirect Prevention**: Never pass unvalidated user input directly into `redirect()` or `redirect()->to()`.
* **Protocol-Relative URLs**: Reject URLs starting with `//` or `///` which browsers resolve across schemes.
* **Positive Host Validation**: When redirecting to external targets, strictly allowlist trusted domain origins (e.g. verified WALHI partners or Midtrans redirect hosts). Use `redirect()->route()` or internal paths whenever possible.
* **Stored URLs**: Any URL saved from admin input (banner buttons, campaign links, media URLs) must pass `App\Rules\SafeUrl` in the FormRequest, and be re-checked with `SafeUrl::sanitize()` when rendered (legacy rows may predate validation).

---

## 4. Authorization & Input Validation (SEC-007, SEC-010)
* **Centralized Policies**: Never write ad-hoc authorization logic in controllers (e.g. `if (auth()->user()->is_admin)`). Use `ContentPolicy` and call `$this->authorize('action', $model)` or `Gate::authorize(...)`.
* **Backed Enum Validation**: Category inputs must always be validated against `App\Enums\ContentCategory` using `Rule::enum(ContentCategory::class)` to prevent category injection.
* **Status Transitions**: Content status changes (`draft` / `published` / `archived`) and donation status transitions (`pending` → `success` | `failed` | `expired`, all terminal) must be validated against their enums. Map Midtrans statuses only through `DonationStatus::fromMidtrans($transactionStatus, $fraudStatus)`.
* **Admin area middleware**: New admin routes go inside the existing `auth`, `verified`, `two-factor` group and carry `role:admin,editor` or `role:admin`. Destructive and export actions are `role:admin`.

---

## 5. Content Security Policy & Script Nonce Discipline (SEC-005)
* **CSP Nonce Injection**: Every `<script>` tag rendered in Blade views must include the dynamic nonce:
  ```blade
  <script nonce="{{ Vite::cspNonce() }}">
  ```
* **No inline event handlers**: The CSP sends `script-src-attr 'none'`; `onclick="..."`, `onsubmit="..."` etc. are blocked by the browser. Use Alpine directives or the `data-on-click` / `data-confirm` / `data-toggle-target` attributes handled by `resources/js/csp-actions.js`.
* **Alpine CSP build**: The app uses `@alpinejs/csp` (no `eval`). Register non-trivial components with `Alpine.data()` in `resources/js/app.js`.
* **Third-party scripts**: Add them to `SecurityHeaders` at an exact, versioned path (never a whole CDN host) and load them with `nonce` + Subresource Integrity (`integrity="..."`, `crossorigin="anonymous"`).
* **No `unsafe-inline` / `unsafe-eval`** may be added to `script-src`. `ContentSecurityHardeningTest` enforces this.

---

## 6. Financial Data Concurrency & Idempotency (SEC-009)
* **Pessimistic Row Locking**: `DonationService::processWebhook()` wraps the status update in `DB::transaction()` and calls `lockForUpdate()` on the `donations` row. Keep any new status writer on this path.
* **Signature Verification**: `DonationController::webhook` (`POST /donasi/webhook`) validates the SHA-512 signature (`hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey)`) and the stored amount before any database mutation.
* **Do not trust the notification body**: The signature does not cover `transaction_status` / `fraud_status`. With `MIDTRANS_VERIFY_WEBHOOK_STATUS=true` (default) the final status comes from the Midtrans Status API (`DonationService::syncFromGateway`).
* **CSRF Boundary**: Web forms require `@csrf`. Only `donasi/webhook` is exempt in `bootstrap/app.php`.
* **Outbound HTTP**: Calls to Midtrans and other services must set `timeout()` / `connectTimeout()`; never block a request indefinitely.

---

## 7. HTML Sanitisation (XSS)
* Rich-text bodies are sanitised **on write** with `App\Services\Content\HtmlSanitizer::forStorage()` and **on read** with `$content->sanitized_body`.
* Render with `{!! $item->sanitized_body !!}`; never `{!! $item->body !!}` or `{!! $anyUserInput !!}`.
* In JavaScript, never assign server data to `innerHTML`. In Quill editors use `quill.clipboard.dangerouslyPasteHTML()` with server-sanitised HTML.

---

## 8. Caching
* Never store Eloquent models or other objects directly in the cache: Laravel 13 rejects them on unserialize (`cache.serializable_classes=false`), which caused a production 500. Use `App\Services\Content\ContentCache::remember()` or cache plain arrays/scalars.
* When the shape of cached data changes, bump the key version (e.g. `:v2` → `:v3`).

---

## 9. Audit Trail & Personal Data
* Every create, update, delete, status change or export performed from the admin area calls `AuditLogService::log($action, $model, $id, $details)`. Do not put secrets, passwords or full personal data in `$details`.
* Personal data (donor, commenter, subscriber) must stay covered by `walhi:prune-personal-data`. New personal-data columns need a retention rule in `config/privacy.php`.

---

## 10. Secrets & Configuration
* Never commit `.env`, keys, passwords or tokens; gitleaks scans the full history in CI.
* Read configuration through `config()`; `env()` is only allowed inside `config/*.php` (config is cached in production). The one exception is `TRUSTED_PROXIES` in `bootstrap/app.php`, which is read before configuration is loaded.
* Never print or log passwords, TOTP secrets, recovery codes, API keys or webhook payload signatures.
