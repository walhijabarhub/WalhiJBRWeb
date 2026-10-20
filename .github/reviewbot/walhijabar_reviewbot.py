#!/usr/bin/env python3
"""walhijabar_reviewbot — automatic pull request review for WalhiJBRWeb.

Checks the lines a pull request adds against the project's own rules
(AGENTS.md, .agents/rules/secure_coding.md, docs/domain-rules.md) and reports:

  * GitHub annotations on the exact lines (visible in "Files changed"),
  * a Markdown report (written to --report, posted as one sticky PR comment),
  * exit code 1 when a BLOCKER is found, so the check can be made required.

Only the Python standard library is used. The bot never prints secret values it
finds; it reports the file and line only.

Usage:
  walhijabar_reviewbot.py --base <sha> --head <sha> [--base-ref production]
                          [--repo-dir .] [--report report.md]
"""

import argparse
import os
import re
import subprocess
import sys
from collections import namedtuple

BOT_NAME = "walhijabar_reviewbot"
MARKER = "<!-- walhijabar_reviewbot -->"

BLOCKER, WARNING, INFO = "blocker", "warning", "info"
SEVERITY_ORDER = {BLOCKER: 0, WARNING: 1, INFO: 2}
SEVERITY_LABEL = {BLOCKER: "🛑 Blocker", WARNING: "⚠️ Peringatan", INFO: "💡 Catatan"}

Finding = namedtuple("Finding", "severity rule path line message")
AddedLine = namedtuple("AddedLine", "path line text")


# ── Diff helpers ──────────────────────────────────────────────────────────────

def git(repo_dir, *args):
    return subprocess.run(
        ["git", "-C", repo_dir, *args], check=True, capture_output=True, text=True
    ).stdout


def changed_files(repo_dir, base, head):
    """Return {path: status} for files changed between the merge base and head."""
    out = git(repo_dir, "diff", "--name-status", "--no-renames", f"{base}...{head}")
    files = {}
    for row in out.splitlines():
        parts = row.split("\t")
        if len(parts) >= 2:
            files[parts[-1]] = parts[0][0]
    return files


def added_lines(repo_dir, base, head):
    """Yield every added line with its line number in the head version."""
    out = git(repo_dir, "diff", "--unified=0", "--no-renames", "--no-color", f"{base}...{head}")
    path, line_no = None, 0
    for raw in out.splitlines():
        if raw.startswith("+++ "):
            target = raw[4:]
            path = target[2:] if target.startswith("b/") else None
        elif raw.startswith("@@"):
            match = re.search(r"\+(\d+)", raw)
            line_no = int(match.group(1)) if match else 0
        elif raw.startswith("+") and path:
            yield AddedLine(path, line_no, raw[1:])
            line_no += 1


def read_head_file(repo_dir, head, path):
    try:
        return git(repo_dir, "show", f"{head}:{path}")
    except subprocess.CalledProcessError:
        return ""


# ── Rules ─────────────────────────────────────────────────────────────────────

def is_blade(path):
    return path.endswith(".blade.php")


def is_php(path):
    return path.endswith(".php") and not is_blade(path)


def is_app_php(path):
    return is_php(path) and path.startswith(("app/", "routes/", "bootstrap/", "database/"))


def is_js(path):
    return path.startswith("resources/js/") and path.endswith((".js", ".ts"))


def is_test(path):
    return path.startswith("tests/")


def is_workflow(path):
    return path.startswith(".github/workflows/") and path.endswith((".yml", ".yaml"))


