# Input Hardening — Endpoint × Field × Rule Matrix

Scope: Task 3 — every request entering the application is treated as untrusted.
Approved scope changes (no oversized-payload middleware; IPS scan left as-is;
server-side payload limits documented below).

## Validation/Rule layer (`app/Support/ValidationRules.php`)

New guarantees on **all** shared free-text rules:

- `encoding:UTF-8` — rejects invalid UTF-8 (built-in Laravel rule, `mb_check_encoding`) on
  every stored string: names, email, username, complaint title/description, chat message,
  assignment note, settings text, and search queries. Malformed JSON bodies are decoded to an
  empty input bag and therefore fail the same required/type rules with 422.
- `email` max aligned 255 → **150** (matches `users.email` `string(150)`); prevents an
  oversized email that passed validation from crashing the insert.
- New shared helpers: `complaintTitle()`, `complaintDescription()`, `chatMessage()`,
  `assignmentNote()`, `appName()`, `contactPhone()`, `addressText()`, `contactEmail()` — all
  UTF-8 validated with column-safe length caps.
- Scalar rules (`string`, `integer`, `file`, `in`) already reject unexpected arrays / wrong
  types (verified: sending arrays for scalar fields yields 422).

## Endpoint coverage

| Endpoint | Inputs | Protection |
| --- | --- | --- |
| POST /api/login | username, password | `string`, `encoding:UTF-8`, max 150/255; throttled; IPS |
| POST /api/forgot-password | email | `required email encoding max:150`; throttled |
| POST /api/reset-password | token, email, password+confirmation | required/string; password policy min8+letters+numbers; throttled |
| POST /api/register/student|staff | names/email/gender/department/password/confirm_password | shared rules + `encoding:UTF-8`; email max 150; dept academic check (student); throttled |
| GET /api/register/verify/{user}/{hash} | signed + sha1 hash | unchanged (safe) |
| POST /api/register/resend | email | `email` + `encoding:UTF-8`; uniform response (no enumeration) |
| GET /api/departments | ?type | `nullable string in:academic,non-academic` |
| POST /api/complaints | category_id, title, description | `integer exists` / `utf8String min3 max255` / `utf8String min10 max5000`; sanitized + encrypted at rest; daily limit |
| GET /api/complaints, GET /api/complaints/{id} | route binding | 404 on bad id (bounded query) |
| PATCH /api/complaints/{id}/status | status | `in` whitelist of `ComplaintWorkflowService::STATUSES` |
| POST/GET /api/complaints/{id}/messages | message | `utf8String max5000`; sanitized at rest; `complaint.access` |
| POST/GET /api/complaints/{id}/attachments, download | attachment file | `file max:10240 mimetypes extensions`; server-generated storage path; `complaint.access` + ownership check |
| POST /api/complaints/{id}/assign | assigned_to, note | `integer exists` / `utf8String max1000`; suffers role check (officer only) |
| GET /api/staff/assignments, /api/staff/list, assignments history | — | role guard |
| GET /api/admin/dashboard | — | role guard |
| GET/POST/PUT/DELETE /api/admin/users/… | role, names, email, gender, title, dept, password, is_active | shared rules + `encoding:UTF-8`; unique email ignoring self; self-delete/last-admin guards |
| PATCH /api/admin/users/{id}/role | role | `in:staff,complaint_officer,sub_admin` (never admin) |
| POST /api/admin/users/{id}/reset-password | password (optional) | `sometimes nullable string` + min8+letters+numbers; else generated temp |
| GET /api/security/dashboard, /api/security/audit/logins | — | role guard |
| PUT /api/settings | app_name, contact_email, contact_phone, address, social_links, meta | UTF-8 + length; empty strings → null; unknown social keys rejected; sanitized at rest |

## Other changes

- `app/Support/InputSanitizer.php` — now also strips control bytes `[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]`
  (keeps `\t`, `\n`, `\r`), so null/control bytes are never stored. Plain text is preserved.
- `App\Models\User::composeName()` — truncates the composed `users.name` to 150 chars (column
  width) as defense-in-depth for pathological first+last combos; normal names untouched.
- `App\Services\AuthService::recordLoginAttempt()` — stores unknown usernames truncated to 150
  (matches `login_attempts.email` width) instead of risking a write-time overflow.
- `app/Http/Middleware/IpsMiddleware.php` — the security-event log no longer stores credential
  fields (`password`, `password_confirmation`, `confirm_password`, `current_password`, `token`)
  and `json_encode` uses `JSON_INVALID_UTF8_SUBSTITUTE` so logging can never fail. The pattern
  scan itself is unchanged (left as-is per decision; false-positive risk noted).
- `app/Http/Controllers/Api/SettingsController.php` — clearing an optional setting now persists
  as `null` (uses key-presence, not `isset`), so blank strings cannot be stored.

## Oversized payloads (server configuration)

No app-level limit was added (decision). Enforce in production:

- PHP `post_max_size` (default 8M) — cap to ~1M; bodies larger are dropped → 422.
- `upload_max_filesize` / `file_uploads` — at least 10M to match the attachment `max:10240`.
- Web server `client_max_body_size` (nginx) / `LimitRequestBody` ≥ the above.
- Note: attachments are capped at 10MB by validation; non-file JSON is bounded by `post_max_size`.

## Tests

`tests/Feature/InputHardeningTest.php` covers: invalid UTF-8 rejection, array/type coercion,
oversized email, malformed JSON → 422, settings blank→null, sanitizer control-byte handling,
composeName width guard, and IPS payload not leaking secrets.