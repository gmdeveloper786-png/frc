# Faizan Rehabilitation Centre (FRC) — Software QA Guide

**Product:** FRC Management System (Web + API)  
**Version:** QA document for full application test  
**Audience:** QA tester / UAT team  
**Language:** English steps; Urdu notes where helpful for local team

---

## 1. Purpose of this document

This file tells you **what to test**, **how to test**, and **what “pass” looks like** so nothing important is missed.

Use it as:

1. **Setup guide** — environment, logins, test data  
2. **Test plan** — module-by-module cases  
3. **Checklist** — tick Pass / Fail / N/A and log bugs  
4. **Regression list** — re-run before go-live

---

## 2. How to do QA (process)

### 2.1 Before you start

| Step | Action |
|------|--------|
| 1 | Get **staging URL** from dev team (not production unless agreed). |
| 2 | Confirm database is seeded or test users exist. |
| 3 | Use **Chrome** (primary) + one of **Firefox / Edge**. |
| 4 | Test **desktop (1366×768+)** and **mobile (375×812)**. |
| 5 | Clear cache or hard refresh (`Ctrl+F5`) after each deploy. |
| 6 | Keep **screenshots + steps** for every bug. |

### 2.2 For each test case

1. Note **role** (who is logged in).  
2. Follow **steps** exactly.  
3. Compare with **expected result**.  
4. Mark: **PASS** / **FAIL** / **BLOCKED** / **N/A**.  
5. On FAIL, log bug using template in **Section 3**.

### 2.3 Bug report template (copy per bug)

```
ID: BUG-___
Title: Short summary
Environment: Staging URL / Browser / Device
Role: e.g. Admin
Module: e.g. Enrollments
Steps to reproduce:
1.
2.
3.
Expected:
Actual:
Screenshot/Video:
Severity: Critical / High / Medium / Low
```

**Severity guide**

| Level | Example |
|-------|---------|
| Critical | Cannot login; payment lost; wrong child sees another child’s data |
| High | Cannot approve enrollment; session cannot be completed |
| Medium | Wrong label; filter broken; validation message unclear |
| Low | Spacing; typo; minor alignment |

---

## 3. Test environment setup

### 3.1 Local / staging (ask dev team)

Typical stack:

- PHP 8.2+, MySQL, Composer  
- `composer install`  
- `.env` configured (DB, `APP_URL`, mail optional)  
- `php artisan migrate --seed` (fresh QA database)  
- **Do not run** `php artisan storage:link` on Hostinger-style hosting — uploads use `/storage/...` route (see `storage.md`)

### 3.2 Default seeded login (after migrate --seed)

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| Super Admin | `superadmin@gmail.com` | `12345678` | Change on production |

**Other roles:** Create via Super Admin → Staff Users, or ask dev for staging accounts:

- Admin  
- Therapist (with branch, services, availability slots)  
- Finance  
- Child (approved + pending)

### 3.3 Minimum test data to create

Before deep testing, ensure exists:

- [ ] At least **2 branches**  
- [ ] **Services** (status: publish)  
- [ ] **Disabilities** (for child registration)  
- [ ] **1+ therapists** with working days + time slots (no break-only slots for booking tests)  
- [ ] **2+ approved children** (for enrollment & group enrollment)  
- [ ] **1 pending child** (for approval flow)  
- [ ] **Settings** filled: org name, bank details, high-discount threshold (default 50%), child registration on/off  

### 3.4 Browsers & devices

| Type | Requirement |
|------|-------------|
| Desktop | Chrome latest — primary |
| Desktop | Firefox or Edge — smoke on main flows |
| Mobile | Chrome DevTools iPhone/Android OR real phone |
| Tablet | Optional — sidebar + forms |

---

## 4. User roles & access (must verify)

### 4.1 Roles

| Role | Dashboard route | Main purpose |
|------|-----------------|--------------|
| Super Admin | `/super-admin/dashboard` | Everything + staff + roles + settings |
| Admin | `/admin/dashboard` | Operations (children, enrollments, config, etc.) |
| Therapist | `/therapist/dashboard` | Sessions, assessments, assigned children |
| Finance | `/finance/dashboard` | Payments, verification, reports |
| Child | `/child/dashboard` | Own enrollment, schedule, payments, profile |

### 4.2 Permission checks (negative tests)

Log in as each role and confirm:

- [ ] **Cannot** open URLs of modules they don’t have permission for (403 or redirect, not data leak).  
- [ ] Child **pending approval** cannot access `/child/dashboard` (only after approve).  
- [ ] Inactive user cannot use system after login blocked.  
- [ ] Logout works; back button does not expose protected pages without login.

### 4.3 Super Admin only

- [ ] Staff Users — CRUD, toggle active/inactive  
- [ ] Roles & Permissions — change admin permissions, save, re-login admin and verify menu changes  
- [ ] System Settings — save and see on login/receipt pages  

---

## 5. Global UI / UX checklist

Apply on **every major screen**:

- [ ] Logo shows correctly (login, register, sidebar) — `public/images/logo.png`  
- [ ] Sidebar: expand/collapse; mobile hamburger; no overlap with content  
- [ ] Top bar: page title, notification bell dropdown (mobile: aligned, not cut off)  
- [ ] Forms: required fields marked; errors show on correct field  
- [ ] Success/error flash messages after submit  
- [ ] Tables: pagination, filters, empty state message  
- [ ] Buttons: loading/disabled where applicable  
- [ ] Print/receipt pages readable  
- [ ] No broken images for uploaded slips/documents (`/storage/...` URLs)

---

## 6. Module test cases

### 6.1 Authentication & guest pages

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| AUTH-01 | Login valid | Login as Super Admin | Dashboard loads |
| AUTH-02 | Login invalid | Wrong password | Error, no login |
| AUTH-03 | Remember me | Login with remember checked | Session persists (browser dependent) |
| AUTH-04 | Forgot password | Request reset for valid email | Success message / email if mail configured |
| AUTH-05 | Logout | Logout from any role | Redirect login; protected routes blocked |
| AUTH-06 | Login UI | Resize mobile + desktop | Logo, form readable; register link if enabled |

### 6.2 Child self-registration (4 steps)

**Pre:** Settings → Child registration **enabled**.

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| REG-01 | Happy path | Complete steps 1–4, submit | Success message; account pending approval |
| REG-02 | Step validation | Skip required on step 1, click Next | Inline errors; cannot proceed |
| REG-03 | Password rules | Weak password | Validation error |
| REG-04 | Disability “Other” | Select Other without text | Error on step 3 |
| REG-05 | Registration off | Disable in settings | Register page closed / message |
| REG-06 | Mobile UI | Full flow on phone | Steps, header logo, buttons usable |

### 6.3 Child approval (Admin)

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| CH-01 | Pending list | Open Pending Approvals | New registration visible |
| CH-02 | Approve | Approve child | Status approved/active; child can login |
| CH-03 | Reject | Reject with reason | Child cannot access portal (or as designed) |
| CH-04 | All children | Search/filter list | Correct records |
| CH-05 | Edit child | Update profile fields | Saves; validation works |
| CH-06 | Child show | Open child detail | Enrollments, assessments visible |

### 6.4 Configuration — Branches, Services, Disabilities

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| CFG-01 | Branch CRUD | Create, edit, list | Data persists |
| CFG-02 | Service CRUD | Draft vs publish | Only publish in enrollment dropdowns |
| CFG-03 | Disability CRUD | Create, edit | Appears on child registration |

### 6.5 Therapists

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| TH-01 | Create therapist | Branch, services, profile | Saved |
| TH-02 | Availability | Set days + slots incl. break | Break slots not bookable in enrollment |
| TH-03 | Edit therapist | Change slots | Enrollment AJAX reflects change |
| TH-04 | List/filter | By branch/service | Correct results |

### 6.6 Assessments (staff)

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| AS-01 | Create assessment | Draft/publish, assign child | Saved |
| AS-02 | Complete | Mark complete | Status updated; notifications if any |
| AS-03 | Cancel | Cancel assessment | Status cancelled |
| AS-04 | Notes | Add note on assessment | Note visible |
| AS-05 | Child view | Login as child | Only own assessments; draft hidden |

### 6.7 Enrollments (critical)

