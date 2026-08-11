# Final Security Review — School Complaints Management

**Scope:** Full-stack audit of the API (Laravel 13, PHP 8.3), SPA (Vue 3), and deployment configuration.
**Method:** Line-by-line review of middleware, controllers, services, repositories, models, Form Requests, migrations, config, and the Vue frontend; verified against the live test suite (46 passed / 126 assertions) and PHPStan/Pint.
**Date:** 2026-08-09

---

## Executive summary

No Critical findings. Two High findings (both availability / hardening, not data-exposure): the default trusted-proxies configuration and the signature-based IP-blocking scanner. All auth, authorization, mass-assignment, and file-upload controls from the earlier tasks remain verified as solid. Recommended fixes are configuration/behavior changes and do not require rewriting working code.

---

## What was verified as solid (no change needed)

- **Authentication**: Sanctum token auth; expiration 120 min; throttles on `login` (5/min, username+IP keyed), `register` (10/hr per IP), `password-reset` (5/hr per email+IP).
- **Account verification**: email verification enforced before login; OTP-like signed verification link; inactive-account blocking; login attempts recorded (no credentials in payload, invalid UTF-8 substituted).
- **Authorization**: role middleware (`admin`, `sub_admin`, `complaint_officer`, `security`, `student`, `staff`); per-complaint access via `EnsureComplaintAccess` + `Gate::authorize('update')`; admin user-management mutations restricted to `role:admin` only, read-only for `sub_admin`.
- **Mass assignment**: models use `$fillable` (or `#[Fillable]`) whitelists; no user input reaches `fill()/create()` with unlisted columns; factory/seeders/tests only mass-assign the intentionally-fillable `role_id`/`email_verified_at`.
- **Input validation & sanitization**: centralized `ValidationRules`; `InputSanitizer` strips scripts, event handlers, dangerous URIs, control characters; no `v-html`/`innerHTML` anywhere in the frontend.
- **SQL injection**: all queries parameterized (Query Builder/Eloquent); dynamic `to_char()` calls in the admin/security dashboards are static strings.
- **File uploads**: MIME sniffing (`getimagesize`), extension allowlist, `SafeImage` dimension/pixel caps, 10 MB limit, randomized storage name, private disk, sanitized display name.
- **Data at rest**: complaint title/description encrypted via `AesEncryptionService`.
- **Injection in exports**: payload logging excludes credentials; `Content-Disposition` uses sanitized filename.
- **Secrets**: `APP_DEBUG=false`; no secrets committed; credentials never logged.

---

## Findings

### HIGH

#### 1. Trusted proxies default to `*` — IP spoofing of rate limiters and IP-blocking

**Location:** `bootstrap/app.php:25` (`trustProxies(at: explode(',', env('TRUSTED_PROXIES', '*')))`).

**Risk / Impact:** With `TRUSTED_PROXIES` unset, *any* client can inject `X-Forwarded-For` and be treated as the real client IP. Three consequences:

- **Rate-limit bypass**: the `login` (5/min), `register` (10/hr), and `password-reset` throttles are keyed by IP; rotating the header defeats them.
- **IP-block spoofing (DoS)**: the IPS blocklist is keyed by `$request->ip()`. An attacker can send `X-Forwarded-For: <victim-IP>` with a pattern-triggering payload and get the victim's address added to the 24-hour blocklist. On a school campus behind NAT, one public IP serves many students — a single spoofed request can lock out the entire campus.
- Loss of reliable IP attribution for the security dashboard.

**Fix:**
- Set `TRUSTED_PROXIES` in production to only the actual proxy/load-balancer IPs or CIDRs (nginx/Heroku/Cloudflare), never `*`.
- On direct connections (no proxy), do not declare the app behind a proxy.
- Keep `HEADER_X_FORWARDED_*` trust limited to `X-Forwarded-Proto/Host/Port` only where required.
- Consider keying auth rate limits by a hash of `username + ip` (already the case) and additionally recording a real client IP when validation is required.

