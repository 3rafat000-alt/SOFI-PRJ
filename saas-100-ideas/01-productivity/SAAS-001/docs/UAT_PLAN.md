# UAT PLAN — TaskSync Pro (SAAS-001) — Gate 7

> **Owner:** Linda Schmidt (sofi-devops-cloud-lead) · **Date:** 2026-06-25
> **Environment:** Staging (`staging.tasksyncpro.com`) · **Target:** Production (`tasksyncpro.com`)
> **Consumes:** docs/PERSONAS.md, docs/JOURNEY_MAP.md, docs/ARCHITECTURE.md, docs/STAGING_PLAN.md
> **Status:** 🟢 Ready for UAT execution

---

## 1. Personas Under Test

| # | Persona | Role | Digital Literacy | Key Journeys |
|---|---------|------|-----------------|--------------|
| 1 | **سارة** — Marketing Manager | Team lead, 7 members | High | Create workspace, assign tasks, view reports |
| 2 | **أحمد** — Freelance Developer | Solo, 3-4 concurrent clients | High | Time tracking, client reports, task organization |
| 3 | **ليلى** — Project Coordinator | 15-person startup, 5 projects | Medium | Cross-project view, deadline tracking, weekly reports |
| 4 | **يوسف** — IT Manager | Admin, manages accounts & billing | High | User management, role assignment, subscription, audit logs |
| 5 | **نورة** — Small Business Owner | Client receiving reports from freelancers | Low-Medium | View shared task progress, receive time reports via WhatsApp |

---

## 2. UAT Test Scenarios — 10 Core Journeys

### Journey 1: سارة — Workspace Setup & Team Invitation

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 1.1 | Navigate to `https://staging.tasksyncpro.com` | Landing page loads in <3s, Arabic text displayed correctly | □ |
| 1.2 | Click "ابدأ التجربة المجانية" | Register form displayed with Arabic labels | □ |
| 1.3 | Fill name, email, password, confirm password; submit | 201 Created, verification email sent to inbox | □ |
| 1.4 | Click verification link in email | Email verified, redirected to workspace setup | □ |
| 1.5 | Enter workspace name "الفريق التسويقي", select industry "تسويق" | Workspace created, step 2 displayed | □ |
| 1.6 | Invite 3 members via email addresses; click "إرسال الدعوات" | Invitations sent, success toast shown | □ |
| 1.7 | Select "Scrum" template, click "إنشاء مساحة العمل" | Redirected to dashboard with sample columns | □ |
| 1.8 | Verify invited members appear in team list | 3 pending invitations shown in Team Settings | □ |

### Journey 2: سارة — Kanban Board & Task Management

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 2.1 | From dashboard, click existing project | Kanban board loads with columns (To Do / In Progress / Done) | □ |
| 2.2 | Click "+" to add task; fill title, assignee, priority, due date; submit | Task appears in "To Do" column, real-time update on WebSocket | □ |
| 2.3 | Drag task card from "To Do" to "In Progress" | Card animates to new column, status updated via API, broadcast via WebSocket | □ |
| 2.4 | Click task card to open detail modal | Modal shows title, description, assignee, comments, attachments, timer | □ |
| 2.5 | Add comment; type Arabic text with mentions | Comment saved and broadcast via WebSocket | □ |
| 2.6 | Filter board by assignee | Only tasks for selected assignee shown | □ |
| 2.7 | Logout and login again | Session persists, Kanban state intact | □ |

### Journey 3: أحمد — Time Tracking & Manual Entry

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 3.1 | Login as أحمد; navigate to task detail | Task detail modal with timer widget displayed | □ |
| 3.2 | Click "▶ تشغيل المؤقت" | Timer starts counting elapsed time in real-time | □ |
| 3.3 | Click "⏹ إيقاف المؤقت" after 30 seconds | Timer stops; time entry recorded with correct duration | □ |
| 3.4 | Click "إدخال يدوي" | Manual time entry form displayed | □ |
| 3.5 | Enter 2.5 hours, date, note; submit | Time entry saved with validation (ended_at > started_at) | □ |
| 3.6 | Navigate to Time Reports page | Bar chart + data table showing today's entries | □ |
| 3.7 | Click "تصدير PDF" | PDF report downloaded in Arabic with correct data | □ |
| 3.8 | Click "تصدير CSV" | CSV file downloaded with proper column headers in Arabic | □ |

### Journey 4: أحمد — Multi-Project Switching & Client Report

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 4.1 | From dashboard, verify project list shows all assigned projects | All projects with task counts displayed | □ |
| 4.2 | Switch between projects via project dropdown | Dashboard stats update per project | □ |
| 4.3 | Navigate to Reports, select date range (last 7 days) | Aggregated report with hours per project | □ |
| 4.4 | Click "إرسال التقرير إلى العميل" | Report sent via WhatsApp (simulated) or email | □ |
| 4.5 | Verify offline resilience: toggle airplane mode, start timer | Timer starts locally; queues in Hive | □ |
| 4.6 | Re-enable network | Queued entry syncs to server, badge clears | □ |

