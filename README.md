# Login Templates

Drop-in auth templates with 4 security tiers. Each file is a standalone HTML page — no build step, no dependencies, just Geist font from Google Fonts.

## Security Tiers

| Tier | Name | Color | Use For |
|------|------|-------|---------|
| 1 | **Basic** | Green | Prototypes, internal tools, dev environments |
| 2 | **Medium** | Blue | SaaS dashboards, team tools, customer portals |
| 3 | **Strong** | Purple | Production apps, admin panels, payment portals |
| 4 | **Super** | Red | Admin HQ, superadmin panels, infrastructure dashboards |

## What's In Each Tier

### Login (`/login/`)

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

## How To Use

1. Copy the HTML file for your tier into your project
2. Open it and change these values at the top of the `<script>`:
   - `API_BASE` — your API URL (default: `/api`)
   - `REDIRECT` / `LOGIN_URL` — where to go after success
3. Wire up the server endpoints listed in the comment block
4. Replace the logo div with your own logo/brand

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

## File Structure

```
login-templates/
  1-basic.html          # Login — basic
  2-medium.html         # Login — medium
  3-strong.html         # Login — strong
  4-super.html          # Login — super
  register/
    1-basic.html        # Registration — basic
    2-medium.html       # Registration — medium
    3-strong.html       # Registration — strong
    4-super.html        # Registration — super
  forgot-password/
    1-basic.html        # Forgot password — basic
    2-medium.html       # Forgot password — medium
    3-strong.html       # Forgot password — strong
    4-super.html        # Forgot password — super
  README.md             # This file (for humans)
  SPEC.md               # Machine-readable spec (for AI)
```

## Customization

- **Colors**: Search for the accent hex (green `#22c55e`, blue `#3b82f6`, purple `#a855f7`, red `#ef4444`) and replace
- **Font**: Swap Geist for any Google Font in the `<link>` tag
- **Logo**: Replace the `.logo` div with an `<img>` tag
- **Dark/Light**: All templates are dark mode — flip `#0a0a0a`↔`#fafafa` and `#141414`↔`#ffffff` for light mode
