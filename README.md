# Auth Kit

Drop-in auth templates for any project. Each file is a standalone HTML page — no build step, no dependencies, just Geist font from Google Fonts. One `theme.css` controls all colors.

## Security Tiers

| Tier | Name | Color | Use For |
|------|------|-------|---------|
| 1 | **Basic** | Green | Prototypes, internal tools, dev environments |
| 2 | **Medium** | Blue | SaaS dashboards, team tools, customer portals |
| 3 | **Strong** | Purple | Production apps, admin panels, payment portals |
| 4 | **Super** | Red | Admin HQ, superadmin panels, infrastructure dashboards |

## What's In Each Tier

### Login (root `/`)

| Feature | Basic | Medium | Strong | Super |
|---------|:-----:|:------:|:------:|:-----:|
| Username + password | x | x | x | x |
| Input sanitization | | x | x | x |
| Length limits | | x | x | x |
| Rate limit countdown (429) | | x | x | x |
| Account lockout (423) | | x | x | x |
| Growing cooldown on fails | | x | x | x |
| Safe redirect (`?redirect=`) | | x | x | x |
| Password strength bar | | x | | |
| Honeypot fields (bot trap) | | | x | x |
| Challenge token (428) | | | x | |
| Optional 2FA (TOTP) | | | x | |
| Backup codes | | | x | |
| Mandatory 2FA on form | | | | x |
| Session takeover option | | | | x |
| 2FA setup flow (QR + key) | | | | x |
| noindex/nofollow | | | | x |
| Security warning footer | | | | x |

### Registration (`/register/`)

| Feature | Basic | Medium | Strong | Super |
|---------|:-----:|:------:|:------:|:-----:|
| Username + email + password | x | x | x | x |
| Confirm password | | x | x | x |
| Password strength + reqs | | x | x | x |
| Input sanitization | | x | x | x |
| 409 (taken) handling | | x | x | x |
| Honeypot fields | | | x | x |
| Terms of Service checkbox | | | x | x |
| Email verification (6-digit) | | | x | x |
| Resend code (60s cooldown) | | | x | x |
| Step indicator dots | | | x | x |
| Invite code required | | | | x |
| Mandatory 2FA setup | | | | x |
| Backup codes displayed | | | | x |
| 3-step flow with progress | | | | x |

### Forgot Password (`/forgot-password/`)

| Feature | Basic | Medium | Strong | Super |
|---------|:-----:|:------:|:------:|:-----:|
| Email input | x | x | x | x |
| No email enumeration | x | x | x | x |
| Rate limit countdown | | x | x | x |
| Code entry step | | x | x | x |
| New password form | | x | x | x |
| Password strength + reqs | | x | x | x |
| Confirm password match | | x | x | x |
| Resend code (60s cooldown) | | x | x | x |
| Honeypot field | | | x | x |
| Code attempt tracking (max 5) | | | x | x |
| Separate verify + reset steps | | | x | x |
| Step indicator dots | | | x | x |
| 2FA verification before reset | | | | x |
| Backup code alternative | | | | x |
| 4-step flow | | | | x |

### Search (`/search/`)

| Style | File | Best For |
|-------|------|----------|
| **Basic** | `search/1-basic.html` | Simple dropdown search, blog/docs sites |
| **Filtered** | `search/2-filtered.html` | Category chips + sort + filter, dashboards |
| **Command Palette** | `search/3-command.html` | Cmd+K power search, SaaS apps |
| **Data Table** | `search/4-table.html` | Full data grid with search/sort/filter/export/bulk |

All search templates include:
- Input sanitization (XSS-safe)
- Debounced requests (200-300ms)
- Request abort on new input
- Client-side cache (30s TTL)
- Keyboard navigation (up/down/enter/esc)
- "/" shortcut to focus
- Match highlighting
- Performance timing display

