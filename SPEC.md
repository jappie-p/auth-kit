# Login Templates — AI Specification

Machine-readable specification for selecting and integrating auth templates.

## Template Selection Guide

Given a project's requirements, select the appropriate tier:

```
IF internal_tool OR prototype OR no_real_users → Tier 1 (Basic)
IF saas OR team_tool OR customer_portal → Tier 2 (Medium)
IF production OR payments OR user_data OR admin_panel → Tier 3 (Strong)
IF superadmin OR infrastructure OR owner_only OR compliance → Tier 4 (Super)
```

## Templates Available

### login

#### tier-1-basic
- path: `1-basic.html`
- accent: `#22c55e` (green)
- fields: username, password
- auth_flow: POST credentials → receive cookie → redirect
- server_endpoints:
  - `POST /api/auth/login` → `{ user: Object }`
- features: []

#### tier-2-medium
- path: `2-medium.html`
- accent: `#3b82f6` (blue)
- fields: username (max 50), password (max 128)
- auth_flow: POST credentials → handle errors → redirect
- server_endpoints:
  - `POST /api/auth/login` → `{ user: Object }`
- features:
  - input_sanitization: blocks `<>'";&|` in username
  - rate_limit: handles HTTP 429 with `retry_after` countdown
  - account_lockout: handles HTTP 423 (permanent and temporary)
  - cooldown: growing delay between fails (1s → 5s)
  - password_strength: visual bar (length, upper, lower, digit, special)
  - safe_redirect: blocks `://` and `//` in `?redirect=` param

#### tier-3-strong
- path: `3-strong.html`
- accent: `#a855f7` (purple)
- fields: username (max 50), password (max 128)
- auth_flow: POST credentials → optional 2FA → redirect
- server_endpoints:
  - `POST /api/auth/login` → `{ user }` or `{ requires_2fa, temp_token }`
  - `POST /api/auth/verify-2fa` → `{ temp_token, code }` or `{ temp_token, backup_code }`
- http_status_handling: [401, 423, 428, 429]
- features:
  - all_from: tier-2-medium
  - honeypot: hidden fields (website, url, email2)
  - challenge_token: auto-retry on HTTP 428 with server-provided token
  - two_factor: optional TOTP (6-digit code)
  - backup_codes: 8-char uppercase codes
  - form_switching: login ↔ 2FA ↔ backup code forms

#### tier-4-super
- path: `4-super.html`
- accent: `#ef4444` (red)
- fields: username (max 50), password (max 128), totp (6-digit, mandatory)
- auth_flow: POST all three fields → handle session conflict → redirect
- server_endpoints:
  - `POST /api/hq/login` → `{ username, password, totpCode, forceReplace? }`
  - `POST /api/hq/setup-2fa` → `{ qr_url, manual_key, pending_secret }`
  - `POST /api/hq/confirm-2fa` → `{ totpCode, pendingSecret }`
- features:
  - all_from: tier-3-strong (except: 2FA is mandatory, not optional)
  - mandatory_totp: TOTP field on main login form
  - session_enforcement: single active session, force takeover checkbox
  - two_fa_setup: QR code + manual key for first-time setup
  - honeypot: hidden fields (website, company_name)
  - noindex: `<meta name="robots" content="noindex, nofollow">`
  - security_warning: footer text about logging

---

### register

#### tier-1-basic
- path: `register/1-basic.html`
- fields: username, email, password
- flow: single step → POST → success → redirect to login
- server_endpoints:
  - `POST /api/auth/register` → `{ user }`

#### tier-2-medium
- path: `register/2-medium.html`
- fields: username (3-30), email (max 100), password (max 128), confirm_password
- flow: single step → POST → success → redirect
- server_endpoints:
  - `POST /api/auth/register` → `{ user }`
- features:
  - confirm_password: live match indicator
  - password_strength: bar + 5 requirement badges
  - input_sanitization: blocks special chars, length limits
  - handles: 409 (taken), 429 (rate limit)

#### tier-3-strong
- path: `register/3-strong.html`
- fields: username, email, password, confirm_password, terms_checkbox
- flow: 2-step (register → email verification)
- steps:
  1. account_details: POST credentials → receive temp_token
  2. email_verify: POST { temp_token, code } → success → redirect
- server_endpoints:
  - `POST /api/auth/register` → `{ temp_token }`
  - `POST /api/auth/verify-email` → `{ temp_token, code }`
  - `POST /api/auth/resend-verify` → `{ temp_token }`
- features:
  - all_from: tier-2-medium
  - honeypot: hidden fields (website, fax_number)
  - terms_checkbox: required, links to /terms and /privacy
  - email_verification: 6-digit code entry
  - resend_code: 60s cooldown
  - step_indicator: dot progress