**Statuses to know:** `draft`, `pending_super_admin_approval`, `approved`, `active`, `rejected`, `completed`, `cancelled`

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| EN-01 | Create single child | 1 child, schedule, start date **today or future** | Enrollment created |
| EN-02 | Past start date (create) | Pick yesterday | Error: cannot be in past |
| EN-03 | Create group | 2–3 children same therapist/slot | Group ID shared; one row per child in list |
| EN-04 | Slot conflict therapist | Same therapist day+slot twice | Error message |
| EN-05 | Slot conflict child | Same child two programmes same slot | Error message |
| EN-06 | Duplicate day+slot row | Two identical schedule rows | Error: duplicate |
| EN-07 | High discount | Discount ≥ threshold (default 50%) | Status `pending_super_admin_approval`; reason required |
| EN-08 | High discount approve | Super Admin → High discount queue → approve | Becomes active/approved flow |
| EN-09 | High discount reject | Reject with reason | Status rejected |
| EN-10 | Edit enrollment | Change therapist/schedule | Saves; notifications if configured |
| EN-11 | Edit start date | Try past date (change) | Blocked (today or future) |
| EN-11b | Edit keep old past | Enrollment already started in past; save without date change | Allowed (historical) |
| EN-12 | Weekly repeat + duration | repeat on, duration weekly/monthly | Total sessions/fee recalculated correctly |
| EN-13 | Enrollment show | Open detail | Fee summary, schedules, group members |
| EN-14 | Full schedule | View schedule calendar/list | Occurrences respect start date |
| EN-15 | Delete | Delete draft enrollment | Removed or blocked per policy |

### 6.8 Payments & finance

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| PAY-01 | Child upload slip | Child uploads image/PDF | Pending verification |
| PAY-02 | File view | Open slip URL | Image/PDF loads (not 404) |
| PAY-03 | Finance verify | Approve payment | Enrollment paid amount updated |
| PAY-04 | Finance reject | Reject with reason | Status rejected; child notified if applicable |
| PAY-05 | Manual payment | Staff manual payment entry | Record created |
| PAY-06 | Receipt | Print/download receipt | Org details, amounts correct |
| PAY-07 | Payment list filters | Date, status filters | Correct rows |
| PAY-08 | Partial payment | Multiple payments | Remaining balance correct |

### 6.9 Finance reports

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| RPT-01 | Finance report | Open report, filters | Data matches payments |
| RPT-02 | Export CSV | Export | File opens, columns correct |
| RPT-03 | Export PDF | Export PDF | Readable |
| RPT-04 | Print | Print view | Layout OK |

### 6.10 Therapist — sessions (critical)

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| SES-01 | Session list | Today / date filters | Correct occurrences |
| SES-02 | Start session | On **scheduled date**, scheduled row | Status in progress |
| SES-03 | Start future | Try start tomorrow’s session today | Blocked |
| SES-04 | Start past | Try start old scheduled date | Blocked |
| SES-05 | Complete | Complete in-progress | Completed; notification |
| SES-06 | Cancel | Cancel with reason | Cancelled |
| SES-07 | No-show | Mark no-show | Status updated |
| SES-08 | Session notes | Update notes on occurrence | Saved on detail |
| SES-09 | Group session | Group row: start/complete group | All members same action |
| SES-10 | Group show | Open group session detail | Lifecycle for members |
| SES-11 | Enrollment not active | Enrollment draft/cancelled | Therapist cannot start |

**Note:** Progress notes feature was **removed** by client — do not expect progress note forms/links.

### 6.11 Therapist — children & assessments

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| TCH-01 | My children | List only assigned children | No other children |
| TCH-02 | Child detail | Open child | Enrollment/schedule info scoped |
| TCH-03 | Assessments | List/complete/add notes | Only assigned assessments |

### 6.12 Child portal

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| CP-01 | Dashboard | Approved child login | Dashboard cards load |
| CP-02 | My enrollment | View enrollment(s) | Correct fee/status |
| CP-03 | My schedule | Calendar/list | Sessions from active enrollment |
| CP-04 | Upload slip | Upload payment proof | Success + pending |
| CP-05 | My payments | History | Matches finance records |
| CP-06 | Profile | Update profile/password | Saves |
| CP-07 | Assessments | View published assessments | No draft |

### 6.13 Notifications

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| NT-01 | Bell dropdown | Open from topbar | Latest list; mobile aligned |
| NT-02 | Mark all read | Click | Count zero |
| NT-03 | Open notification | Click item | Goes to correct page (enrollment, session, etc.) |
| NT-04 | Permission denied link | Old/broken link | Friendly message, no crash |
| NT-05 | Notifications page | Bulk mark/delete | Works |

### 6.14 Super Admin — staff & roles

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| SA-01 | Create staff | Admin/Finance/Therapist user | Can login |
| SA-02 | Deactivate staff | Toggle inactive | Cannot login |
| SA-03 | Edit role permissions | Remove `manage_enrollments` from admin | Admin menu hides enrollments |