| Feature | Basic | Filtered | Command | Table |
|---------|:-----:|:--------:|:-------:|:-----:|
| Debounced input | x | x | x | x |
| Sanitization | x | x | x | x |
| Highlight match | x | x | x | x |
| Keyboard nav | x | x | x | |
| "/" focus shortcut | | x | x | x |
| Category filters | | x | | x |
| Sort options | | x | | x |
| Result count + timing | x | x | x | x |
| Client cache | | x | x | x |
| Abort previous | x | x | x | x |
| Cmd+K trigger | | | x | |
| Scoped prefixes (#, >) | | | x | |
| Built-in actions | | | x | |
| Fuzzy matching | | | x | |
| Rate limiting | | | x | |
| Column sorting | | | | x |
| Pagination | | | | x |
| Row selection | | | | x |
| Bulk actions | | | | x |
| Filter panel | | | | x |
| Active filter tags | | | | x |
| Export (CSV/JSON/clipboard) | | | | x |
| Per-page selector | | | | x |

### Payments (`/payments/`)

| Page | File | Features |
|------|------|----------|
| **Stripe Checkout** | `payments/stripe-checkout.html` | Card/iDEAL/Bancontact, Stripe Elements mount points, 3D Secure, coupon codes, order summary |
| **Mollie Checkout** | `payments/mollie-checkout.html` | iDEAL (with bank picker), Bancontact, Credit Card, PayPal, SOFORT/Klarna, EPS, Apple Pay, billing cycle toggle, coupons |
| **Pricing Page** | `payments/pricing.html` | 3-tier pricing cards, monthly/quarterly/yearly toggle, feature lists, "Popular" badge |
| **Payment Success** | `payments/success.html` | Receipt with transaction ID, plan, amount, method, date |
| **Payment Failed** | `payments/failed.html` | Error reason from URL params, retry + dashboard links |

**How payments work (no API keys in frontend):**

```
Frontend                    Your Backend                 Stripe / Mollie
   |                            |                              |
   |--- POST /api/payments/create -->                          |
   |    (plan, method, email)   |--- create payment --------->|
   |                            |<-- checkout_url / secret ----|
   |<-- { checkout_url }  ------|                              |
   |--- redirect to Mollie ---->|                              |
   |    OR confirm with         |                              |
   |    Stripe.js client_secret |                              |
   |                            |<-- webhook (payment done) ---|
```

- **Stripe**: Frontend loads `Stripe.js`, calls your backend for a `client_secret`, then `stripe.confirmCardPayment()` handles 3D Secure
- **Mollie**: Purely redirect-based — your backend returns a `checkout_url`, user pays on Mollie's hosted page, Mollie webhooks your backend

### Dashboard & Onboarding

| Page | File | Features |
|------|------|----------|
| **Dashboard** | `dashboard.html` | 4 stat cards with change indicators, activity feed, quick action grid |
| **Onboarding** | `onboarding.html` | 4-step wizard, animated transitions, profile setup, preferences |
| **Coming Soon** | `coming-soon.html` | Animated gradient title, CSS particles, countdown timer, email waitlist, progress bar |

### Account (extended)

| Page | File | Features |
|------|------|----------|
| **Settings** | `account/settings.html` | General, notifications toggles, theme picker (dark/light/system), danger zone |
| **Team / Invite** | `account/invite.html` | Invite by email, role picker, pending invites, team member list |

### Support & Legal

| Page | File | Features |
|------|------|----------|
| **Contact Form** | `support.html` | Category, subject, message, drag-and-drop file upload, priority, ticket reference |
| **Changelog** | `changelog.html` | Version timeline, type badges (Feature/Fix/Improvement), filter buttons |
| **Terms of Service** | `legal/terms.html` | Styled legal page, sticky sidebar TOC, [bracket] placeholders, print-friendly |
| **Privacy Policy** | `legal/privacy.html` | Same layout, GDPR rights section, cross-links |

### Components

| Component | File | Features |
|-----------|------|----------|
| **Toast Notifications** | `components/toasts.html` | 4 types, auto-dismiss with progress bar, stacking, pause on hover, mobile bottom-stack, copy-paste ready |
| **Cookie Consent** | `components/cookie-banner.html` | GDPR banner, Accept/Reject/Manage, preference toggles modal, localStorage persistence |

## Quick Start

Pick your stack and run one command to see everything working:

```bash
# Node.js (needs: npm install express)
node backend/server-node.js

# PHP (needs: PHP 7+ — works on school servers, XAMPP, shared hosting)
php -S localhost:3000 backend/server-php.php

# Python (needs: Python 3.6+ — zero dependencies, no pip install)
python3 backend/server-python.py
```

Open `http://localhost:3000` → browse all 33 templates with working mock API.

**Mock credentials:** `admin` / `admin123` or `demo` / `demo123`
**Mock 2FA code:** `123456`
**Mock coupon:** `DEMO50`

## How To Use

1. Copy `theme.css` + the HTML file(s) you need into your project
2. Open `theme.css` and set `--accent` to your brand color (that's it for theming)
3. In each HTML file, change these values at the top of the `<script>`:
   - `API_BASE` — your API URL (default: `/api`)
   - `REDIRECT` / `LOGIN_URL` — where to go after success
4. Wire up the server endpoints listed in the comment block
5. Replace the logo div with your own logo/brand

### Theming

All colors live in one file: **`theme.css`**. To customize:

- **Accent color**: Change `--accent` and `--accent-text` (white or black, whichever reads on your accent)
- **Light mode**: Add `data-theme="light"` to your `<html>` tag
- **Presets**: Uncomment one of the preset lines at the top of `theme.css`

The HTML files in subfolders (`register/`, `forgot-password/`) link to `../theme.css` — keep the folder structure or update the `<link>` path.

### Server Endpoints Expected

**Login:**
- `POST /api/auth/login` — returns `{ user }` or `{ requires_2fa, temp_token }`
- `POST /api/auth/verify-2fa` — accepts `{ temp_token, code }` or `{ temp_token, backup_code }`
- Super: `POST /api/hq/login` — accepts `{ username, password, totpCode }`

**Registration:**
- `POST /api/auth/register` — returns `{ user }` or `{ temp_token }` (for email verify)
- `POST /api/auth/verify-email` — accepts `{ temp_token, code }`
- `POST /api/auth/resend-verify` — accepts `{ temp_token }`
- Super: `POST /api/auth/setup-2fa` + `POST /api/auth/confirm-2fa`

**Forgot Password:**
- `POST /api/auth/forgot-password` — accepts `{ email }`, always returns 200
- `POST /api/auth/reset-password` — accepts `{ email, code, new_password }` or `{ reset_token, new_password }`
- Strong: `POST /api/auth/verify-reset` — separate code verification step
- Super: `POST /api/auth/verify-reset-2fa` — 2FA before password change

**Account:**
- `GET /api/auth/profile` — returns `{ username, display_name, email, avatar_url }`
- `PATCH /api/auth/profile` — accepts `{ display_name, email }`
- `POST /api/auth/profile/avatar` — FormData with `avatar` file
- `POST /api/auth/change-password` — accepts `{ current_password, new_password }`
- `POST /api/auth/delete-account` — accepts `{ password }`
- `GET /api/auth/sessions` — returns `{ sessions: [{ id, device, browser, ip, location, last_active, current }] }`
- `POST /api/auth/sessions/revoke` — accepts `{ session_id }`
- `POST /api/auth/sessions/revoke-all` — revokes all except current
- `GET /api/auth/2fa/status` — returns `{ enabled: bool }`
- `POST /api/auth/2fa/setup` — returns `{ qr_url, manual_key, pending_secret }`
- `POST /api/auth/2fa/confirm` — accepts `{ pendingSecret, code }` → `{ backup_codes }`
- `POST /api/auth/2fa/disable` — accepts `{ password, code }`

**Payments:**
- `POST /api/payments/create-intent` — (Stripe) returns `{ client_secret }` — body: `{ plan_id, coupon_code?, payment_method }`
- `POST /api/payments/create` — (Mollie) returns `{ checkout_url }` — body: `{ plan_id, cycle, method, email, issuer? }`
- `POST /api/payments/apply-coupon` — returns `{ valid, discount_amount, new_total }` — body: `{ coupon_code, plan_id }`
- `POST /api/payments/webhook` — (Mollie webhook) body: `{ id }`
- `GET /api/payments/:id` — returns receipt: `{ plan, amount, currency, method, transaction_id, date }`

**Search:**
- `GET /api/search?q=query&filter=type&sort=relevance` — returns `{ results: [{ id, title, description, url, type }], total }`
- `GET /api/users?q=query&status=&role=&sort=name&order=asc&page=1&limit=25` — (data table) returns `{ results, total, page, pages }`

**Settings:**
- `GET /api/auth/settings` — returns `{ theme, language, notifications: { email, push, marketing } }`
- `PATCH /api/auth/settings` — accepts any subset of the above

**Team / Invite:**
- `GET /api/team/members` — returns `{ members: [{ id, name, email, role, joined_at }], pending: [{ id, email, role, invited_at }] }`
- `POST /api/team/invite` — accepts `{ email, role }`
- `POST /api/team/invite/cancel` — accepts `{ invite_id }`
- `POST /api/team/members/remove` — accepts `{ member_id }`

**Support:**
- `POST /api/support/ticket` — FormData with `category`, `subject`, `message`, `priority`, optional `attachments`

**Onboarding:**
- `POST /api/auth/onboarding` — accepts `{ display_name, role, preferences }` — saves onboarding choices

**Waitlist:**
- `POST /api/waitlist` — accepts `{ email }` — returns `{ message }`

**Other:**
- `POST /api/auth/logout` — clears session

### HTTP Status Codes Used

| Code | Meaning | Handled In |
|------|---------|------------|
| 200 | Success | All |
| 400 | Bad request / invalid code | Medium+ |
| 401 | Invalid credentials | All |
| 409 | Username/email taken | Registration Medium+ |
| 423 | Account locked (permanent or temporary) | Medium+ |
| 428 | Challenge token required | Strong login |
| 429 | Rate limited (with `retry_after`) | Medium+ |

### Account Pages

| Page | File | Features |
|------|------|----------|
| **Profile** | `account/profile.html` | Edit display name, email, avatar upload, delete account, quick links to other account pages |
| **Change Password** | `account/change-password.html` | Current + new password, strength bar, 5 requirement badges, confirm match |
| **2FA Setup** | `account/setup-2fa.html` | 3-step flow (QR scan, verify code, backup codes), disable option, status check |
| **Active Sessions** | `account/sessions.html` | View all sessions, revoke individual or all, current device badge, time ago |

### Standalone Pages

| Page | File | Features |
|------|------|----------|
| **Email Verification** | `verify-email.html` | Landing page from email link, auto-verifies `?token=` or `?code=&email=`, resend option, expired/error states |
| **Logout** | `logout.html` | Confirmation page, clears localStorage/sessionStorage, optional auto-logout on load |

### Error Pages

| Page | File | Features |
|------|------|----------|
| **403 Forbidden** | `error/403.html` | Access denied, go home / go back |
| **404 Not Found** | `error/404.html` | Page not found, go home / go back |
| **500 Server Error** | `error/500.html` | Error with auto-generated reference ID |
| **Session Expired** | `error/expired.html` | Auto-redirect countdown (10s) to login |
| **Maintenance** | `error/maintenance.html` | Configurable ETA countdown, refresh button |
| **Account Locked** | `error/locked.html` | Permanent or timed lock display, countdown, reset password link |

## File Structure

```
auth-kit/
  theme.css                # Shared theme — edit this to change all colors
  verify-email.html        # Email verification landing page
  logout.html              # Logout confirmation
  1-basic.html             # Login — basic
  2-medium.html            # Login — medium
  3-strong.html            # Login — strong
  4-super.html             # Login — super
  register/
    1-basic.html           # Registration — basic
    2-medium.html          # Registration — medium
    3-strong.html          # Registration — strong
    4-super.html           # Registration — super
  forgot-password/
    1-basic.html           # Forgot password — basic
    2-medium.html          # Forgot password — medium
    3-strong.html          # Forgot password — strong
    4-super.html           # Forgot password — super
  account/
    profile.html           # Profile settings + avatar
    change-password.html   # Change password form
    setup-2fa.html         # 2FA setup / disable
    sessions.html          # Active session management
  search/
    1-basic.html           # Dropdown search
    2-filtered.html        # Search + category filters + sort
    3-command.html         # Cmd+K command palette
    4-table.html           # Data table with search/sort/filter/export
  payments/
    stripe-checkout.html   # Stripe checkout (Card, iDEAL, Bancontact)
    mollie-checkout.html   # Mollie checkout (7 payment methods)
    pricing.html           # Pricing page with plan cards
    success.html           # Payment success + receipt
    failed.html            # Payment failed + retry
  error/
    403.html               # Forbidden
    404.html               # Not found
    500.html               # Server error
    expired.html           # Session expired (auto-redirect)
    maintenance.html       # Maintenance mode
    locked.html            # Account locked
  components/
    toasts.html            # Toast notification system (copy-paste ready)
    cookie-banner.html     # GDPR cookie consent banner + preferences modal
  legal/
    terms.html             # Terms of Service with sidebar TOC
    privacy.html           # Privacy Policy with sidebar TOC
  coming-soon.html         # Animated launch page with countdown + waitlist
  onboarding.html          # 4-step welcome wizard
  dashboard.html           # Stats dashboard starter
  support.html             # Contact/support form with file upload
  changelog.html           # Product changelog with type filters
  index.html               # Visual template browser with dark/light toggle
  backend/
    server-node.js         # Node.js (Express) mock server
    server-php.php         # PHP (no framework) mock server
    server-python.py       # Python (no dependencies) mock server
  README.md
  SPEC.md
```

**Total: 45 templates + 3 backend starters** — all themed from one `theme.css`, all mobile responsive.

## Customization

- **Colors**: Edit `theme.css` — change `--accent` once, all pages update
- **Light mode**: Add `data-theme="light"` to `<html>` — light palette is built in
- **Presets**: 8 color presets at the top of `theme.css` (green, blue, purple, red, amber, cyan, pink, violet)
- **Font**: Swap Geist for any Google Font in the `<link>` tag in each HTML file
- **Logo**: Replace the `.logo` div with an `<img>` tag
