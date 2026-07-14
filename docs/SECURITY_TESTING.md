# Security Testing Framework — Local Multi-Device Setup

This document is the developer manual for verifying every security control in the **School Complaints Management System**. It is designed for **local multi-device testing**: your Mac acts as the development server, and another machine (your Dell PC) simulates an attacker or remote user.

---

## 1. Environment Topology

```
┌────────────────────────────────────────────────────────────────────────┐
│                        YOUR LOCAL NETWORK                              │
│                                                                        │
│   ┌─────────────────────┐                       ┌─────────────────┐    │
│   │   MacBook (Master)  │ ◄── local network ──► │   Dell PC       │    │
│   │                     │                       │   (Tester)      │    │
│   │  • Laravel backend  │    192.168.x.x:8000    │  • Browser      │    │
│   │  • Vue 3 frontend   │ ◄──────────────────── │  • cURL         │    │
│   │  • Postgres DB      │                       │  • Postman      │    │
│   │  • Security Dashbd  │                       │                 │    │
│   └─────────────────────┘                       └─────────────────┘    │
│                                                                        │
│   Both devices share the same Wi-Fi/LAN router.                        │
└────────────────────────────────────────────────────────────────────────┘
```

| Role | Device | Purpose |
|------|--------|---------|
| **Master** | MacBook | Runs `php artisan serve` + `npm run dev`; watches security dashboard |
| **Tester** | Dell PC (or Phone) | Acts as an attacker / remote complainant |
| **Network** | Home Wi-Fi / LAN | Connects them |

---

## 2. Prerequisites Checklist

### On MacBook (Master)
- [ ] `composer install` completed
- [ ] `npm install` completed
- [ ] `.env` is configured with PostgreSQL
- [ ] `php artisan migrate --seed` ran successfully
- [ ] `php artisan key:generate` ran
- [ ] Cache cleared: `php artisan cache:clear`
- [ ] Backend running: `php artisan serve` (default: `127.0.0.1:8000`)
- [ ] Frontend running: `npm run dev` (default: `localhost:5173`)
- [ ] Find your LAN IP: `ifconfig | grep "inet "` (look for `192.168.x.x` or `10.0.x.x`)

### On Tester (Dell PC / Phone)
- [ ] Connected to the **same Wi-Fi** as MacBook
- [ ] Browser, Postman, or `curl` installed
- [ ] Optional: VPN OFF (so attacker IP === real LAN IP)

---

## 3. Network Exposure — Three Approaches

### Approach A: ✅ RECOMMENDED — Laravel `--host` Flag

Bind Laravel to all interfaces (not just `127.0.0.1`):

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Then on the Dell PC, visit:
```
http://<YOUR_MAC_LAN_IP>:8000
```

Use your LAN IP (e.g., `192.168.1.50:8000`).

> ✅ No hosting. No port forwarding. No router config. Works on a home LAN.

### Approach B: Vite Dev Server Exposed
Same idea for the frontend:
```bash
npm run dev -- --host 0.0.0.0
```
Then the SPA is reachable at `http://<LAN_IP>:5173`.

### Approach C: ngrok / Cloudflare Tunnel (External)
If you want to test from **outside your network** (e.g., over mobile data):

```bash
brew install ngrok          # Mac
ngrok http 8000             # exposes Laravel
```

You'll get a public URL like `https://abc123.ngrok.io`. Both the API and frontend connections will need that base URL set.

**Set the API base URL in the frontend `.env`:**
```env
VITE_API_BASE_URL=https://abc123.ngrok.io/
```

Then `npm run dev` will hit the tunneled backend.

> ⚠️ **For testing only.** Do not keep tunnels open long-term.

---

## 4. Testing Plan — Phase by Phase

Each phase should be run **in this order**. Run phase 1 clean first, then move on.

---

### 🔬 Phase 1 — Authentication & Login Audit

**Goal:** Confirm login audits work and capture login info.

#### Mac (Master)
1. Open `http://localhost:8000/admin` → log in as **admin** (`admin@example.com` / `password`).
2. Open the Security Dashboard: `http://localhost:8000/security`.
3. Note the "Login Audit" widget.

#### Dell PC (Tester)
4. Open the same login URL **over LAN**: `http://192.168.x.x:8000/login`.
5. Try 5 login attempts:
   - 1 successful: `cybersecurity-student-1@example.com` / `password`
   - 1 wrong password: `cybersecurity-student-1@example.com` / `wrong`
   - 1 non-existent user: `ghost@example.com` / `password`
   - 1 valid inactive account (any seeded inactive user) / correct password
   - 1 with SQL-ish username: `' OR 1=1 --` / `whatever`