SAFE_RAW_OUTPUT = re.compile(r"sanitized_body|SEO::generate\(\)|\$qrCodeSvg|json_encode|@json|Js::from")
SECRET_PATTERNS = [
    re.compile(r"-----BEGIN (RSA |EC |OPENSSH |)PRIVATE KEY-----"),
    re.compile(r"\bgh[pousr]_[A-Za-z0-9]{30,}"),
    re.compile(r"\bgithub_pat_[A-Za-z0-9_]{40,}"),
    re.compile(r"\bAKIA[0-9A-Z]{16}\b"),
    re.compile(r"\b(SB-)?Mid-(server|client)-[A-Za-z0-9_\-]{10,}"),
    re.compile(r"\bsk-(ant-)?[A-Za-z0-9_\-]{20,}"),
    re.compile(r"\bxox[abprs]-[A-Za-z0-9\-]{10,}"),
    re.compile(r"\b0x4AAAA[A-Za-z0-9_\-]{10,}"),  # Cloudflare Turnstile secret
]
ASSIGNED_SECRET = re.compile(
    r"(?i)\b(password|passwd|secret|api_?key|server_?key|token)\b\s*(=|=>|:)\s*['\"][^'\"\s$]{8,}['\"]"
)
PLACEHOLDER = re.compile(r"(?i)(change[-_]?me|example|placeholder|your[-_]|xxx|\*\*\*|<.*>|dummy|secret_here|null)")
ENV_FILE = re.compile(r"(^|/)\.env(\.[A-Za-z0-9_-]+)?$")
ALLOWED_ENV_FILES = re.compile(r"\.example$")


def secret_findings(p, t):
    """Credential patterns; the value itself is never reported."""
    for i, pattern in enumerate(SECRET_PATTERNS):
        if i == 0 and p.endswith(".md"):
            continue  # docs explain where to paste a private key
        if pattern.search(t):
            if is_test(p) and re.search(r"(?i)test|fake|dummy|example", t):
                return []  # obvious fixture values in tests
            severity = WARNING if is_test(p) else BLOCKER
            return [(severity, "SEC-SECRET", "Kemungkinan kredensial/secret ikut ter-commit. Hapus, lalu rotasi kredensialnya.")]
    return []