---

#### 2. Signature-based IP blocking has false-positive-triggering patterns on all request input

**Location:** `app/Http/Middleware/IpsMiddleware.php` — the SQLi/XSS regex scan over `$request->all()`.

**Risk / Impact:** The scanner runs against **every string in the request body** — including the login `password` field, complaint titles/descriptions, and chat messages. Several patterns match ordinary text, most notably:

- `/--/i` — matches **any** double hyphen (`"2021--2022"`, a password containing `--`, "range A--B").
- `/update\s+.*\s+set/i`, `/select\s+.*\s+from/i` — match natural-language sentences.
- `/alert\s*\(/i`, `/onload\s*=/i` — match innocuous words such as "alert(" in academic text.

A single hit returns `403 INTRUSION_DETECTED` and **adds the IP to a 24-hour blocklist** (`Cache::put(..., addHours(24))`). Because the login `password` field is scanned, a user whose password contains `--` cannot log in at all; combined with Finding 1 the block is trivially triggered against victims. No false-positive suppression, no threshold, no manual unblock other than waiting or cache clear.

**Fix:**
- Do not run signature scans on the `password` field or on free-text fields; scope detection to low-entropy credential fields (username/login) and known-injection vectors (`' OR 1=1`, `UNION SELECT`, `; DROP`).
- Remove the bare `/--/i` rule.
- Require **multiple distinct signals** (or a count threshold) before blocking, so a single odd string cannot ban an IP.
- Reduce block duration (e.g., 1 hour) and/or return the specific error only after N hits.
- Add an admin "unblock IP" action in the security dashboard.

---

### MEDIUM

#### 3. Account existence / status enumeration

**Location:** `app/Services/AuthService.php:37-54`, `app/Http/Controllers/Api/AuthController.php:101` (reset), registration unique-email messages.

**Risk / Impact:** Distinct responses reveal account state:

- Login differentiates `'Account is inactive.'`, `'Please verify your email address ... before logging in.'` vs. generic `auth.failed`.
- Password reset differentiates unknown-email (`passwords.user`) from invalid-token (`passwords.token`) via `__($status)`.
- Registration (`RegisterUserRequest`), `StoreUserRequest`, `UpdateUserRequest` return `'An account with this email address already exists.'`.

An unauthenticated attacker can scrape which emails/institution IDs exist and their verification/status state, enabling targeted phishing and credential-stuffing refinement. Mitigated but not removed by the existing throttles.

**Fix:**
- Return the identical generic message for all login failures (`auth.failed`) and for unknown-email vs invalid-token on reset.
- Log the specific reason server-side (already logged to `login_attempts`) but never expose it in the response.
- For registration, use a generic success message regardless of uniqueness.

---

#### 4. No workflow/state-machine enforcement on complaint transitions

**Location:** `app/Services/ComplaintWorkflowService.php::transitionStatus()`.

**Risk / Impact:** Any user with `role:admin,complaint_officer` and update permission can move a complaint between **any** of the nine statuses in any order (e.g., `closed` → `draft`, `resolved` → `submitted`, `rejected` → `assigned`). There is no legal-transition map, no re-opening policy, no audit of who changed status and when. This is a business-integrity weakness: a complaint can be stealthily regressed, and reporting/dashboard data can be distorted.

**Fix:**
- Implement an explicit transition table (e.g., `submitted → under_review → assigned → in_progress → resolved → closed`, with `rejected`/`reopened` guarded rules).
- Enforce who may perform which transition (complainant vs. officer vs. admin).
- Persist a complaint activity/audit record (user, from-status, to-status, timestamp) rather than only assignments history.

---

#### 5. Unthrottled high-volume write endpoints (below the global IPS ceiling)

**Location:** attachment store, chat send, complaint store (`routes/api.php` auth group; only `daily_limit=2` per complaint applies).

