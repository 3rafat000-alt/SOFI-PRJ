# PRD: StampMe (SAAS-046)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام حضور وانصراف ذكي — تسجيل عبر GPS وتعرّف على الوجه، تقارير حضور شهرية، إشعارات للتأخير.
- **Problem:** الشركات والمصانع تواجه صعوبات في تتبع حضور الموظفين. البصمة التقليدية غير صحية (كورونا)، والسجلات اليدوية قابلة للتلاعب.
- **Solution:** StampMe — تطبيق حضور وانصراف عبر الجوال مع GPS للتحقق من الموقع وبصمة وجه للتأكيد، وتقارير آنية.

## 2. Market & Opportunity
- **Target market:** سوق أنظمة الحضور ~$4.5B (2025)، نمو 15% CAGR.
- **Customer segment:** B2B — شركات (20-500 موظف)، مصانع، مدارس، عيادات.
- **Competitors:**
  - BambooHR: HR suite شامل لكن غالي ومعقد.
  - Jibble: جيد للحضور لكن بصمة وجه محدودة.
  - ZKTeco: أجهزة بصمة فيزيائية لكن تحتاج أجهزة مخصصة.
  - Time Doctor: تتبع وقت لكن غالي وموجه للفريلانسر.
- **Differentiation:** بدون أجهزة مادية، GPS + Face Auth مدمج، تقارير عربية، سعر مناسب للشركات المتوسطة.

## 3. User Personas

### الشخصية الأساسية: سارة — مديرة موارد بشرية
- **الدور:** مسؤولة عن متابعة حضور وانصراف 100 موظف
- **الأهداف:** تقارير حضور دقيقة، تقليل التلاعب، أتمتة حساب التأخير
- **المشكلات:** الموظفون يوقعون لبعضهم، الأجهزة التقليدية تتعطل، التقارير اليدوية تستهلك وقتاً

### الشخصية الثانوية: فهد — عامل في مصنع
- **الدور:** يحتاج تسجيل حضور سريع بدون تعقيد
- **الأهداف:** بصمة وجه سريعة، تتبع ساعات العمل الإضافي
- **المشكلات:** لا يمتلك جهاز كمبيوتر، الموقع بعيد، اللغة الإنجليزية صعبة

### Admin: مدير النظام
- يدير الموظفين، الفرق، فترات العمل، الإجازات، صلاحيات HR.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Employee, AttendanceRecord, LeaveRequest, WorkSchedule, GeolocationFence, FaceBiometric
- GPS verification: compare check-in coordinates with assigned location
- Face recognition via external API (AWS Rekognition / Azure Face)
- Attendance calculation: auto compute late, overtime, missing
- Leave management: request → approve → deduct balance

### React Dashboard (Web)
- Attendance calendar view (green/orange/red per day)
- Employee grid: daily status, summary cards
- Reports: monthly summary, late report, overtime report (PDF export)
- Work schedule editor: shifts, grace period, break time
- Geo-fence manager: set allowed location + radius on map
- Leave management: request list, approve/reject, balance tracking

### Flutter App (Mobile)
- Check-in/out with one tap: GPS capture + face scan
- Face scan with liveness detection (blink check)
- Attendance status: view today's record, weekly hours
- Leave request: submit with type, date, reason
- Manager view: approve leaves, view team attendance
- Notifications: reminder to check-in, late alert, leave approved

## 5. Data Model (MVP)
- **Employee**: id, user_id, employee_code, department, position, hire_date, base_salary, leave_balance
- **AttendanceRecord**: id, employee_id, check_in_time, check_out_time, check_in_location (lat/lng), check_out_location, face_verified, status (on-time/late/absent/half-day)
- **WorkSchedule**: id, name, start_time, end_time, grace_minutes, break_start, break_end, working_days_json
- **GeolocationFence**: id, name, latitude, longitude, radius_meters, address
- **LeaveRequest**: id, employee_id, type (sick/annual/emergency), start_date, end_date, reason, status (pending/approved/rejected), approved_by
- **FaceBiometric**: id, employee_id, face_vector_json, enrolled_at, last_verified_at

## 6. API Endpoints (MVP)
- `POST /api/attendance/check-in` — GPS coordinates + face image → verify → record
- `POST /api/attendance/check-out` — same as above
- `GET /api/attendance/today` — today's status
- `GET /api/attendance/range` — date range records
- `GET /api/attendance/stats/monthly` — monthly summary
- `CRUD /api/leaves` — full leave management
- `CRUD /api/schedules` — work schedule CRUD
- `CRUD /api/geo-fences` — geo-fence CRUD
- `POST /api/face/enroll` — register face
- `POST /api/face/verify` — verify face (liveness check)
- `GET /api/reports/late`, `GET /api/reports/overtime` — report exports
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): Attendance calendar, quick stats (present/late/absent), pending leaves
- **Attendance Grid** (React): Employee table with today's status, filters by department
- **Reports** (React): Monthly summary PDF, late analysis, overtime calculation, CSV export
- **Schedule Editor** (React): Visual shift builder, team assignment, exception dates
- **Geo-Fence Map** (React): Interactive map, draw radius, assign locations
- **Settings** (React): Company info, face recognition toggle, grace period, holidays
- **Mobile** (Flutter): Home → check-in/out button (big) → face scan → success screen
- **Mobile History**: Weekly calendar, daily details, summary stats
- **Mobile Leave**: Balance display → request form → status

## 8. Business Model
- **Free**: 10 employees, basic GPS check-in, no face recognition
- **Starter**: $2/employee/month — face auth, geo-fence, reports, leave management
- **Pro**: $4/employee/month — everything + overtime calculation, API access, priority support
- **Enterprise**: Custom — SSO, custom reports, dedicated server, on-prem
- **Free trial**: 30 days Pro (up to 30 employees)
- **Target MRR/client**: $40–$200 (for 20-50 employees)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Employee/Attendance models + check-in/out + schedule + geo-fence
- **Phase 2 (Weeks 3-4)**: React Dashboard — attendance grid, calendar, reports, schedule editor
- **Phase 3 (Weeks 5-6)**: Flutter App — check-in with GPS + face scan, attendance history, leave flow
- **Phase 4 (Weeks 7-8)**: Face recognition integration, liveness detection, PDF reports, performance testing

## 10. Risk & Mitigation
- **Technical**: Face recognition accuracy in varying light → Mitigation: AWS Rekognition (99%+), with liveness detection
- **Privacy**: Biometric data concerns → Mitigation: on-device vector only (no raw images stored), GDPR compliance
- **Technical**: GPS spoofing → Mitigation: combine GPS + WiFi fingerprint + device ID + face verification
- **Market**: Resistance to biometric tracking → Mitigation: offer GPS-only tier, clear privacy policy