def line_rules(add):
    """Rules that look at one added line."""
    p, t = add.path, add.text
    s = t.strip()
    found = secret_findings(p, t)
    yield from found
    if not s or s.startswith(("//", "#", "*", "/*", "{{--")):
        return  # comments do not execute
    if p.endswith(".md"):
        return  # documentation may quote the patterns it warns about

    if not found:
        if (is_php(p) or is_js(p) or p.endswith((".yml", ".yaml", ".sh", ".json"))) and not is_test(p) \
                and ASSIGNED_SECRET.search(t) and not PLACEHOLDER.search(t) and "${{" not in t:
            yield WARNING, "SEC-SECRET", "Nilai mirip password/token ditulis langsung di kode. Baca lewat config()/secret, jangan hardcode."

    # XSS: raw Blade output (secure_coding §7)
    if is_blade(p) and "{!!" in t and not SAFE_RAW_OUTPUT.search(t):
        if "->body" in t:
            yield BLOCKER, "XSS-RAW-BODY", "`{!! $item->body !!}` tidak aman. Pakai `{!! $item->sanitized_body !!}` (HTMLPurifier)."
        else:
            yield BLOCKER, "XSS-RAW", "Output Blade mentah `{!! !!}` tanpa sanitasi. Pakai `{{ }}` atau `sanitized_body`."

    # CSP: inline handlers and script nonce (secure_coding §5)
    if (is_blade(p) or p.endswith(".html")) and re.search(r"(?<![\w:-])on[a-z]{3,}\s*=\s*[\"']", t):
        yield BLOCKER, "CSP-INLINE-HANDLER", "Inline event handler (`onclick=` dll.) diblokir CSP `script-src-attr 'none'`. Pakai `data-on-click` / `data-confirm` (resources/js/csp-actions.js) atau Alpine."
    if is_blade(p) and re.search(r"<script\b", t, re.I) and "nonce=" not in t \
            and not re.search(r"type=[\"']application/(ld\+)?json", t, re.I):
        yield BLOCKER, "CSP-NONCE", "Tag `<script>` wajib memakai `nonce=\"{{ Vite::cspNonce() }}\"`."
    if re.search(r"<script\b[^>]*\bsrc=[\"']https?://", t, re.I) and "integrity=" not in t:
        yield INFO, "CSP-SRI", "Script pihak ketiga perlu `integrity` (SRI) + `crossorigin=\"anonymous\"` dan path versi yang pasti di SecurityHeaders."
    if not is_test(p) and ("unsafe-eval" in t or ("unsafe-inline" in t and "script-src" in t)):
        yield BLOCKER, "CSP-UNSAFE", "Jangan menambah `'unsafe-inline'`/`'unsafe-eval'` ke `script-src`."

    # Configuration: env() outside config/ (secure_coding §10)
    if is_php(p) and re.search(r"(?<![\w>$:])env\(", t) and not p.startswith("config/") \
            and not (p == "bootstrap/app.php" and "TRUSTED_PROXIES" in t) and not is_test(p):
        yield BLOCKER, "CFG-ENV", "`env()` hanya boleh di `config/*.php` (config di-cache di produksi). Pakai `config()`."

    # SQL injection (secure_coding §1)
    if is_php(p) and re.search(r"(whereRaw|orWhereRaw|selectRaw|orderByRaw|havingRaw|groupByRaw|DB::raw|DB::select|DB::statement|DB::unprepared)\s*\(", t):
        if re.search(r"\"[^\"]*\$[a-zA-Z_{]|\.\s*\$[a-zA-Z_]|\$[a-zA-Z_]\w*\s*\.", t):
            yield WARNING, "SQL-RAW-INTERP", "Raw SQL berisi variabel. Pastikan nilai dari input pengguna lewat binding `?`, bukan interpolasi."
        else:
            yield INFO, "SQL-RAW", "Raw SQL: pastikan parameter memakai binding dan query tetap jalan di SQLite & MySQL."

    # Storage discipline (SEC-008)
    if is_php(p) and not is_test(p) and "documents" in t and re.search(r"['\"]public['\"]", t) \
            and re.search(r"->(store|storeAs|storePublicly|storePubliclyAs|put|putFile|putFileAs|writeStream|move|copy)\(", t):
        yield BLOCKER, "SEC008-PUBLIC-DOC", "Dokumen wajib di disk `local` (`storage/app/private/documents`), bukan disk `public`."
    if (is_blade(p) or is_php(p)) and re.search(r"storage/documents", t) and not is_test(p) \
            and "MigrateLegacyDocuments" not in p and "ContentController" not in p and "Content.php" not in p:
        yield WARNING, "SEC008-STATIC-URL", "Jangan tautkan `/storage/documents/...` langsung. Pakai `$item->download_url` (`/dokumen/{slug}/unduh`)."

    # Open redirect (SEC-006)
    if is_php(p) and re.search(r"redirect\(\)->away\(|redirect\(\s*\$request|redirect\(\)->to\(\s*\$request|redirect\(\s*request\(", t):
        yield BLOCKER, "SEC006-REDIRECT", "Redirect ke URL dari input pengguna (open redirect). Pakai `redirect()->route()` atau allowlist host."

    # Authorization (secure_coding §4)
    if is_php(p) and p.startswith("app/Http/Controllers/") and \
            re.search(r"->(isAdmin|isEditor|canManageContent|canDelete)\(\)|->role\s*(===|==|!==|!=)", t):
        yield INFO, "AUTHZ-ADHOC", "Cek role ad-hoc di controller. Pakai Policy: `$this->authorize(...)` / `Gate::authorize(...)`."
    if is_php(p) and re.search(r"(->|::)(create|update|fill|forceFill|insert)\(\s*\$request->(all|input)\(\)\s*\)", t):
        yield BLOCKER, "MASS-ASSIGN", "Mass assignment dari `$request->all()`. Pakai `$request->validated()` dari FormRequest."
    if is_php(p) and "Rule::in(" not in t and re.search(r"['\"]category['\"]\s*=>\s*\[[^\]]*['\"](string|in:)", t) \
            and "enum" not in t.lower():
        yield WARNING, "ENUM-CATEGORY", "Validasi kategori wajib `Rule::enum(ContentCategory::class)`."

    # CSRF boundary
    if p == "bootstrap/app.php" and "validateCsrfTokens" in t:
        yield BLOCKER, "CSRF-EXCEPT", "Perubahan pengecualian CSRF. Hanya `donasi/webhook` yang boleh dikecualikan."
    if is_php(p) and "withoutMiddleware(" in t and not is_test(p):
        yield WARNING, "MIDDLEWARE-OFF", "`withoutMiddleware()` di kode aplikasi. Pastikan tidak mematikan auth/CSRF/2FA/throttle."

    # Donations (SEC-009)
    if is_php(p) and re.search(r"transaction_status['\"]?\]?\s*(===|==)\s*['\"]", t) and not p.endswith("DonationStatus.php"):
        yield BLOCKER, "SEC009-STATUS-MAP", "Pemetaan status Midtrans hanya lewat `DonationStatus::fromMidtrans($status, $fraudStatus)`."
    if is_php(p) and re.search(r"Http::", t) and not is_test(p) and "fake(" not in t:
        yield INFO, "HTTP-TIMEOUT", "Pemanggilan HTTP keluar wajib memakai `timeout()` / `connectTimeout()`."

    # Caching (Laravel 13 rejects objects on unserialize)
    if is_php(p) and not is_test(p) and re.search(r"(?<![\w\\])Cache::(remember|rememberForever|put|forever|add)\(", t) and "ContentCache" not in p:
        yield WARNING, "CACHE-OBJECT", "Jangan cache objek Eloquent (Laravel 13 menolak unserialize → HTTP 500). Simpan array/skalar atau pakai `ContentCache::remember()`."

    # Logging personal data / secrets
    if is_php(p) and re.search(r"(Log::\w+|logger\()", t) and re.search(r"(?i)\$\w*(password|secret|token|recovery|otp)\w*|->(password|two_factor_secret|two_factor_recovery_codes|remember_token)\b|signature_key", t):
        yield WARNING, "LOG-SECRET", "Jangan mencatat password, token, secret TOTP, recovery code, atau signature ke log."

    # Debug leftovers
    if (is_php(p) or is_blade(p)) and not is_test(p) and re.search(r"(?<![\w>:$])(dd|dump|var_dump|print_r|ray)\(", t):
        yield WARNING, "DEBUG", "Sisa debug (`dd`/`dump`/`var_dump`). Hapus sebelum merge."
    if is_js(p) and re.search(r"console\.(log|debug)\(", t):
        yield INFO, "DEBUG-JS", "Sisa `console.log`. Hapus bila tidak diperlukan."
    if (is_js(p) or is_blade(p)) and re.search(r"\.innerHTML\s*=|insertAdjacentHTML\(|document\.write\(", t):
        yield WARNING, "XSS-DOM", "Hindari `innerHTML`/`document.write` dengan data server. Pakai `textContent` atau elemen DOM."

    # Production configuration
    if re.search(r"APP_DEBUG\s*[:=]\s*[\"']?true", t, re.I) and not p.startswith("tests/") and ".env.example" not in p:
        yield BLOCKER, "CFG-DEBUG", "`APP_DEBUG=true` tidak boleh untuk produksi."

    # Workflows
    if is_workflow(p):
        if "pull_request_target" in t:
            yield BLOCKER, "CI-PRT", "`pull_request_target` menjalankan kode PR dengan token bertulis. Pakai `pull_request`."
        uses = re.search(r"uses:\s*([\w.\-]+/[\w.\-/]+)@([\w.\-]+)", t)
        if uses and not re.fullmatch(r"[0-9a-f]{40}", uses.group(2)):
            yield WARNING, "CI-PIN", f"Action `{uses.group(1)}` harus di-pin ke commit SHA (tag di komentar), sesuai konvensi repo."
        if re.search(r"\$\{\{\s*github\.event\.(pull_request|issue|comment|head_commit)\.[\w.]*(title|body|message|name|ref|label)", t) and "run:" in t:
            yield WARNING, "CI-INJECTION", "Data event PR dipakai langsung di `run:` (script injection). Teruskan lewat `env:`."