**Risk / Impact:** Only auth endpoints have dedicated `throttle:` middlewares. Attachment uploads, chat messages, and complaint submissions are bounded only by the global 100 req/hr/IP IPS limit (affected by Finding 1). An authenticated attacker can still upload many 10 MB files in a short window (a few hundred requests = up to ~1 GB), fill the private disk, and spam chat.

**Fix:**
- Add `throttle:` rules to `POST /complaints`, `POST /attachments` (e.g., 30/min), and `POST /complaints/{id}/messages` (e.g., 20/min).
- Add a per-user upload-count/day cap via `Rule::unique`/config in `StoreAttachmentRequest`.

---

### LOW

#### 6. CORS is implicit, not explicit

**Location:** no `config/cors.php`; relies on Sanctum stateful domains + default CORS behavior.

**Risk / Impact:** No misconfiguration found, but behavior is not explicitly declared. If a future edit publishes a permissive `cors.php` (`'allowed_origins' => ['*']`) alongside `withCredentials`/bearer-token auth, it would silently widen exposure.

**Fix:** Publish `config/cors.php` and pin `allowed_origins` to the SPA origin(s); keep `supports_credentials` aligned with the auth mode actually used (stateful/Sanctum). Document it.

---

#### 7. Bearer token in `localStorage`

**Location:** frontend stores the Sanctum token in browser storage; sent as `Authorization: Bearer`.

**Risk / Impact:** Persisted tokens survive refresh and are a single XSS away from theft (no `v-html`/`innerHTML` found, but the performance boundary is a future code change).

**Fix:** Prefer Sanctum stateful (cookie-based) auth for the SPA so no token is readable by JS; if bearer tokens are kept, keep expiration short (already 120 min) and rotate on password change.

---

#### 8. `markAsRead` flips all other participants' messages

**Location:** `app/Services/ChatService.php::markAsRead()`.

**Risk / Impact:** Calling `markAsRead(complaintId, userId)` marks every other user's messages in the conversation as read — including the officer's own messages when the complainant views the thread. Only affects unread indicators; no data exposure. Low severity.

**Fix:** Scope read-state per (message, reader) — either a counterpart field or a per-message `mentions`/seen model.

---

#### 9. Dashboard SQL uses `to_char()` (PostgreSQL-specific)

**Location:** admin/security dashboard queries.

**Risk / Impact:** DB-portability only; production runs `pgsql`, tests use sqlite and avoid this path. Not an injection risk.

**Fix:** Cosmetic/optional — abstract date formatting behind a dialect helper if MySQL support is ever required.

---

#### 10. No AV/malware scanning on uploads; `.txt` still allowed

**Location:** attachment upload flow.

**Risk / Impact:** Allows storing potential malware; `.txt` files are permitted by the allowed-extensions list. Documented in the upload-hardening round; residual risk is a stored-content risk only (downloads are served with `X-Content-Type-Options` absent — see note below).

**Fix / note:** Add `Content-Disposition: attachment` (already the case via `response()->download`) and set `X-Content-Type-Options: nosniff` header on the download route. If feasible, add ClamAV on write for uploaded files.

---

## Recommended action plan (priority order)

| # | Action | Severity | Effort |
|---|--------|----------|--------|
| 1 | Pin `TRUSTED_PROXIES` to real proxy IPs/CIDRs (or remove XFF trust) | High | Low |
| 2 | Harden IPS patterns (drop `--`, skip passwords/free text, add threshold, shorter bans, unblock UI) | High | Medium |
| 3 | Uniform authentication/registration/reset error messages | Medium | Low |
| 4 | Add status-transition map + audit log | Medium | Medium |
| 5 | Add `throttle:` on complaint/attachment/chat write routes | Medium | Low |
| 6 | Publish explicit `config/cors.php` pinned to SPA origin | Low | Low |
| 7 | (Optional) `X-Content-Type-Options: nosniff` on attachment downloads | Low | Low |

No Critical issues. High findings are availability/hardening in nature; no evidence of current exploitation, but both should be addressed before wider deployment.