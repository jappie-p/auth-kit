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

### account

#### profile
- path: `account/profile.html`
- server_endpoints:
  - `GET /api/auth/profile` → `{ username, display_name, email, avatar_url }`
  - `PATCH /api/auth/profile` → `{ display_name, email }`
  - `POST /api/auth/profile/avatar` → FormData with `avatar` file → `{ avatar_url }`
  - `POST /api/auth/delete-account` → `{ password }`
- features:
  - edit_display_name
  - edit_email
  - avatar_upload: file picker with preview
  - delete_account: password confirmation required
  - quick_links: change password, 2FA, sessions

#### change-password
- path: `account/change-password.html`
- server_endpoints:
  - `POST /api/auth/change-password` → `{ current_password, new_password }`
- features:
  - current_password: required
  - password_strength: bar + 5 requirement badges
  - confirm_password: live match indicator

#### setup-2fa
- path: `account/setup-2fa.html`
- server_endpoints:
  - `GET /api/auth/2fa/status` → `{ enabled: bool }`
  - `POST /api/auth/2fa/setup` → `{ qr_url, manual_key, pending_secret }`
  - `POST /api/auth/2fa/confirm` → `{ pendingSecret, code }` → `{ backup_codes }`
  - `POST /api/auth/2fa/disable` → `{ password, code }`
- features:
  - status_check: shows enabled/disabled state
  - qr_code: scan with authenticator app
  - manual_key: copyable text fallback
  - verify_code: 6-digit TOTP confirmation
  - backup_codes: displayed once after setup
  - disable_option: requires password + TOTP code

#### sessions
- path: `account/sessions.html`
- server_endpoints:
  - `GET /api/auth/sessions` → `{ sessions: [{ id, device, browser, ip, location, last_active, current }] }`
  - `POST /api/auth/sessions/revoke` → `{ session_id }`
  - `POST /api/auth/sessions/revoke-all`
- features:
  - session_list: device, browser, IP, location, time ago
  - current_badge: highlights active session
  - revoke_individual: per-session revoke button
  - revoke_all: revokes all except current

#### settings
- path: `account/settings.html`
- server_endpoints:
  - `GET /api/auth/settings` → `{ theme, language, notifications: { email, push, marketing } }`
  - `PATCH /api/auth/settings` → partial update
  - `POST /api/auth/delete-account` → `{ password }`
- features:
  - general: language picker
  - notifications: email, push, marketing toggles
  - appearance: theme picker (dark/light/system)
  - danger_zone: delete account with confirmation

#### invite
- path: `account/invite.html`
- server_endpoints:
  - `GET /api/team/members` → `{ members: [...], pending: [...] }`
  - `POST /api/team/invite` → `{ email, role }`
  - `POST /api/team/invite/cancel` → `{ invite_id }`
  - `POST /api/team/members/remove` → `{ member_id }`
- features:
  - invite_form: email + role picker
  - pending_invites: list with cancel option
  - member_list: name, email, role, remove option

---

### search

#### basic
- path: `search/1-basic.html`
- server_endpoints:
  - `GET /api/search?q=query` → `{ results: [...], total }`
- features:
  - debounced_input: 300ms
  - sanitization: XSS-safe
  - highlight_match
  - keyboard_nav: up/down/enter/esc
  - abort_previous: cancels in-flight requests
  - result_count
  - performance_timing

#### filtered
- path: `search/2-filtered.html`
- server_endpoints:
  - `GET /api/search?q=query&filter=type&sort=relevance` → `{ results, total }`
- features:
  - all_from: basic
  - category_chips: filter by type
  - sort_options
  - slash_focus: "/" shortcut
  - client_cache: 30s TTL

#### command
- path: `search/3-command.html`
- server_endpoints:
  - `GET /api/search?q=query&scope=type` → `{ results, total }`
- features:
  - cmd_k_trigger: Cmd+K / Ctrl+K
  - scoped_prefixes: "#" for tags, ">" for commands
  - built_in_actions: navigation shortcuts
  - fuzzy_matching
  - rate_limiting
  - slash_focus
  - client_cache

#### table
- path: `search/4-table.html`
- server_endpoints:
  - `GET /api/users?q=query&status=&role=&sort=name&order=asc&page=1&limit=25` → `{ results, total, page, pages }`
- features:
  - column_sorting: click headers to sort
  - pagination: page navigation + per-page selector
  - row_selection: checkbox per row
  - bulk_actions: delete, export selected
  - filter_panel: status, role filters with active tags
  - export: CSV, JSON, clipboard
  - slash_focus

---

### payments

#### stripe-checkout
- path: `payments/stripe-checkout.html`
- server_endpoints:
  - `POST /api/payments/create-intent` → `{ client_secret }` — body: `{ plan_id, coupon_code?, payment_method }`
  - `POST /api/payments/apply-coupon` → `{ valid, discount_amount, new_total }`