# File-level rules ----------------------------------------------------------------

MUTATION = re.compile(r"->(create|update|delete|forceDelete|restore|toggle\w*)\(|::create\(")


def file_rules(repo_dir, head, files, adds_by_file):
    for path, status in files.items():
        if status == "D":
            continue
        added_text = "\n".join(a.text for a in adds_by_file.get(path, []))

        if ENV_FILE.search(path) and not ALLOWED_ENV_FILES.search(path):
            yield Finding(BLOCKER, "SEC-ENV-FILE", path, None, "File `.env` tidak boleh di-commit. Hapus dari PR dan rotasi semua secret di dalamnya.")

        if path.startswith("app/Http/Controllers/Admin/") and MUTATION.search(added_text):
            content = read_head_file(repo_dir, head, path)
            if "AuditLogService::log" not in content:
                yield Finding(WARNING, "AUDIT-LOG", path, None, "Operasi tulis di controller admin tanpa `AuditLogService::log()` (wajib untuk create/update/delete/status/export).")

        if path.startswith("app/Http/Controllers/") and re.search(r"public function (store|update|destroy)\(", added_text):
            content = read_head_file(repo_dir, head, path)
            if not re.search(r"authorize\(|Gate::|FormRequest|Request \$request\)", content) and "Admin/" in path:
                yield Finding(WARNING, "AUTHZ-MISSING", path, None, "Aksi tulis baru tanpa `authorize()`/Policy yang terlihat.")

        if path.startswith("database/migrations/") and status == "A":
            content = read_head_file(repo_dir, head, path)
            if re.search(r"DB::statement|FULLTEXT|fullText\(|ALTER TABLE", content) and "getDriverName" not in content:
                yield Finding(WARNING, "MIGRATION-DRIVER", path, None, "Statement khusus MySQL di migration tanpa guard `DB::getDriverName()` (test memakai SQLite).")
            if re.search(r"dropColumn|renameColumn|Schema::drop|->change\(\)", content):
                yield Finding(WARNING, "MIGRATION-COMPAT", path, None, "Deploy menjalankan migration sebelum container lama diganti. Drop/rename kolom bisa merusak rilis yang masih berjalan; pecah menjadi dua rilis.")

        if path == "routes/web.php" and re.search(r"Route::(post|put|patch|delete)\(", added_text):
            yield Finding(INFO, "ROUTE-WRITE", path, None, "Route tulis baru: pastikan ada di grup `auth`+`verified`+`two-factor` dengan `role:`, punya `throttle`, dan aksi destruktif/export `role:admin`.")

        if path.startswith("app/Models/") and re.search(r"\$guarded\s*=\s*\[\s*\]", added_text):
            yield Finding(BLOCKER, "MASS-ASSIGN-GUARDED", path, None, "`$guarded = []` membuka mass assignment. Pakai `$fillable` yang eksplisit.")

        if path.startswith("app/Models/") and re.search(r"'(email|phone|donor_\w+|ip_address|name)'", added_text) \
                and "config/privacy.php" not in files:
            yield Finding(INFO, "PRIVACY-RETENTION", path, None, "Kolom data pribadi baru? Tambahkan aturan retensi di `config/privacy.php` (`walhi:prune-personal-data`).")


