# Mass Assignment & Authorization Audit

Round 4 of the backend security hardening. Introduces a per-route and per-ability
authorization layer on top of the existing filtering, closes an IDOR on
assignment history, hardens `original_filename`, and locks down mass assignment
for the only system-managed field that was still user-reachable.

## Models audited

| Model | Fillable fields | Verdict |
| --- | --- | --- |
| User | role_id, names, email, institution_id, password, phone, department_id, is_active, email_verified_at | Tightened: removed `last_login_at`. `role_id` / `email_verified_at` remain fillable because the factory, seeders, admin user-store and 7 test files mass-assign them legitimately; **no request payload can reach them** in any handler (all controllers build insert data from validated keys only). |
| Role | name, slug, description, is_system | `is_system` is set only by the seeder/factory; there is no role CRUD route. Safe. |
| ComplaintCategory | name, slug, description, default_priority, is_active | Categories are only created by the seeder/factory; no CRUD route. Safe. |
| Complaint | submit/update fields | Only service-built from validated keys. Safe. |
| ComplaintAssignment | complaint_id, assigned_to, assigned_by, timestamps | Service-built only. Safe. |
| ComplaintAttachment | complaint_id, user_id, file_path, original_filename, mime_type, file_size | Controlled by `AttachmentService`. Safe. |
| ComplaintMessage | complaint_id, user_id, message | `sendMessage` uses `validated()` + explicit assignment. Safe. |
| Department / Setting / RoutingRule / LoginAttempt | — | No mass assignment from user input paths. Safe. |

**Key finding:** `last_login_at` was the only system-managed timestamp that was
both fillable *and* overwritten only by internal code (`UserRepository::markLogin`).
Because Eloquent (without strict mode) silently discards non-fillable keys, an
admin `/admin/users` create/update could have carried a spoofed
`last_login_at`/`email_verified_at`/`role_id`. `role_id` and `email_verified_at`
still need to stay fillable for test/factory/seed machinery and are never fed from
user input; `last_login_at` was removed from the fillable list (still set via
direct property assignment, nothing mass-assigns it).

## Authorization gaps closed

1. **IDOR on assignment history** — `GET /complaints/{id}/assignments` belonged
   to any complaint officer (role middleware) but bypassed per-complaint access
   checks. An officer could read the full assignment/assignee note history of a
   complaint assigned to a colleague.
   Fix: added `complaint.access` middleware (same check as `/complaints/{id}`),
   so officers only see history for complaints currently assigned to them.

2. **Sub-admins could mutate users via routes** — `/admin/users/*` write routes
   were bound to `role:admin,sub_admin`. `PermissionService::canManageUsers()`
   is admin-only, giving controller-level protection, but routing still advertised
   sub-admin access.
   Fix: split into read-only (`role:admin,sub_admin`) and mutation
   (`role:admin`) groups. Sub-admins keep `students`, `staff`, `show`; only
   admins reach `store`, `update`, `destroy`, `updateRole`, `resetPassword`.

3. **Status update relied only on route middleware** — `updateStatus` accepted
   any admin/complaint-officer + `complaint.access`, but `complaint.access`
   alone (admin/sub_admin pass) doesn't express "update is admin-or-assigned-officer".
   Fix: added `ComplaintPolicy::update()` and a `Gate::authorize('update', $complaint)`
   call inside `updateStatus`, matching the existing capability model.

## Input hardening

- `AttachmentService::original_filename` now strips all control bytes
  (`\x00`–`\x1F`, `\x7F`) and caps the length at 255 (column width limit). This
  prevents over-length storage and header-injection style filenames in the
  download `Content-Disposition`.

## Regression tests (`tests/Feature/AuthorizationAndMassAssignmentTest.php`)

- Unassigned complaint officer → 403 on assignment history; assigned officer and
  admin → 200.
- Sub-admin can list users but gets 403 on create / update / delete / role change.
- Admin create and update endpoints **ignore** injected `role_id`,
  `last_login_at`, `email_verified_at` keys.
- Registration with injected `role_id`/`role=admin` cannot escalate: account is
  created as `student`, unverified.
- Attachment filename containing a 300-char run and a control byte is stored
  ≤255 chars with the control byte stripped.

## Verification

- `php artisan test` → 38 passed, 109 assertions.
- `php -l` clean on all changed files.
- Laravel Pint applied to changed files only (project convention).

Suite totals across all hardening rounds: 38 tests green.