- features:
  - stripe_elements: Card, iDEAL, Bancontact mount points
  - three_d_secure: handled by Stripe.js
  - coupon_codes: apply before payment
  - order_summary: plan, price, discount

#### mollie-checkout
- path: `payments/mollie-checkout.html`
- server_endpoints:
  - `POST /api/payments/create` → `{ checkout_url }` — body: `{ plan_id, cycle, method, email, issuer? }`
  - `POST /api/payments/apply-coupon` → `{ valid, discount_amount, new_total }`
- features:
  - payment_methods: iDEAL (bank picker), Bancontact, Credit Card, PayPal, SOFORT/Klarna, EPS, Apple Pay
  - billing_cycle_toggle: monthly/quarterly/yearly
  - coupon_codes
  - redirect_flow: backend returns checkout_url, user redirected to Mollie

#### pricing
- path: `payments/pricing.html`
- features:
  - three_tier_cards: Free, Pro, Enterprise
  - cycle_toggle: monthly/quarterly/yearly pricing
  - feature_lists: per plan
  - popular_badge: highlights recommended plan

#### success
- path: `payments/success.html`
- server_endpoints:
  - `GET /api/payments/:id` → `{ plan, amount, currency, method, transaction_id, date }`
- features:
  - receipt: transaction ID, plan, amount, method, date
  - dashboard_link

#### failed
- path: `payments/failed.html`
- features:
  - error_reason: from URL params
  - retry_link
  - dashboard_link

---

### standalone

#### verify-email
- path: `verify-email.html`
- server_endpoints:
  - `POST /api/auth/verify-email` → `{ token }` or `{ code, email }`
- features:
  - auto_verify: reads `?token=` or `?code=&email=` from URL
  - resend_option
  - expired_state
  - error_state

#### logout
- path: `logout.html`
- server_endpoints:
  - `POST /api/auth/logout`
- features:
  - confirmation_page
  - clears_storage: localStorage + sessionStorage
  - optional_auto_logout: uncomment to auto-trigger on load

#### coming-soon
- path: `coming-soon.html`
- server_endpoints:
  - `POST /api/waitlist` → `{ email }` → `{ message }`
- features:
  - animated_gradient_title
  - css_particles
  - countdown_timer: configurable target date
  - email_waitlist: signup form
  - progress_bar

#### onboarding
- path: `onboarding.html`
- server_endpoints:
  - `POST /api/auth/onboarding` → `{ display_name, role, preferences }`
  - `GET /api/auth/profile`
  - `POST /api/auth/profile/avatar`
- features:
  - four_step_wizard: welcome → profile → preferences → done
  - animated_transitions
  - avatar_upload
  - role_selection
  - preference_toggles

#### dashboard
- path: `dashboard.html`
- features:
  - stat_cards: 4 cards with change indicators
  - activity_feed: recent events
  - quick_actions: grid of action buttons
  - no_api: static template, wire up your own data

---

### error

#### 403
- path: `error/403.html`
- features: access denied message, go home / go back links

#### 404
- path: `error/404.html`
- features: page not found, go home / go back links

#### 500
- path: `error/500.html`
- features: error message with auto-generated reference ID

#### expired
- path: `error/expired.html`
- features: auto-redirect countdown (10s) to login

#### maintenance
- path: `error/maintenance.html`
- features: configurable ETA countdown, refresh button

#### locked
- path: `error/locked.html`
- features: permanent or timed lock display, countdown timer, reset password link

---

### components

#### toasts
- path: `components/toasts.html`
- features:
  - four_types: success, error, warning, info
  - auto_dismiss: with progress bar
  - stacking: multiple toasts stack
  - pause_on_hover
  - mobile_bottom_stack
  - copy_paste_ready: self-contained JS functions

#### cookie-banner
- path: `components/cookie-banner.html`
- features:
  - gdpr_banner: bottom bar with Accept/Reject/Manage
  - preferences_modal: toggle per cookie category
  - local_storage: persists user choice

---

### legal

#### terms
- path: `legal/terms.html`
- features:
  - styled_legal_page
  - sticky_sidebar_toc: table of contents
  - bracket_placeholders: `[Company Name]` etc.
  - print_friendly

#### privacy
- path: `legal/privacy.html`
- features:
  - same_layout: as terms
  - gdpr_rights_section
  - cross_links: links to terms

---

### support

#### contact
- path: `support.html`
- server_endpoints:
  - `POST /api/support/ticket` → FormData with category, subject, message, priority, attachments
- features:
  - category_picker
  - subject_input
  - message_textarea
  - file_upload: drag-and-drop
  - priority_selector
  - ticket_reference: displayed after submission

### changelog

- path: `changelog.html`
- features:
  - version_timeline
  - type_badges: Feature, Fix, Improvement
  - filter_buttons: filter by type

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
