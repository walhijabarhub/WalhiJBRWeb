# Frontend Architecture & Design System Guide

This document outlines the visual identity, design tokens, template hierarchy, client-side interactivity, and asset pipeline for the **WALHI Jawa Barat** web platform. AI coding agents modifying views or stylesheets must strictly follow these rules to preserve design coherence and security integrity.

---

## 1. Visual Identity & Neo-Brutalist Design Philosophy

The WALHI Jawa Barat frontend follows a distinct **Neo-Brutalist** aesthetic reflecting the grassroots environmental activism domain:
- **High Contrast & Sharp Borders**: High contrast between paper backgrounds and ink blacks; sharp rectangular borders (typically `border-2 border-[#1D1D1D]` or `border-4 border-[#256D4A]`).
- **No Soft Skeuomorphism**: Avoid generic soft dropshadows (`shadow-lg`), heavy border radii (`rounded-full` or `rounded-2xl` on structural cards), or generic corporate pastel palettes.
- **Editorial Punch**: Bold uppercase titles, strong badge callouts, clean grid alignment, and prominent action banners.

### Core Color Palette Tokens (`brand.*`)
All colors are configured in [tailwind.config.js](../tailwind.config.js):

| Token | Hex Value | Semantic Usage |
| :--- | :--- | :--- |
| `brand.cream` | `#F4F1EA` | Primary page background (warm off-white/paper texture) |
| `brand.dark` | `#1D1D1D` | Primary ink text, heavy dark section blocks, high-contrast borders |
| `brand.green` | `#256D4A` | WALHI forest green: primary brand identity, active tabs, action buttons |
| `brand.green-light` | `#5C8D59` | Secondary green: badges, hover accents, success highlights |
| `brand.orange` | `#D95C3F` | Urgency alert: campaign highlights, petition CTA, error notices |
| `brand.brown` | `#8B6B4A` | Earth brown: secondary category badges, historical/archive tags |

---

## 2. Typography System

The typography system pairs an expressive geometric sans-serif for headlines with a highly legible humanist sans-serif for narrative copy:

### Hierarchy & Fonts
1. **Headlines & Display (`font-heading`, `font-label`, `font-oswald`)**:
   - **Font**: `Aspekta` (loaded locally via `/assets/fonts/webfonts/font-face.css`).
   - **Styling**: Uppercase, bold/extrabold (`font-bold` / `font-extrabold`), tight tracking (`tracking-wide` or `tracking-tight`), compact leading (`leading-none` to `leading-tight`).
   - **Usage**: Page titles (`h1`), section banners (`h2`), card headers (`h3`, `h4`), metadata tags, button labels.
2. **Body & Narrative (`font-sans`)**:
   - **Font**: `Montserrat` (loaded via Google Fonts with local fallback).
   - **Styling**: Standard sentence case, regular/medium weights (`font-normal` / `font-medium`), relaxed line height (`leading-relaxed` or `leading-loose`).
   - **Usage**: Article paragraphs, form inputs, lists, administrative tables.

---

## 3. Blade Layout & Template Hierarchy

Views are located in `resources/views/` and organized into two primary interfaces:

```text
resources/views/
├── layouts/
│   ├── admin.blade.php           # Admin panel shell (sidebar, topbar, anti-indexing shield)
│   ├── app.blade.php             # Authenticated user fallback layout
│   ├── guest.blade.php           # Guest authentication container (/portal-jabar)
│   └── navigation.blade.php      # Top nav bar component
├── components/
│   └── turnstile.blade.php       # <x-turnstile /> Cloudflare Turnstile widget (renders nothing when disabled)
├── newsletter/
│   └── unsubscribe.blade.php     # Newsletter unsubscribe confirmation
├── partials/
│   ├── head-assets.blade.php     # Fonts + Vite bundle (+ Lucide with ['lucide' => true]) for public pages
│   ├── seo-meta.blade.php        # Dynamic meta, OpenGraph, and Twitter tags
│   ├── site-header.blade.php     # Public header, navigation menu, emergency alert
│   └── site-footer.blade.php     # Organization details, newsletter widget, social links
├── admin/                        # Admin views (dashboard, content CRUD, comments, subscribers)
├── auth/                         # Login, password reset, two-factor-setup, two-factor-challenge
├── errors/                       # Branded error pages (403, 404, 419, 500)
├── welcome.blade.php             # Homepage with emergency campaign hero & issue grid
├── blog.blade.php                # Articles & publications listing
├── content-detail.blade.php      # Unified long-form reading view
├── donasi.blade.php              # Public donation & crowdfunding interface
├── regulasi.blade.php            # Environmental policy & legal database
└── tentang-kami.blade.php        # Organization history, structure, & executive board
```

### Layout Conventions
- **Public Views**: Public pages include [partials/head-assets.blade.php](../resources/views/partials/head-assets.blade.php) (fonts, Vite bundle, optional Lucide) in `<head>`, plus [partials/seo-meta.blade.php](../resources/views/partials/seo-meta.blade.php), [partials/site-header.blade.php](../resources/views/partials/site-header.blade.php), and [partials/site-footer.blade.php](../resources/views/partials/site-footer.blade.php).
- **Admin Layout**: All views in `admin/` extend `layouts.admin` (`@extends('layouts.admin')`).
  - Includes robots meta tags: `<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">` to shield admin routes from web scrapers and crawlers.
  - Generates dynamic breadcrumbs from route names via `$breadcrumbMap`.

---

## 4. Client-side Interactivity & Alpine.js Conventions

Interactivity is intentionally lightweight and server-driven.