### Journey 5: ليلى — Cross-Project Overview & Timeline

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 5.1 | Login as ليلى; view Dashboard | QuickStatsBar shows: active projects, overdue tasks, team workload chart | □ |
| 5.2 | Click "الجدول الزمني" on project | Gantt/timeline view renders with task bars | □ |
| 5.3 | Hover on overdue task | Red highlight, days overdue shown | □ |
| 5.4 | Create new project "الحملة الربعية" with due date | Project created, appears in project list | □ |
| 5.5 | Add 5 tasks with different assignees and priorities | Tasks created, assignees receive WebSocket + notification | □ |
| 5.6 | Navigate to dashboard, verify workload chart | Chart shows each member's task count correctly | □ |

### Journey 6: ليلى — Weekly Report Generation

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 6.1 | Navigate to Reports → Time Report | Report filter panel displayed | □ |
| 6.2 | Select "هذا الأسبوع" filter | KPI cards (total hours, tasks done, completion rate) update | □ |
| 6.3 | View bar chart and pie chart | Charts render <1s, Arabic labels correct | □ |
| 6.4 | Click "تصدير PDF" | PDF generated with all charts + data table | □ |
| 6.5 | Verify data accuracy | Total hours match sum of individual time entries | □ |

### Journey 7: يوسف — Admin: Team Management & RBAC

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 7.1 | Login as يوسف (Owner role); navigate to Team Settings | Member list shows all users with roles | □ |
| 7.2 | Change أحمد's role from Member to Viewer | Role updated; audit log entry created | □ |
| 7.3 | Login as أحمد; attempt to create a task | Task creation fails (Viewer cannot create tasks) | □ |
| 7.4 | Login as يوسف; restore أحمد's role to Member | Permission restored | □ |
| 7.5 | Invite new user via email | Invitation sent; pending status shown | □ |
| 7.6 | Remove a member from workspace | Member removed; workspace count decremented | □ |
| 7.7 | View audit log (activity_logs) | All recent actions logged with timestamp + actor | □ |

### Journey 8: يوسف — Billing & Subscription

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 8.1 | Navigate to Billing page | Current plan (Free) displayed with usage stats | □ |
| 8.2 | Click "تحديث الخطة" → Select Pro plan | Plan upgrade flow initiated | □ |
| 8.3 | Enter payment details (stripe test card) | Payment processed, plan upgraded to Pro | □ |
| 8.4 | Verify Pro features unlocked | Task limit increased from 5/project to unlimited | □ |
| 8.5 | Navigate to Invoice History | Invoice for upgrade displayed | □ |
| 8.6 | Downgrade to Free plan | Plan downgraded; feature restrictions re-applied | □ |

### Journey 9: نورة — Client View & WhatsApp Notification

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 9.1 | Receive WhatsApp invite from أحمد | WhatsApp message with invite link in Arabic | □ |
| 9.2 | Click invite link; register with limited scope | Registered as external viewer; only shared project visible | □ |
| 9.3 | View shared project's task board | Read-only view; no edit controls shown | □ |
| 9.4 | Receive WhatsApp notification when task status changes | WhatsApp message: "تم تحديث المهمة: تصميم الصفحة الرئيسية → تم" | □ |
| 9.5 | View weekly summary report sent via WhatsApp | Report image/card with key stats | □ |

### Journey 10: Critical Path — Error Handling & Recovery

| Step | Action | Expected Result | Pass/Fail |
|------|--------|-----------------|-----------|
| 10.1 | Submit login with wrong password 6 times | Rate limit triggers after 5 attempts; 429 + Retry-After header | □ |
| 10.2 | Upload file >10MB | Rejected with 413 + Arabic error message | □ |
| 10.3 | Submit empty task form | Validation errors shown per field in Arabic | □ |
| 10.4 | Access API without Bearer token | 401 Unauthenticated response | □ |
| 10.5 | Access project from different workspace | 403 Forbidden (workspace isolation) | □ |
| 10.6 | Kill Reverb container; verify fallback polling | Dashboard falls back to 30s polling; reconnects when Reverb returns | □ |
| 10.7 | Trigger Horizon failure; re-queue job | Job retried per configuration | □ |

---

## 3. Test Data Requirements

| Data Type | Source | Quantity |
|-----------|--------|----------|
| Registered users | Demo seeder (`DatabaseSeeder`) | 3 (سارة, أحمد, ليلى) |
| Additional test accounts | Manual creation | 2 (يوسف, نورة) |
| Projects | Demo seeder | 2 existing + 2 created during UAT |
| Tasks | Demo seeder | 6 existing + ~20 created during UAT |
| Time entries | Demo seeder + manual | 3 existing + ~10 created during UAT |
| File uploads | Manual | 3 (image, PDF, large file) |

