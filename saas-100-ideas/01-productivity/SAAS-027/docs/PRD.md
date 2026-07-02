# PRD: ShiftMaster (SAAS-027)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام جدولة دوام الموظفين الذكي — توزيع المناوبات، تبديل الشفتات، تتبع الحضور والغياب.
- **Problem:** المطاعم والمستشفيات والمتاجر تعاني من جدولة المناوبات يدوياً، تعارض المواعيد، غيابات اللحظة الأخيرة.
- **Proposed solution:** Laravel API + React Dashboard للمشرفين + Flutter App للموظفين.

## 2. Market & Opportunity
- **Target market size:** سوق Workforce Management ~$9B. قطاع المطاعم والرعاية الصحية الأكبر.
- **Customer segment:** B2B (مطاعم، مستشفيات، متاجر، فنادق).
- **Competitor landscape:** 7shifts, Deputy, When I Work, Homebase.
- **Differentiation:** دعم كامل للعربية، تسعير حسب عدد الموظفين ($2/موظف)، تكامل مع أنظمة الحضور المحلية.

## 3. User Personas
- **Primary 1 — مدير مطعم (ناصر):** يدير 20 موظفاً. يريد توزيع المناوبات الأسبوعية بسرعة، معرفة من سيحضر.
- **Primary 2 — موظف (لمى):)** نادلة تريد رؤية جدول دوامها، التبديل مع زميل، تقديم طلب إجازة.
- **Admin — مدير تشغيلي:** يراقب دوام كل الفروع، تقارير تكلفة المناوبات.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Shift, Schedule, Employee, SwapRequest, Attendance, Location
- RESTful endpoints: CRUD shifts/schedules/swaps
- Scheduling engine: auto-generate shifts based on rules & demand
- Auth & roles: JWT, roles (admin, manager, employee)
- Notifications: Push عن جدول جديد، تأكيد تبديل، تذكير بدء الدوام

### React Dashboard (Web)
- تقويم المناوبات drag & drop
- نموذج جدولة تلقائية (auto-schedule)
- إدارة طلبات التبديل بالموافقة
- تتبع الحضور: ساعة دخول/خروج
- تقارير: ساعات العمل، تكلفة المناوبات، الغياب

### Flutter App (Mobile)
- عرض جدول الدوام الأسبوعي
- طلب تبديل مناوبة مع زميل
- تسجيل الدخول (GPS + QR)
- الإشعارات (تذكير بدوام، تأكيد تبديل)
- طلب إجازة

## 5. Data Model (MVP)
- **Employee:** id, user_id, location_id, position, wage, availability (JSON)
- **Location:** id, name, address, timezone
- **Shift:** id, location_id, title, start_time, end_time, min_employees, max_employees
- **Schedule:** id, location_id, week_start, published (bool)
- **ScheduleShift:** id, schedule_id, shift_id, employee_id, date
- **SwapRequest:** id, schedule_shift_id, requested_by, swap_with, status, reason
- **Attendance:** id, employee_id, schedule_shift_id, check_in, check_out, status

## 6. API Endpoints (MVP)
- `POST /auth/login`, `POST /auth/register`
- `GET /employees`, `POST /employees`
- `GET /shifts`, `POST /shifts`
- `GET /schedules`, `POST /schedules/generate`, `POST /schedules/publish`
- `GET /schedule-shifts`, `PATCH /schedule-shifts/{id}/assign`
- `POST /swaps`, `PATCH /swaps/{id}/approve`, `PATCH /swaps/{id}/reject`
- `POST /attendance/checkin`, `POST /attendance/checkout`
- `GET /reports/hours?period=week`

## 7. User Interface (Screen List)
- **Dashboard:** مناوبات اليوم، غياب، طلبات تبديل معلقة
- **Schedule Calendar:** تقويم أسبوعي/شهري drag & drop
- **Shift Editor:** إضافة/تعديل شفت
- **Swaps:** طلبات التبديل مع موافقة/رفض
- **Attendance:** سجل الحضور مع فلترة
- **Mobile - Home:** جدولي لهذا الأسبوع
- **Mobile - Clock:** تسجيل دخول/خروج
- **Mobile - Swap:** طلب تبديل

## 8. Business Model
- **Pricing tiers:**
  - Starter (up to 10 employees): $29/شهر
  - Growth (11-50 employees): $79/شهر
  - Scale (51-200 employees): $199/شهر
- **Free trial:** 14 يوم
- **Target MRR per client:** $29-$199

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Employees/Shifts CRUD
- Phase 2 (Weeks 3-4): React Dashboard + Calendar + Auto-schedule
- Phase 3 (Weeks 5-6): Flutter App + Swaps
- Phase 4 (Weeks 7-8): Attendance + Reports + Geolocation

## 10. Risk & Mitigation
- **Technical risk:** Auto-schedule algorithm NP-hard. → البدء بخوارزمية greedy بسيطة.
- **Market risk:** المطاعم قد تكون بطيئة في التبني. → استهداف سلسلة مطاعم كعملاء أوليين.
- **Adoption risk:** الموظفون يرفضون التطبيق. → تصميم بسيط مع مزايا واضحة (رؤية الجدول، طلب تبديل).