### Alpine.js (CSP build)
- The app uses **`@alpinejs/csp`**, not the standard `alpinejs` package. The CSP build never calls `eval()` / `new Function()`, which the Content Security Policy forbids.
- Bootstrapped in [resources/js/app.js](../resources/js/app.js): `import Alpine from '@alpinejs/csp'`, components registered with `Alpine.data(...)`, then `Alpine.start()`.
- **Prefer named components** registered with `Alpine.data('name', () => ({ ... }))` in `app.js` (see `heroSlider`) and reference them with `x-data="name"`. Keep inline expressions in templates simple (property reads, assignments, method calls). Complex JavaScript inside attributes does not work under the CSP build; after any Alpine change, check the browser console for errors.
- Use `x-cloak` to avoid UI flicker during hydration.

### Images & lazy loading
- `<img>` below the fold gets `loading="lazy" decoding="async"`; in card lists the first row (`$loop->index < 3`) stays `eager`. The main image of a detail page uses `fetchpriority="high"`.
- CSS background images below the fold are written as `data-lazy-bg="{{ $url }}"` instead of `style="background-image: url(...)"`; [resources/js/lazy-backgrounds.js](../resources/js/lazy-backgrounds.js) sets the background when the element is within 300px of the viewport. Hero slides keep an inline `background-image` because they are visible on first paint.

### Replacing inline event handlers (`resources/js/csp-actions.js`)
The CSP sets `script-src-attr 'none'`, so `onclick=`, `onsubmit=`, `onchange=`, `onmouseover=` etc. **do not run**. Use the delegated data attributes instead:

| Attribute | Behaviour |
| :--- | :--- |
| `data-on-click="fnName"` | Calls `window.fnName(...args, element, event)` on click |
| `data-on-change="fnName"` / `data-on-input="fnName"` | Same, for `change` / `input` |
| `data-args='[1, "two"]'` | Optional JSON array of leading arguments |
| `data-confirm="Yakin?"` | On a `<form>`: asks for confirmation before submitting |
| `data-hover-style="..."` / `data-rest-style="..."` | Inline styles applied on mouseenter / restored on mouseleave |
| `data-toggle-target="#id"` | Toggles the target between `display:none` and `block` |
| `data-submit-closest-form` | Prevents default and submits the enclosing form |

Only named global functions are called; no strings are evaluated. Functions used this way must be defined on `window` in a nonced script or in a Vite entry.

### Icons
- Lucide icons (`<i data-lucide="name">`) come from the `lucide` npm package (pinned `0.460.0`), bundled by Vite in [resources/js/icons.js](../resources/js/icons.js) with **only the icons in use**. No CDN script is loaded.
- A new icon must be added to the import list in `icons.js` (PascalCase of the `data-lucide` name, e.g. `bar-chart-3` → `BarChart3`); `tests/Feature/LucideIconsTest.php` fails when one is missing.
- Public pages load it with `@include('partials.head-assets', ['lucide' => true])`; the admin layout includes `resources/js/icons.js` in its `@vite`. Inline scripts that change `data-lucide` can call `lucide.createIcons()` (a small wrapper set on `window`).
- Public pages prefer inline SVG.

### CAPTCHA component
`<x-turnstile />` (optional `size="compact"`) renders the Cloudflare Turnstile widget and its script once per page, only when both Turnstile keys are configured. Place it inside forms that use `Turnstile::rules()` (comments, newsletter). The validation error is rendered by the component.

---

## 5. Security & CSP Discipline

`SecurityHeaders` middleware sends a per-request nonce and this policy (abridged):

```text
script-src 'self' 'nonce-…' https://app(.sandbox).midtrans.com/snap/
           https://cdn.jsdelivr.net/npm/quill@2.0.2/ https://cdn.jsdelivr.net/npm/chart.js@4.4.7/
           [+ https://challenges.cloudflare.com when Turnstile is on]
script-src-attr 'none'
style-src 'self' 'unsafe-inline' fonts + quill css
img-src 'self' data: blob: https:
frame-src 'self' midtrans [+ challenges.cloudflare.com]
object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'
```

Rules:
1. **Every `<script>` tag must carry the nonce:** `<script nonce="{{ Vite::cspNonce() }}">`.
2. **No inline event handlers** (`on*=` attributes). Use Alpine directives or the `data-on-*` attributes above.
3. **Third-party scripts** must be added to the CSP at an exact, versioned path and loaded with an SRI `integrity` attribute. Do not allow whole CDN hosts.
4. **No `javascript:` URLs** in `href`. Links coming from content go through `App\Rules\SafeUrl`.
5. Never assign untrusted HTML to `innerHTML`. In admin editors, load bodies with `quill.clipboard.dangerouslyPasteHTML()` from server-sanitised data.
6. `tests/Feature/ContentSecurityHardeningTest.php` asserts the CSP has no `unsafe-inline`/`unsafe-eval` for scripts and that key public pages render no `on*="` handlers. It does not cover every admin view, so check the browser console on pages you change.

---

## 6. Asset Build Pipeline (Vite)

- **Config**: [vite.config.js](../vite.config.js) uses `laravel-vite-plugin` with entrypoints:
  - `resources/css/app.css`
  - `resources/js/app.js` (imports `csp-actions.js` and `lazy-backgrounds.js`)
  - `resources/js/csp-actions.js` (also usable on its own, e.g. by admin pages)
- **Commands**:
  ```bash
  npm run dev     # Starts local Vite development server with Hot Module Replacement (HMR)
  npm run build   # Compiles and minifies assets to public/build/ for production
  ```
- Production images build assets inside the Dockerfile (`node:24` LTS stage pinned by digest, `npm ci`). Feature tests call `$this->withoutVite()`, so tests do not need a built manifest.
- **In Blade**:
  ```blade
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  ```
