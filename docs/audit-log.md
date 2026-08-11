# Audit Log (Task 7)

Immutable, append-only security audit trail for the Laravel backend. Added on top
of Task 6 (`docs/final-security-review.md`), which flagged the absence of an audit
record for status changes and other security-sensitive actions (finding 4).

## Design decisions

- **Immutable by construction.** The table has no `updated_at`; the model throws
  `LogicException` on any attempt to update or delete an existing record; there
  are no API routes that mutate the log.
- **Event-driven dispatch.** Call sites only fire an `AuditEvent`; a single
  listener writes the row. This keeps the vocabulary closed (`AuditAction` enum),
  avoids duplication, and makes future sinks (e.g., SIEM/streaming) a one-line
  change.
- **Network context captured without Auth::attempt.** `audit.context` middleware
  copies `ip` + `User-Agent` into a request-scoped singleton (`App\Support\AuditContext`)
  **before** `ips` and auth run, so login failures and IPS blocks — where the user is
  unauthenticated — still get correct attribution. The listener falls back to this
  singleton and to `auth()->user()`/`request()` as needed.
- **Coexists with `login_attempts`.** `login_attempts` is the operational/rate-limit
  feed keyed for throttling; `audit_logs` is the durable, human-reviewable event feed.
  Two different purposes, not duplicates.
- **Sensitive data rule.** Metadata may only contain IDs, from/to status, changed
  field *names*, counts, and matched-pattern type. Never passwords, tokens, hashes,
  or decrypted complaint content.

## Schema

`database/migrations/2026_08_09_000000_create_audit_logs_table.php`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | FK users, nullable, `nullOnDelete` | actor; null when unauthenticated |
| action | string(100), indexed | `AuditAction` value |
| subject_type / subject_id | nullableMorphs (indexed) | target resource (polymorphic) |
| description | string nullable | human sentence |
| metadata | json nullable | array cast; see sensitive-data rule |
| ip_address | string(45), indexed | source IP |
| user_agent | string nullable | raw UA |
| created_at | timestamp `useCurrent`, indexed | no `updated_at` |

Composite indexes: `[action, created_at]`, `[user_id, created_at]` for the common
filter/sort shapes.

## Action vocabulary

`app/Enums/AuditAction.php` — a closed, backed enum, each case with a `label()`:

- **Auth:** `login`, `login_failed`, `logout`, `password_reset_link`, `password_reset`, `password_change`, `email_verified`
- **Users:** `user_registered`, `user_created`, `user_updated`, `user_deleted`, `role_changed`
- **Complaints:** `complaint_created`, `complaint_assigned`, `complaint_closed`, `complaint_reopened`, `complaint_status_changed`
- **Admin ops:** `settings_updated`, `category_created`/`updated`/`deleted`, `department_created`/`updated`/`deleted`
- **IPS:** `intrusion_sqli`, `intrusion_xss`, `ip_blocked`, `rate_limit_exceeded`, `ip_unblocked`

Unknown stored values fail loudly (`LogicException`) on read rather than degrading to
a missing label.

## Components

- `app/Enums/AuditAction.php` — vocabulary + labels.
- `app/Models/AuditLog.php` — fillable whitelist, `UPDATED_AT = null`, `metadata` cast,
  `user()`/`subject()` relations, `booted()` immutability guards, `labelFor()`.
- `app/Support/AuditContext.php` — request-scoped `ip`/`user_agent` holder (singleton).
- `app/Http/Middleware/AuditContext.php` — captures request ip/UA, clears in `finally`.
- `app/Events/AuditEvent.php` + `app/Listeners/AuditEventListener.php` — readonly event
  → `AuditLog::create()`, resolving ip/UA from the event or `AuditContext` next.
- `app/Services/AuditLogger.php` — `log(AuditAction, ?User, ?Model, ?description, metadata)`
  facade for controllers/services; observers use `Auth::user()`.
- `app/Observers/ComplaintCategoryObserver.php`, `app/Observers/DepartmentObserver.php`
  — `created` / `updated` / `deleted` on the two admin-managed resources (explicitly
  registered in `AppServiceProvider::boot()`).
- `app/Http/Requests/Audit/IndexAuditLogsRequest.php` — validates filters.
- `app/Http/Controllers/Api/AuditLogController.php` — admin-only index with filters
  and computed `action_label`.

## Route & access

`routes/api.php`:

- `GET /api/admin/audit/logs` → `role:admin` only. `sub_admin` and `security` are
  explicitly forbidden (tested). Reads are paginated (default 20, max 100).
- Middleware order on public auth routes: `audit.context` **before** `ips` **before**
  `throttle`, so failure events (login_failed, intrusion, ip_blocked,
  rate_limit_exceeded) carry the real ip/UA.
- Authenticated group: `auth:sanctum, audit.context, ips`.

## Dispatch sites

| Trigger | Where | Action(s) |
| --- | --- | --- |
| Login | `AuthService::login` | login / login_failed (3 variants), logout |
| Login attempts | `AuthService::findForCredentials` | login_failed |
| Password reset | `AuthController` | password_reset_link, password_reset |
| Registration | `RegisterController` | user_registered, email_verified |
| Admin user mgmt | `UserManagementController` | user_created/updated/deleted, role_changed, password_change |
| Complaint lifecycle | `ComplaintService`, `ComplaintWorkflowService`, `ComplaintAssignmentService` | complaint_created, complaint_status_changed, complaint_closed, complaint_reopened, complaint_assigned |
| Settings | `SettingsController` | settings_updated |
| Categories / departments | Observers | category_*/department_* |
| IPS | `IpsMiddleware` | intrusion_sqli/xss, rate_limit_exceeded, ip_blocked |

`ComplaintWorkflowService::transitionStatus` takes `bool $audit = true`; the
assignment service suppresses the generic status change (`audit: false`) and emits
the more specific `complaint_assigned` instead.

## Filtering (admin)

`action`, `user_id`, `ip_address`, `date_from`, `date_to`, `search` (LIKE over
`description`/`metadata`/`user_agent`), `per_page` (1–100). Response rows include a
computed `action_label`.

## Tests

`tests/Feature/AuditLogTest.php` — 24 tests covering every dispatch site, event vs.
context ip attribution, `audit.context` alongside IPS, immutability (update/delete
throw), admin-only access (forbidden for staff and security), and filters.

Verification: full suite 70 passed / 178 assertions; Pint applied to changed files
only (project convention).