---

## 4. UAT Execution Process

### Environment
```
URL:    https://staging.tasksyncpro.com
API:    https://staging.tasksyncpro.com/api/v1
WS:     wss://staging.tasksyncpro.com:6001
Admin:  https://staging.tasksyncpro.com/horizon (password-protected)
```

### Pre-requisites (before UAT starts)
- [ ] Staging environment healthy (`GET /api/v1/health` → 200)
- [ ] Database seeded with demo data
- [ ] Mailhog or test mail service configured
- [ ] WhatsApp sandbox configured
- [ ] Test Stripe keys configured
- [ ] All 5 user accounts created with known passwords
- [ ] PHP-FPM, Redis, PostgreSQL, Reverb, Horizon all running
- [ ] UAT testers have access instructions

### Tester Instructions
1. Use Chrome or Firefox (desktop) for web dashboard tests
2. Use Flutter app on Android/iOS emulator for mobile tests (or web as fallback)
3. Record pass/fail for each step
4. Screenshot any failures and note the browser console errors
5. Check `docker compose logs` for backend errors on failures

---

## 5. Acceptance Criteria for Production Go-Live

All criteria must pass for UAT sign-off:

| # | Criterion | How to Verify | Minimum Threshold |
|---|-----------|---------------|-------------------|
| A1 | All 10 core journeys pass | UAT execution report | 100% pass rate on P0 scenarios (1-5), ≥90% overall |
| A2 | No Critical or High bugs open | GitHub Issues / Bug tracker | 0 Critical, 0 High |
| A3 | API P95 latency <500ms under load | k6 test or staging observability | P95 <500ms |
| A4 | Health endpoint returns 200 | `curl https://staging.tasksyncpro.com/api/v1/health` | 200 |
| A5 | WebSocket connects and receives events | Browser console: Echo connector | Connect <2s, event delivery |
| A6 | Email notifications deliver | Mailhog/test inbox | <30s delivery |
| A7 | WhatsApp notifications send | WhatsApp sandbox | Message received |
| A8 | Redis cache operational | `redis-cli ping` → PONG | PONG |
| A9 | Horizon queue processing | Horizon dashboard | Jobs processed, no backlog |
| A10 | DB backup mechanism verified | Manual `pg_dump` test | SQL file created, restorable |

---

## 6. UAT Sign-off Template

```
═══════════════════════════════════════════════
 TASKSYNC PRO — UAT SIGN-OFF

 Date: _______________
 Environment: staging.tasksyncpro.com

 PERSONAS TESTED (circle):  سارة  أحمد  ليلى  يوسف  نورة
 JOURNEYS TESTED (circle):  1  2  3  4  5  6  7  8  9  10
 TOTAL PASS RATE: ___ / 10 journeys (≥90% required)

 ACCEPTANCE CRITERIA STATUS:
   A1 (all journeys pass P0):         ☐ PASS  ☐ FAIL
   A2 (no Critical/High bugs):        ☐ PASS  ☐ FAIL
   A3 (API latency <500ms):           ☐ PASS  ☐ FAIL
   A4 (health endpoint 200):          ☐ PASS  ☐ FAIL
   A5 (WebSocket functional):         ☐ PASS  ☐ FAIL
   A6 (Email notifications):          ☐ PASS  ☐ FAIL
   A7 (WhatsApp notifications):       ☐ PASS  ☐ FAIL
   A8 (Redis operational):            ☐ PASS  ☐ FAIL
   A9 (Horizon processing):           ☐ PASS  ☐ FAIL
   A10 (DB backup verified):          ☐ PASS  ☐ FAIL

 OVERALL VERDICT:  ☐ APPROVED  ☐ CONDITIONAL  ☐ REJECTED

 BLOCKING ISSUES (if CONDITIONAL/REJECTED):
 1. _______________________________________________
 2. _______________________________________________

 TESTER NAME: _________________________  SIGNATURE: _________________________
 STAKEHOLDER NAME: ____________________  SIGNATURE: _________________________

═══════════════════════════════════════════════
```

---

## 7. UAT Execution Log

| Journey | Tester | Date | Result | Notes |
|---------|--------|------|--------|-------|
| 1 — Workspace Setup | | | ☐ | |
| 2 — Kanban & Tasks | | | ☐ | |
| 3 — Time Tracking | | | ☐ | |
| 4 — Multi-Project | | | ☐ | |
| 5 — Cross-Project View | | | ☐ | |
| 6 — Weekly Reports | | | ☐ | |
| 7 — Admin RBAC | | | ☐ | |
| 8 — Billing | | | ☐ | |
| 9 — Client & WhatsApp | | | ☐ | |
| 10 — Error Handling | | | ☐ | |

---

*Prepared by Linda Schmidt (sofi-devops-cloud-lead) · Gate 7 Production UAT Plan*
*Next: Execute UAT → Capture sign-off → Production deploy*