#### tier-4-super
- path: `register/4-super.html`
- fields: invite_code, username, email, password, confirm_password, terms_checkbox
- flow: 3-step (register → email verify → 2FA setup)
- steps:
  1. account_details: invite code + credentials → temp_token
  2. email_verify: 6-digit code → user_token
  3. two_fa_setup: QR scan → confirm code → backup codes shown
- server_endpoints:
  - `POST /api/auth/register` → `{ invite_code, username, email, password }` → `{ temp_token }`
  - `POST /api/auth/verify-email` → `{ temp_token, code }` → `{ user_token }`
  - `POST /api/auth/resend-verify` → `{ temp_token }`
  - `POST /api/auth/setup-2fa` → Bearer token → `{ qr_url, manual_key, pending_secret }`
  - `POST /api/auth/confirm-2fa` → `{ pendingSecret, code }` → `{ backup_codes, user }`
- features:
  - all_from: tier-3-strong
  - invite_code: required, admin-issued, uppercase
  - mandatory_2fa_setup: QR + manual key + confirmation
  - backup_codes: displayed after 2FA confirmed (one-time view)
  - step_indicator: 3 dots with labels

---

### forgot-password

#### tier-1-basic
- path: `forgot-password/1-basic.html`
- fields: email
- flow: single step → POST → always show success (no enumeration)
- server_endpoints:
  - `POST /api/auth/forgot-password` → `{ email }` → always 200

#### tier-2-medium
- path: `forgot-password/2-medium.html`
- fields: email → code + new_password + confirm_password
- flow: 2-step (request → enter code + new password)
- server_endpoints:
  - `POST /api/auth/forgot-password` → `{ email }`
  - `POST /api/auth/reset-password` → `{ email, code, new_password }`
- features:
  - rate_limit: 429 countdown
  - password_strength: bar + 4 requirements
  - confirm_password: live match
  - resend_code: 60s cooldown
  - no_enumeration: always shows success on email step

#### tier-3-strong
- path: `forgot-password/3-strong.html`
- fields: email → code → new_password + confirm_password
- flow: 3-step (request → verify code → set password)
- server_endpoints:
  - `POST /api/auth/forgot-password` → `{ email }`
  - `POST /api/auth/verify-reset` → `{ email, code }` → `{ reset_token }`
  - `POST /api/auth/reset-password` → `{ reset_token, new_password }`
- features:
  - all_from: tier-2-medium
  - honeypot: hidden field
  - code_attempts: max 5, counter displayed
  - separate_verify: code verification and password set are different steps
  - step_indicator: 3 dots

#### tier-4-super
- path: `forgot-password/4-super.html`
- fields: email → code → 2FA/backup → new_password + confirm_password
- flow: 4-step (email → code → 2FA → password)
- server_endpoints:
  - `POST /api/auth/forgot-password` → `{ email }`
  - `POST /api/auth/verify-reset` → `{ email, code }` → `{ reset_token, requires_2fa }`
  - `POST /api/auth/verify-reset-2fa` → `{ reset_token, code|backup_code }` → `{ reset_token }`
  - `POST /api/auth/reset-password` → `{ reset_token, new_password }`
- features:
  - all_from: tier-3-strong
  - two_fa_required: TOTP or backup code before password change
  - backup_code_option: 8-char uppercase fallback
  - step_indicator: 4 dots with labels
  - noindex: hidden from search engines
  - audit_warning: footer about IP/device logging

---

## Integration Checklist

When integrating a template:

1. Set `API_BASE` to your API URL
2. Set `REDIRECT` / `LOGIN_URL` to your post-auth destination
3. Implement the listed server endpoints with matching request/response shapes
4. Use httpOnly cookies for session tokens (templates use `credentials: 'include'`)
5. Return generic error messages on login (no user enumeration)
6. Handle these HTTP status codes on your server:
   - 200: success
   - 400: bad request
   - 401: invalid credentials
   - 409: username/email taken (registration)
   - 423: account locked (`{ permanent: bool, retry_after: seconds }`)
   - 428: challenge required (`{ challenge: string }`) — strong login only
   - 429: rate limited (`{ retry_after: seconds }`)

## Design Constants

```
font: Geist (Google Fonts)
bg-primary: #0a0a0a
bg-card: #141414
border: #262626
text-primary: #fafafa
text-secondary: #a3a3a3
text-muted: #737373
accent-green: #22c55e (tier 1)
accent-blue: #3b82f6 (tier 2)
accent-purple: #a855f7 (tier 3)
accent-red: #ef4444 (tier 4)
error: #ef4444
warning: #f59e0b
success: #22c55e
border-radius: 12px (card), 8px (inputs/buttons)
max-width: 400-420px
```