#### Expected Results (Master)
- The **Login Audit** shows 5 rows — one per attempt.
- IPs should appear in the table.
- Failure reasons: "Invalid credentials", "Account inactive", etc.

---

### 🔬 Phase 2 — SQLi / XSS Intrusion Detection

**Goal:** Trigger `IpsMiddleware` patterns and verify they get blocked & logged.

#### Setup
Reset state before testing:
```bash
php artisan cache:clear
```

#### Test 2A: SQL Injection Detection
Open Dell PC browser and navigate to the **complaint submit form**: `http://<MAC_IP>/student/submit`.

Fill in:
- **Title:** `union select * from users` (or `' OR 1=1 --`)
- **Description:** Anything
- **Category:** Any
- Click **Submit**

#### Expected
- HTTP `403` returned with `{"code":"INTRUSION_DETECTED"}`.
- On Mac, refresh **Security Dashboard**;
  - "Security Events" list shows a row with `type: sqli`, your Dell PC IP, and reason `"SQL Injection pattern matched in input 'title'"`.

#### Test 2B: XSS Detection
After clearing cache (`php artisan cache:clear` on Mac), submit again:
- **Title:** `<script>alert(1)</script>`
- **Description:** `onerror=alert(1)`

#### Expected
- HTTP `403` returned with `{"code":"INTRUSION_DETECTED"}`.
- Security Dashboard shows `type: xss`.

