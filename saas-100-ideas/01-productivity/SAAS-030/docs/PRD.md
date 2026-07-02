# PRD: TimeSheet Pro (SAAS-030)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** تطبيق تتبع ساعات العمل الذكي — مؤقت زمني، تصنيف مهام، تقارير إنتاجية.
- **Problem:** شركات البرمجة والفريلانسر يضيعون وقتاً في تتبع ساعات العمل. تقدير المهام غير دقيق، الفوترة للعملاء تعتمد على تخمين.
- **Proposed solution:** Laravel API + React Dashboard للتقارير + Flutter App للتوقيت المباشر.

## 2. Market & Opportunity
- **Target market size:** سوق Time Tracking ~$800M. تزايد العمل عن بعد يضاعف الطلب.
- **Customer segment:** B2B (شركات برمجة، وكالات رقمية) + B2C (فريلانسر).
- **Competitor landscape:** Toggl, Harvest, Clockify, RescueTime.
- **Difference:** واجهة عربية، تقارير إنتاجية متقدمة، تكامل مع أنظمة الفوترة المحلية، تسعير مجاني للأفراد.

## 3. User Personas
- **Primary 1 — مطور (أحمد):** يعمل على 3 مشاريع في اليوم. يريد مؤقتاً بسيطاً، تصنيف المهام، تقارير أسبوعية.
- **Primary 2 — مدير مشروع (نورة):** تريد رؤية ساعات الفريق، تقدير تكلفة المشروع، مقارنة المخطط بالفعل.
- **Admin — مدير الشركة:** يراقب إنتاجية الفرق، يحلل bottlenecks.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: TimeEntry, Project, Task, Client, Report, Invoice
- RESTful endpoints: CRUD entries/projects/tasks
- Timer engine: start/stop, auto-pause, idle detection
- Auth & roles: JWT, roles (admin, manager, member, client)
- Reports aggregation: daily, weekly, monthly, per project
- Notifications: تذكير بتسجيل الوقت، تقرير أسبوعي

### React Dashboard (Web)
- جدول زمني (توقيت مباشر للفريق)
- تقارير: ساعات لكل مشروع/مهمة/شخص
- لوحة المشاريع: المخطط vs الفعلي
- إدارة الفرق والمشاريع
- تصدير (PDF, CSV, Excel)

### Flutter App (Mobile)
- مؤقت تشغيل/إيقاف بزر واحد
- اختيار المشروع والمهمة
- عرض آخر 5 تسجيلات
- إضافة إدخال يدوي (لأوقات فاتت)
- إشعار تذكير
- إحصائيات سريعة

## 5. Data Model (MVP)
- **Client:** id, name, email, company, hourly_rate
- **Project:** id, client_id, name, description, budget_hours, status, color
- **Task:** id, project_id, name, estimated_hours, billable (bool)
- **TimeEntry:** id, user_id, task_id, start_time, end_time, duration, description, billable
- **Report:** id, project_id, period, total_hours, billable_hours, cost, generated_at
- **Team:** id, name, member_ids (JSON)

## 6. API Endpoints (MVP)
- `POST /auth/register`, `POST /auth/login`
- `GET /clients`, `POST /clients`
- `GET /projects`, `POST /projects`, `PUT /projects/{id}`
- `GET /tasks`, `POST /tasks`
- `GET /time-entries`, `POST /time-entries/start`, `POST /time-entries/stop`, `POST /time-entries/manual`
- `GET /time-entries/running` (current timer)
- `GET /reports/daily`, `GET /reports/weekly`, `GET /reports/project/{id}`
- `GET /reports/export?format=csv`

## 7. User Interface (Screen List)
- **Dashboard:** من يعمل الآن؟، ساعات اليوم، آخر الإدخالات
- **Projects:** قائمة مشاريع مع التقدم
- **Reports:** رسوم بيانية (ساعات لكل مشروع، لكل شخص)
- **Timesheet:** جدول الإدخالات اليومي/الأسبوعي
- **Mobile - Timer:** زر تشغيل/إيقاف كبير
- **Mobile - Entry:** اختيار مشروع + مهمة + وصف
- **Mobile - History:** سجل الإدخالات

## 8. Business Model
- **Pricing tiers:**
  - Free (1 user, unlimited entries): $0
  - Pro (5 users): $19/شهر
  - Team (20 users): $59/شهر
  - Enterprise (unlimited): $149/شهر
- **Free trial:** مجاني للأبد لـ 1 مستخدم
- **Target MRR per client:** $19-$149

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Projects/Tasks CRUD
- Phase 2 (Weeks 3-4): Timer engine + Time entries API
- Phase 3 (Weeks 5-6): React Dashboard + Reports + Charts
- Phase 4 (Weeks 7-8): Flutter App + Notifications + Export

## 10. Risk & Mitigation
- **Technical risk:** idle detection يحتاج activity monitoring. → استخدام last interaction timestamp.
- **Market risk:** Toggl مجاني وقوي. → التركيز على التقارير المتقدمة والتكامل المحلي كميزة.
- **Adoption risk:** المطورون ينسون تشغيل المؤقت. → إشعارات ذكية + إدخال يدوي.
