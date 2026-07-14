# MVP Secure Complaint Management - Implementation Checklist

## Step 1 — Database schema (polymorphic identity)
- [ ] Add new roles: admin, staff, student, complaint_officer (replace/adjust current RoleSeeder as needed).
- [ ] Add migrations:
  - students (user_id, student_id, department, etc.)
  - staff_profiles (user_id, staff_id, department, etc.)
  - users polymorphic identity columns: userable_type, userable_id (nullable)
- [ ] Update users migration/model to include new columns (and keep institution_id temporarily if needed for backward compatibility).

## Step 2 — Eloquent models + relationships
- [ ] Update `app/Models/User.php` to add polymorphic relationships (morphTo / morphOne / morphMany as appropriate).
- [ ] Create new models:
  - `app/Models/Student.php`
  - `app/Models/StaffProfile.php`

## Step 3 — Seed data
- [ ] Update `database/seeders/UserSeeder.php`:
  - Seed admin, staff, student, complaint_officer users
  - Create corresponding profile rows in students/staff_profiles tables

## Step 4 — Admin user management APIs (+ minimal UI)
- [ ] Add new routes to `routes/api.php`:
  - `POST /api/admin/students`
  - `POST /api/admin/staff`
  - (optional) `GET /api/admin/staff` and `GET /api/admin/students` for UI needs
- [ ] Implement controller(s) to create users + attach profile row automatically.
- [ ] Add minimal admin pages/forms (or update existing AdminDashboard.vue) to create students/staff.

## Step 5 — Frontend build + MVP testing
- [ ] Run `php artisan migrate:fresh --seed`
- [ ] Run `npm run build`
- [ ] Manual MVP testing checklist:
  - [ ] Login as each role & redirection works
  - [ ] Complaint categories load
  - [ ] Submit complaint as student/staff complainant (encryption present)
  - [ ] Complaint listing works per role
  - [ ] Status updates work (staff/admin/officer as per RBAC)
  - [ ] Assignments work
  - [ ] Attachments upload + download works
  - [ ] Chat messages work
  - [ ] IPS blocks SQLi/XSS and Security Dashboard reflects events