#### Test 2C: Word-of-Mouth False Positives
Try submitting a benign title like `"Hi, I have a question"`.
- **Expected**: 201 Created (the regex doesn't match harmless text).

#### Things To Remember
- A single match **blocks your IP for 24h**! Always clear cache between tests:
  ```bash
  php artisan cache:clear
  ```

---

### 🔬 Phase 3 — Rate Limit Test

**Goal:** Show the per-IP throttle behavior.

#### Test 3A: Burst Requests with cURL
On Dell PC (or Mac), open a terminal:

```bash
for i in $(seq 1 105); do
  curl -s -o /dev/null -w "%{http_code}\n" \
    -H "Authorization: Bearer <TOKEN>" \
    http://<MAC_IP>/api/complaints
done
```

> Get a valid `<TOKEN>` by logging in via Postman first.

#### Expected
- The first 100 requests → `200`.
- Requests 101+ → `429` with `{"code":"IP_BLOCKED"}`.
- After 24 hours (or after `php artisan cache:clear` on Mac) → 200 again.

#### Mac Window
- Watch the **Security Dashboard** "Recent Events" list.
- The blocked-IP list should now include your tester's IP.

---

### 🔬 Phase 4 — AES-256 Encryption Verification

**Goal:** Confirm sensitive fields are encrypted at rest.

#### Step 1 — Submit a complaint as student (`STD-2026-201` / `password`)
- Title: `Cafeteria food is cold`
- Description: `The cafeteria is serving cold meals`

#### Step 2 — On Mac, check the database directly
```bash
psql complaint_management
\echo '\n== Inspecting complaints table =='
SELECT id, reference_no, title_encrypted, description_encrypted FROM complaints;
```

#### Expected
- `title_encrypted` and `description_encrypted` should be **base64-like** strings (Laravel `Crypt` produces them via encrypted serialization).
- They should **not** contain readable English text like "Cafeteria".
- Compare to `reference_no` (which is plain text).

#### Step 3 — Check decryption works
- Login as admin, navigate to the same complaint.
- The UI should display the human-readable `Cafeteria food is cold`.

---

### 🔬 Phase 5 — Authorization Bypass Attempts

**Goal:** Ensure unauthorized users cannot access complaints.

#### Roles matrix

| Test User | Email | Role |
|-----------|-------|------|
| Student | `cybersecurity-student-1@example.com` | student |
| Staff complainant | `staff@example.com` | staff |
| Complaint officer | (seeded officer user) | complaint_officer |
| Admin | `admin@example.com` | admin |

#### 5A — Student tries to access another student's complaint
1. Login as student A on Dell PC browser.
2. Open `/api/complaints/<student_B_complaint_id>` directly.
3. **Expected**: `403 Forbidden — you do not have access to this complaint.`

#### 5B — Student tries to assign a complaint
1. Login as student.
2. POST to `/api/complaints/{id}/assign`.
3. **Expected**: `403 Forbidden — your role cannot perform this action.`

#### 5C — Staff (not officer) tries to update status
1. Login as `staff@example.com`.
2. PATCH `/api/complaints/{id}/status`.
3. **Expected**: `403 Forbidden`.

#### 5D — Officer tries to assign (only admin can)
1. Login as officer.
2. POST `/api/complaints/{id}/assign`.
3. **Expected**: `403 Forbidden`.

#### 5E — Admin can do anything
1. Login as admin.
2. Verify all allowed calls succeed.

---

### 🔬 Phase 6 — Attachment Download Audit

**Goal:** Confirm files download only via authenticated requests.

1. Login as student on Dell PC, upload a small test PDF to a complaint.
2. Copy the attachment ID.
3. Try:
   - **In browser**, visit the URL directly: `http://<MAC_IP>/api/complaints/{c}/attachments/{a}/download`
   - **In cURL**, post no token: `curl http://<MAC_IP>/api/...`
4. **Expected:** Both return `{"message":"Unauthenticated"}` — fixed by your blob download.

5. Then use the proper UI button — file downloads successfully.

---

### 🔬 Phase 7 — Frontend Role Routing

**Goal:** Confirm `/officer` is locked to `complaint_officer` only.

1. Login as plain `staff` user → try to navigate to `/officer`.
2. **Expected**: Redirected to `/staff` (their allowed dashboard).
3. Login as `complaint_officer` → `/officer` opens OfficerDashboard without issues.
4. Login as student → `/officer` redirects to `/student`.

---

## 5. Quick Reset Utilities

Whenever you get blocked or want to start fresh:

```bash
# On Mac (Master)
php artisan cache:clear           # wipes IPS blocked IPs + events
php artisan migrate:fresh --seed  # reset DB to seed
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart processes
php artisan serve --host=0.0.0.0
npm run dev -- --host
```

---

## 6. Suggested Tools (Tester Side)

| Tool | Use It For |
|------|-----------|
| **Postman** | Build requests quickly; manage tokens; save sample bodies |
| **Browser DevTools (F12)** | Inspect network calls, headers, payloads |
| **cURL** | Burst rate-limit tests, automation |
| **BurpSuite (Community)** | Pro-grade attack simulation (optional, advanced) |
| **Hoppscotch** | Browser-based Postman alternative |

---

## 7. Test Checklist Summary

```
[ ] Phase 1 — Login audits recorded
[ ] Phase 2A — SQLi blocked at the boundary
[ ] Phase 2B — XSS blocked at the boundary
[ ] Phase 2C — Benign text is NOT blocked
[ ] Phase 3 — Rate limit kicks in after threshold (100)
[ ] Phase 4 — Encryption shows scrambled text in DB
[ ] Phase 5A — Students cannot view each other's complaints
[ ] Phase 5B — Students cannot assign
[ ] Phase 5C — Staff (non-officer) cannot update status
[ ] Phase 5D — Officers cannot assign
[ ] Phase 5E — Admin has full access
[ ] Phase 6 — Attachments require auth (inline + token only)
[ ] Phase 7 — Frontend role guards redirect correctly
```

---

## 8. End-to-End Workflow

```
┌──────── MASTER (Mac) ─────────┐        ┌────── TESTER (Dell PC) ──────┐
│                               │        │                              │
│  1. Start servers             │        │  4. Same Wi-Fi              │
│  2. Get Mac LAN IP            │        │  5. Open browser:           │
│  3. Clear cache               │        │     http://<MAC_IP>:8000    │
│                               │        │                              │
│  8. Watch Security Dashboard  │ ─────► │  6. Run attack scenarios    │
│     for events                │ ◄───── │  7. Login attempts          │
│                               │        │                              │
│  9. Reset & rerun tests       │        │                              │
└───────────────────────────────┘        └──────────────────────────────┘
```

---

## 9. Document Your Findings

After each test cycle, record:

```
┌──────────────────────────────────────────────────────────────────┐
│ TEST LOG                                                        │
│ Date      : 2026-07-05                                          │
│ Tester    : <your-name>                                         │
│ Phase     : 2A                                                   │
│ Scenario  : SQLi in title field                                 │
│ Input     : "union select * from users"                         │
│ Expected  : 403 INTRUSION_DETECTED                              │
│ Actual    : 403 INTRUSION_DETECTED                              │
│ Status    : PASS                                                 │
│ Notes     : Logged in Security Dashboard                         │
└──────────────────────────────────────────────────────────────────┘
```

Keep a `security_test_log.md` per cycle — auditors love it.

---

## 10. Optional — Production-Like Setup

If you eventually want to share with a remote friend or remote device over the **internet** (not LAN), use:

| Service | Use |
|---------|-----|
| **ngrok** | `ngrok http 8000` |
| **Cloudflare Tunnel** | Free, stable, no account-level limits |
| **Tailscale** | Mesh VPN — your Dell PC appears as if on the same LAN |
| **localtunnel.me** | Quick external tunnel |

For this project, **Approach A (LAN exposure)** is enough for you + your Dell PC on the same Wi-Fi.