def pr_rules(files, base_ref):
    paths = [p for p, s in files.items()]
    if base_ref in ("main", "prod"):
        yield Finding(BLOCKER, "BRANCH-TARGET", None, None, f"PR menargetkan `{base_ref}`. `production` adalah sumber kebenaran; `main` hanya mirror dan `prod` sudah tidak dipakai. Ubah base ke `production`.")

    app_changes = [p for p in paths if is_app_php(p) and not p.startswith("database/seeders/")]
    if app_changes and not any(is_test(p) for p in paths):
        yield Finding(WARNING, "TESTS-MISSING", None, None, "Kode aplikasi berubah tanpa test yang ditambah/diubah. Tambahkan feature test (lihat docs/testing.md).")

    behaviour = [p for p in paths if p.startswith(("routes/", "config/")) or p.startswith(".env") or p.endswith(".env.production.example")]
    if behaviour and not any(p.endswith(".md") for p in paths):
        yield Finding(INFO, "DOCS-SYNC", None, None, "Route/config/env berubah tanpa pembaruan dokumentasi (`docs/`, `AGENTS.md`, `DEPLOYMENT.md`).")

    infra = [p for p in paths if p in ("deploy.sh", "docker-compose.prod.yml", "Dockerfile", "docker-entrypoint.sh", "docker-backup.sh", "docker-restore.sh", ".github/workflows/deploy.yml") or p.startswith("docker/")]
    if infra:
        yield Finding(INFO, "INFRA-CHANGE", None, None, "File infrastruktur produksi berubah (" + ", ".join(f"`{p}`" for p in infra[:6]) + "). Merge ke `production` langsung deploy: pastikan ada backup, rencana rollback, dan review manual.")

    if any(p.startswith("database/migrations/") for p in paths):
        yield Finding(INFO, "MIGRATION", None, None, "Ada migration: deploy menjalankan `migrate --force` sebelum swap dan tidak di-rollback otomatis. Pastikan aman untuk rilis lama yang masih berjalan.")

    if any(p.startswith("public/build/") or p.startswith("vendor/") or p.startswith("node_modules/") for p in paths):
        yield Finding(BLOCKER, "BUILD-ARTIFACT", None, None, "Artefak build/dependency (`public/build`, `vendor`, `node_modules`) ikut ter-commit. Hapus dari PR.")