### 6.15 System settings

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| SET-01 | Organisation | Change name/short name/tagline | Login + sidebar update |
| SET-02 | High discount % | Change threshold | Enrollment approval rule changes |
| SET-03 | Bank details | Update | Shown on child payment/upload |
| SET-04 | Child registration toggle | Off/on | Register route behavior |

---

## 7. End-to-end scenarios (full journeys)

Run these as **story tests** after module tests pass.

### E2E-1: New child to first payment

1. Register new child (public)  
2. Admin approves child  
3. Admin creates enrollment (active, start date today)  
4. Super Admin approves if high discount  
5. Child logs in, sees enrollment & schedule  
6. Child uploads payment slip  
7. Finance verifies payment  
8. Child sees updated payment status / receipt  

**Pass if:** No step fails; amounts consistent end-to-end.

### E2E-2: Therapist session day

1. Active enrollment with session **today**  
2. Therapist opens Sessions  
3. Start → Complete session  
4. Admin/child sees updated status where applicable  
5. Notification received (if enabled)  

### E2E-3: Group enrollment

1. Admin enrolls 3 children same group slot  
2. Therapist sees **one group row** with multiple names  
3. Start group session → Complete group  
4. Each child enrollment still correct on show page  

### E2E-4: Rejected / cancelled paths

1. Reject child registration  
2. Reject enrollment (high discount)  
3. Cancel session as therapist  
4. Verify dashboards counts and lists update  

---

## 8. API testing (optional — if mobile app or Postman QA)

Base URL: `{APP_URL}/api`  
Auth: Bearer token from `POST /api/auth/login`

| Area | Endpoints (sample) |
|------|-------------------|
| Auth | `POST /api/auth/login`, `POST /api/auth/register`, `GET /api/me` |
| Child portal | `GET /api/child/my-enrollment`, slip upload |
| Therapist | `GET /api/therapist/my-sessions`, start/complete session |
| Staff | enrollments, payments, children CRUD per permission |

Check:

- [ ] 401 without token  
- [ ] 403 wrong role  
- [ ] Same business rules as web (past start date, session date rules)  

---

## 9. Security & data privacy (spot checks)

- [ ] User A cannot open User B’s enrollment/payment by changing ID in URL  
- [ ] Therapist cannot access another therapist’s sessions  
- [ ] Child cannot access `/children` admin routes  
- [ ] CSRF: forms submit with token (normal POST)  
- [ ] File upload: only allowed types (pdf, jpg, png, webp); oversized file rejected  
- [ ] Password fields masked; change password requires current password  

---

## 10. Regression checklist (before sign-off)

Quick re-test after any bug fix:

- [ ] Login all 5 roles  
- [ ] Create enrollment + edit start date validation  
- [ ] Child slip upload + view file  
- [ ] Finance verify payment  
- [ ] Therapist start + complete session (today)  
- [ ] Notification open from bell  
- [ ] Mobile: login, sidebar, notification dropdown, register form  
- [ ] Group enrollment session row  

---

## 11. Out of scope / known notes

| Item | Note |
|------|------|
| Progress notes | **Removed** — not in scope for QA |
| `storage:link` on production | Not used; files via `/storage/{path}` route |
| Email delivery | Depends on server mail config; may not work on local |
| API vs Web | Web is primary; API mirrors most rules |

---

## 12. QA sign-off sheet

| Field | Value |
|-------|-------|
| Tester name | |
| Test environment URL | |
| Build / commit / date | |
| Test period | from ______ to ______ |
| Total cases executed | |
| Passed | |
| Failed | |
| Blocked | |
| Critical open bugs | |
| Recommendation | ☐ Ready for UAT / production ☐ Not ready |

**Approver (client):** _________________________ **Date:** __________

---

## 13. Reference — main menu by role

### Super Admin

Dashboard · Notifications · Staff Users · Roles & Permissions · (all Admin modules) · Settings

### Admin (typical)

Dashboard · Notifications · Pending Children · All Children · Therapists · Disabilities · Services · Branches · Assessments · Enrollments · High discount · Payments · Manual payment · Pending verification · Finance report · Profile

### Therapist

Dashboard · Assessments · Sessions & Schedule · My Children · Notifications · Profile

### Finance

Dashboard · Notifications · Profile · Payments (via finance routes) · Reports

### Child

Dashboard · Notifications · Assessments · Enrollment · Schedule · Profile · Upload slip · Payments

---

*Document maintained with codebase routes in `routes/web.php`, `routes/api.php`. Report gaps to the development team.*