# ── Report ────────────────────────────────────────────────────────────────────

def annotate(f):
    level = {BLOCKER: "error", WARNING: "warning", INFO: "notice"}[f.severity]
    loc = ""
    if f.path:
        loc = f" file={f.path}" + (f",line={f.line}" if f.line else "")
    msg = f"[{f.rule}] {f.message}".replace("%", "%25").replace("\r", "%0D").replace("\n", "%0A")
    print(f"::{level}{loc},title={BOT_NAME}::{msg}" if loc else f"::{level} title={BOT_NAME}::{msg}")


def render(findings, files, server, repo, head):
    counts = {s: sum(1 for f in findings if f.severity == s) for s in SEVERITY_ORDER}
    lines = [MARKER, f"## 🌿 {BOT_NAME}", ""]
    if counts[BLOCKER]:
        verdict = f"**Belum siap merge** — {counts[BLOCKER]} blocker harus diperbaiki."
    elif counts[WARNING]:
        verdict = "**Boleh lanjut setelah peringatan ditinjau.**"
    elif counts[INFO]:
        verdict = f"**Tidak ada blocker atau peringatan.** ✅ Ada {counts[INFO]} catatan untuk ditinjau reviewer."
    else:
        verdict = "**Tidak ada temuan pada aturan proyek.** ✅"
    lines += [
        verdict, "",
        f"| {SEVERITY_LABEL[BLOCKER]} | {SEVERITY_LABEL[WARNING]} | {SEVERITY_LABEL[INFO]} | File berubah |",
        "|---|---|---|---|",
        f"| {counts[BLOCKER]} | {counts[WARNING]} | {counts[INFO]} | {len(files)} |", "",
    ]

    def link(f):
        if not f.path:
            return "PR"
        if server and repo and head:
            anchor = f"#L{f.line}" if f.line else ""
            return f"[`{f.path}{':' + str(f.line) if f.line else ''}`]({server}/{repo}/blob/{head}/{f.path}{anchor})"
        return f"`{f.path}{':' + str(f.line) if f.line else ''}`"

    for severity in (BLOCKER, WARNING, INFO):
        group = [f for f in findings if f.severity == severity]
        if not group:
            continue
        lines += [f"### {SEVERITY_LABEL[severity]} ({len(group)})", ""]
        shown = group[:40]
        for f in shown:
            lines.append(f"- {link(f)} — **{f.rule}**: {f.message}")
        if len(group) > len(shown):
            lines.append(f"- … dan {len(group) - len(shown)} lainnya (lihat anotasi di tab *Files changed*).")
        lines.append("")

    lines += [
        "<details><summary>Checklist reviewer manusia (Definition of Done)</summary>", "",
        "- [ ] Otorisasi lewat Policy/middleware `role:` (editor tidak bisa aksi khusus admin)",
        "- [ ] Input divalidasi FormRequest; URL admin lewat `SafeUrl`",
        "- [ ] Dokumen di disk `local`, media di disk `public`",
        "- [ ] Tidak ada XSS/SQLi/CSRF bypass/open redirect/path traversal",
        "- [ ] `AuditLogService::log()` untuk operasi tulis admin",
        "- [ ] Test ditambah dan `php artisan test`, `pint --test`, `npm run build` lulus",
        "- [ ] Migration aman untuk rilis lama yang masih berjalan",
        "- [ ] Dokumentasi disinkronkan",
        "", "</details>", "",
        "<sub>Aturan: AGENTS.md · .agents/rules/secure_coding.md · docs/domain-rules.md. "
        "Bot ini memeriksa pola, bukan pengganti review manusia. Komentar ini diperbarui di setiap push.</sub>",
    ]
    return "\n".join(lines)


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--base", required=True)
    parser.add_argument("--head", required=True)
    parser.add_argument("--base-ref", default=os.environ.get("GITHUB_BASE_REF", "production"))
    parser.add_argument("--repo-dir", default=".")
    parser.add_argument("--report", default="")
    parser.add_argument("--no-annotations", action="store_true")
    args = parser.parse_args()

    files = changed_files(args.repo_dir, args.base, args.head)
    adds = list(added_lines(args.repo_dir, args.base, args.head))
    adds_by_file = {}
    for a in adds:
        adds_by_file.setdefault(a.path, []).append(a)

    findings = []
    for a in adds:
        if a.path.startswith(".github/reviewbot/"):
            continue  # the rules themselves contain the patterns they look for
        for severity, rule, message in line_rules(a):
            findings.append(Finding(severity, rule, a.path, a.line, message))
    findings += list(file_rules(args.repo_dir, args.head, files, adds_by_file))
    findings += list(pr_rules(files, args.base_ref))

    # Outbound HTTP calls that set a timeout within a few lines are fine.
    cache = {}
    kept = []
    for f in findings:
        if f.rule == "HTTP-TIMEOUT" and f.path and f.line:
            content = cache.setdefault(f.path, read_head_file(args.repo_dir, args.head, f.path).splitlines())
            window = "\n".join(content[max(0, f.line - 4):f.line + 6])
            if re.search(r"timeout\(|connectTimeout\(", window):
                continue
        if f.rule == "CACHE-OBJECT" and f.path and f.line:
            # Only a cached Eloquent query is the Laravel 13 hazard; counters and arrays are fine.
            content = cache.setdefault(f.path, read_head_file(args.repo_dir, args.head, f.path).splitlines())
            window = "\n".join(content[f.line - 1:f.line + 6])
            if not re.search(r"->(get|first|firstOrFail|paginate|all|find)\(|::(find|where|query|with|latest|published|ofCategory)\(", window) \
                    or "->toArray()" in window or "->all()" in window and "->get(" not in window:
                continue
        kept.append(f)
    findings = kept

    # One finding per rule and line.
    unique = {}
    for f in findings:
        unique.setdefault((f.rule, f.path, f.line), f)
    findings = sorted(unique.values(), key=lambda f: (SEVERITY_ORDER[f.severity], f.path or "", f.line or 0))

    if not args.no_annotations:
        for f in findings:
            annotate(f)

    report = render(findings, files, os.environ.get("GITHUB_SERVER_URL", "https://github.com"),
                    os.environ.get("GITHUB_REPOSITORY", ""), args.head)
    if args.report:
        with open(args.report, "w", encoding="utf-8") as fh:
            fh.write(report + "\n")
    else:
        print(report)

    blockers = sum(1 for f in findings if f.severity == BLOCKER)
    print(f"{BOT_NAME}: {blockers} blocker, "
          f"{sum(1 for f in findings if f.severity == WARNING)} warning, "
          f"{sum(1 for f in findings if f.severity == INFO)} info", file=sys.stderr)
    return 1 if blockers else 0


if __name__ == "__main__":
    sys.exit(main())
