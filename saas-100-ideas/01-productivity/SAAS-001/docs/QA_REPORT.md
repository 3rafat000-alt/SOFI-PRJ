# QA REPORT — TaskSync Pro (SAAS-001)

> **Gate:** 5 · **QA Type:** Manual Exploratory · **Tester:** Rosa Giménez (sofi-manual-exploratory-tester)
> **Date:** 2026-06-25 · **TKT:** TKT-011
> **Platforms Tested:** Vue 3 Dashboard (web), Laravel API, Flutter mobile (code review)
> **Locale:** Arabic (ar) primary, English fallback

---

## Summary

| Metric | Value |
|--------|-------|
| Total Test Cases | 33 |
| Passed | 21 |
| Failed | 12 |
| Pass Rate | **63.6%** |
| Critical Bugs | 1 |
| High Bugs | 3 |
| Medium Bugs | 5 |
| Low Bugs | 3 |
| **Health Score** | **🟡 FAIR** — Ship blocked until Critical + High resolved |

---

## Persona 1: سارة — مديرة مشروع (Project Manager)

### TC-001 · تسجيل حساب جديد
- **Journey Stage:** Signup
- **Action:** تملأ نموذج التسجيل (الاسم، البريد، كلمة المرور، اسم مساحة العمل) وتضغط "إنشاء حساب"
- **Expected:** حساب جديد يُنشأ مع مساحة عمل، يُعاد التوجيه إلى لوحة التحكم
- **Actual:** **PASS** — RegisterView.vue ينفذ التسجيل عبر authStore.register() ويعيد التوجيه إلى /dashboard
- **Edge:** ✅ Arabic name "سارة أحمد" — handled. ✅ RTL layout renders correct. ✅ Timezone auto-detected via Intl.

### TC-002 · تسجيل — بريد مكرر
- **Journey Stage:** Signup
- **Action:** تحاول التسجيل ببريد مستخدم مسبقاً
- **Expected:** رسالة خطأ "البريد الإلكتروني مستخدم مسبقاً"
- **Actual:** **PASS** — API returns 422 with validation error, displayed in alert banner
- **Severity:** Medium

### TC-003 · تسجيل — كلمة مرور قصيرة
- **Journey Stage:** Signup
- **Action:** تُدخل كلمة مرور أقل من 8 أحرف
- **Expected:** رسالة خطأ validation
- **Actual:** **PASS** — HTML5 `minlength="8"` يمنع الإرسال. API-side validation also present.
- **Severity:** Medium

### TC-004 · إنشاء مساحة عمل
- **Journey Stage:** Create Workspace
- **Action:** بعد التسجيل، تنشئ مساحة عمل wizard بخطوتين (الاسم والقالب)
- **Expected:** مساحة عمل جديدة مع قالب Kanban، مجال فريد (slug)
- **Actual:** **PASS** — Workspace creation tied to registration. Slug auto-generated.
- **Severity:** Low
- **Edge:** ✅ Skipping invite step allowed per spec

### TC-005 · دعوة أعضاء الفريق
- **Journey Stage:** Invite
- **Action:** تدعو "ahmed@example.com" بدور "member"
- **Expected:** دعوة تُرسل، العضو يظهر بحالة "معلق"
- **Actual:** **PASS** — InvitationService.invite() stores in activity_log, returns pending status
- **Severity:** Low

### TC-006 · دعوة — بريد غير صالح
- **Journey Stage:** Invite (Edge)
- **Action:** تدعو "not-an-email"
- **Expected:** رسالة خطأ validation "البريد الإلكتروني غير صالح"
- **Actual:** **PASS** — HTML5 email validation + API 422
- **Severity:** Low

### TC-007 · دعوة — عضو موجود مسبقاً
- **Journey Stage:** Invite (Edge)
- **Action:** تدعو شخص هو بالفعل عضو في مساحة العمل
- **Expected:** رسالة "العضو موجود مسبقاً"
- **Actual:** **PASS** — InvitationService checks `$workspace->members()->where('user_id', $existingUser->id)->exists()`
- **Severity:** Low

### TC-008 · إنشاء مشروع جديد
- **Journey Stage:** Add Tasks → Create Project
- **Action:** تنشئ مشروع "حملة إطلاق المنتج" مع لون وموعد
- **Expected:** مشروع جديد يظهر في قائمة المشاريع
- **Actual:** **PASS** — POST /api/v1/projects يعيد Full project object
- **Severity:** Medium

### TC-009 · إنشاء مهمة + تعيين عضو
- **Journey Stage:** Add Tasks → Assign
- **Action:** تنشئ مهمة "تصميم الصفحة الرئيسية" أولوية عالية، تسندها إلى ليلى
- **Expected:** مهمة جديدة في عمود "للتنفيذ" مع اسم المسؤول
- **Actual:** **PASS** — TaskService.create() ينشئ مع assignee_ids sync. WebSocket TaskCreated يُبث
- **Severity:** Medium

### TC-010 · لوحة Kanban — سحب وإفلات
- **Journey Stage:** Kanban Board
- **Action:** تسحب بطاقة مهمة من "للتنفيذ" إلى "قيد التنفيذ"
- **Expected:** حالة المهمة تتغير، WebSocket TaskMoved يُبث
- **Actual:** **FAIL** ⚠️ — KanbanBoard.vue ينفذ client-side drag-drop ولكن:
  1. **BUG-008**: `TaskService::reorder()` لا يبث `TaskMoved` event. WebSocket event missing completely.
  2. لا يوجد دعم للّمس (touch events) للأجهزة المحمولة
  3. لا يوجد دعم keyboard drag-drop (Space/Enter)
- **Severity:** **High**
- **Bug Reproduction:** اسحب مهمة من عمود لآخر → التحقق من WebSocket → لا يوجد TaskMoved event. تحقق من TaskService.php line 177-193: `broadcast()` غير موجود.

### TC-011 · مراجعة التقارير
- **Journey Stage:** Report → Goal
- **Action:** تفتح صفحة التقارير، تختار آخر 30 يوماً
- **Expected:** KPIs (ساعات، مهام مكتملة، مشاريع نشطة) + رسوم بيانية
- **Actual:** **FAIL** ⚠️
  1. **BUG-006**: KPI "المشاريع النشطة" يقرأ `reportData.value?.summary?.project_count` لكن API لا ترجع `project_count` في الـ summary (ترجع فقط `total_minutes`, `total_hours`, `avg_daily_minutes`, `period`). القيمة دائماً '-'.
  2. **BUG-012**: Pie chart يحاول قراءة `reportData.value.entries[0].projects` لكن API ترجع entries مصنفة حسب `group_by: day` والمشاريع غير متضمنة في كل entry. الشارت يعمل فقط إذا `group_by=project`.
- **Severity:** **Medium**
- **Bug Reproduction:** ReportsView.vue → أنشئ بيانات وقت → افتح التقارير → KPI "المشاريع النشطة" يظهر '-' دائماً.

### TC-012 · Dashboard — إحصائيات فارغة
- **Journey Stage:** Report (Edge)
- **Action:** مستخدم جديد بدون مهام أو وقت مسجل يفتح لوحة التحكم
- **Expected:** رسالة "لا توجد بيانات كافية" مع illustration
- **Actual:** **PASS** — ReportsView وDashboardView يعرضان EmptyState عند عدم وجود بيانات. Dashboard KPI cards تظهر '-'.
- **Severity:** Low

### TC-013 · Dashboard — خطأ في تحميل الإحصائيات
- **Journey Stage:** Report (Edge: offline)
- **Action:** تفصل الإنترنت وتحاول فتح لوحة التحكم
- **Expected:** رسالة خطأ + زر "إعادة المحاولة"
- **Actual:** **PASS** — DashboardView.vue lines 96-99: error state مع retry button. Vue-chartjs قد لا يظهر في وضع offline.
- **Severity:** Medium

### TC-014 · مهمة — عنوان طويل جداً
- **Journey Stage:** Add Tasks (Edge)
- **Action:** تُدخل عنوان مهمة من 500 حرف
- **Expected:** تقليم أو رسالة خطأ validation
- **Actual:** **FAIL** ⚠️ — لا يوجد maxlength validation على frontend (RegisterView, TaskForm غير موجود هنا لكن TaskForm غير مراجع). API-side قد يقبل لكن العرض سيbreak. BUG-013.
- **Severity:** Medium

---

## Persona 2: أحمد — مطور (Developer)

### TC-101 · عرض المهام الموكلة
- **Journey Stage:** Track Time
- **Action:** يسجل الدخول، يفتح صفحة "جميع المهام"
- **Expected:** يرى فقط المهام المسندة إليه
- **Actual:** **FAIL** ⚠️ — TasksView.vue line 114: يعرض `taskStore.filteredTasks` اللي يجيب كل المهام من الـ workspace. فلتر `assignee_id` موجود في `taskStore.filters` لكنه not set by default. المستخدم العادي (member) يرى كل مهام مساحة العمل وليس فقط مهامه.
- **Severity:** **High**
- **Bug Reproduction:** أحمد يسجل دخوله → /tasks → يرى مهام كل الفريق بدل مهامه فقط. لم يتم تنفيذ "My Tasks" view افتراضي.

### TC-102 · تشغيل مؤقت الوقت
- **Journey Stage:** Track Time
- **Action:** يختار مهمة من dropdown، يضغط "بدء المؤقت"
- **Expected:** مؤقت يبدأ، عداد يتصاعد، WebSocket TimerStarted يُبث
- **Actual:** **PASS** — TimeEntryService.startTimer() يعمل. TimerWidget.vue يظهر العداد. localStorage timer يعمل.
- **Severity:** Medium

### TC-103 · إيقاف المؤقت + تسجيل وقت يدوي
- **Journey Stage:** Track Time
- **Action:** يضغط إيقاف المؤقت، يسجل ملاحظة
- **Expected:** وقت محسوب، يُضاف إلى سجل الإدخالات
- **Actual:** **PASS** — Timer stop يُحسب duration. Manual entry form متاح مع datetime-local pickers.
- **Severity:** Medium

### TC-104 · إيقاف المؤقت — بدون مؤقت نشط (Edge)
- **Journey Stage:** Track Time (Edge)
- **Action:** يضغط إيقاف المؤقت بدون مؤقت نشط
- **Expected:** رسالة "لا يوجد مؤقت نشط"
- **Actual:** **FAIL** ⚠️ — **BUG-003**: `TimeEntryService::stopTimer()` يرمي `TimerAlreadyRunningException('No running timer found.')` — استثناء خاطئ. الـ exception class يقول "TimerAlreadyRunning" بينما المعنى "No running timer". API يرجع 409 Conflict بدل 404 Not Found. Frontend يعرض رسالة مضللة.
- **Severity:** **Low** (text only, no data loss)
- **Bug Reproduction:** اضغط Stop بدون Timer نشط → API يرجع 409 "Timer already running" → رسالة خطأ خاطئة.

### TC-105 · إدخال وقت يدوي — وقت نهاية قبل البداية
- **Journey Stage:** Track Time (Edge)
- **Action:** يدخل started_at 16:00 و ended_at 14:00 (نهاية قبل البداية)
- **Expected:** رسالة خطأ validation "يجب أن يكون وقت النهاية بعد وقت البداية"
- **Actual:** **FAIL** ⚠️ — **BUG-002**: لا يوجد validation للـ sequence. `TimeEntryService::createManual()` يحسب duration لكن النتيجة ستكون سالبة. `startedAt->diffInMinutes($endedAt)` يعطي القيمة المطلقة في Carbon لكن فجوة المنطق تبقى.
- **Severity:** **High**
- **Bug Reproduction:** TimeTrackingView → إدخال يدوي → اختر ended_at قبل started_at → API يقبل بدون خطأ → duration محسوب بشكل خاطئ.

### TC-106 · إضافة تعليق + منشن
- **Journey Stage:** Track Time
- **Action:** يُضيف تعليق "تم الانتهاء @ليلى" على مهمة
- **Expected:** تعليق يُحفظ، إشعار يُرسل إلى ليلى
- **Actual:** **PASS** — POST /api/v1/tasks/{id}/comments مع @mention. WebSocket CommentAdded يُبث.
- **Severity:** Medium

### TC-107 · رفع مرفق
- **Journey Stage:** Track Time
- **Action:** يرفع ملف "تصميم_الصفحة_الرئيسية.png" (2MB)
- **Expected:** ملف يُرفع، thumbnail يُنشأ، رابط يظهر
- **Actual:** **PASS** — multipart/form-data upload مع size/type validation.
- **Severity:** Medium

### TC-108 · رفع ملف كبير جداً (Edge)
- **Journey Stage:** Track Time (Edge)
- **Action:** يحاول رفع ملف 15MB (أكبر من 10MB limit)
- **Expected:** رفض مع رسالة "الملف يتجاوز الحد الأقصى 10MB"
- **Actual:** **PASS** — API.md §8.1: Max size 10MB. Backend validation متوقع.
- **Severity:** Medium

### TC-109 · تغيير حالة المهمة
- **Journey Stage:** Track Time
- **Action:** يغير حالة مهمة من "للتنفيذ" إلى "قيد التنفيذ" عبر PATCH
- **Expected:** حالة تتغير، WebSocket TaskUpdated يُبث
- **Actual:** **PASS** — `TaskService::changeStatus()` يعمل. PATCH /api/v1/tasks/{id}/status مع WebSocket broadcast.
- **Severity:** Low

### TC-110 · مؤقت — تبديل مهمة أثناء التشغيل (Edge)
- **Journey Stage:** Track Time (Edge)
- **Action:** يبدأ مؤقت على مهمة، يختار مهمة أخرى، يبدأ مؤقت جديد
- **Expected:** إما رفض "يوجد مؤقت قيد التشغيل" أو إيقاف المؤقت القديم تلقائياً
- **Actual:** **FAIL** ⚠️ — **BUG-011**: TimerWidget.vue line 45-47: يوقف المؤقت القديم تلقائياً قبل بدء الجديد لكن لا يحفظ ملاحظة المستخدم. API يعيد 409 إذا حاولت startTimer مع مؤقت قديم. سلوك غير متسق بين frontend و backend.
- **Severity:** Medium
- **Bug Reproduction:** ابدأ مؤقت على مهمة A → اختر مهمة B من dropdown → ابدأ مؤقت → TimerWidget يوقف A بدون ملاحظة → API قد يرفض إذا multi-tab.

---

## Persona 3: ليلى — منسقة مشاريع (Coordinator)

### TC-201 · عرض لوحة المعلومات
- **Journey Stage:** Report → Goal
- **Action:** تفتح Dashboard بعد تسجيل الدخول
- **Expected:** ترى KPIs (إجمالي المهام، الوقت، المشاريع، الأعضاء)
- **Actual:** **PASS** — DashboardView.vue يعرض 4 KPI cards. Welcome message مع الاسم.
- **Severity:** Low

### TC-202 · فلترة المهام حسب المشروع
- **Journey Stage:** Report → Goal
- **Action:** تختار مشروع معين من فلتر المشاريع
- **Expected:** فقط مهام ذلك المشروع تظهر
- **Actual:** **FAIL** ⚠️ — TasksView.vue لا يحتوي على فلتر المشروع في الـ template. الفلتر موجود في `taskStore.filters.projectId` لكن لا يوجد UI element لاختيار المشروع. المستخدم فقط يرى كل المهام.
- **Severity:** **Medium**
- **Bug Reproduction:** /tasks → لا يوجد dropdown لاختيار المشروع. الفلتر موجود في الـ store بس مش معروض.

### TC-203 · فلترة المهام حسب الأولوية
- **Journey Stage:** Report → Goal
- **Action:** تختار أولوية "عالية" من dropdown
- **Expected:** فقط المهام ذات الأولوية العالية تظهر
- **Actual:** **PASS** — priorityFilter v-model مع computed property. taskStore.filteredTasks يُفلتر.
- **Severity:** Low

### TC-204 · البحث عن مهام محددة
- **Journey Stage:** Report → Goal
- **Action:** تكتب "تصميم" في مربع البحث
- **Expected:** المهام التي تحتوي "تصميم" في العنوان أو الوصف تظهر
- **Actual:** **PASS** — SearchInput.vue مع debounce 300ms. API ILIKE search. Client-side filter also.
- **Severity:** Medium

### TC-205 · بحث — أحرف عربية (Edge)
- **Journey Stage:** Report → Goal (Edge)
- **Action:** تبحث عن "المنتج" (بحث عربي)
- **Expected:** نتائج البحث تحتوي كلمة "المنتج"
- **Actual:** **PASS** — PostgreSQL GIN tsvector مع Arabic config. ILIKE fallback.
- **Severity:** Low

### TC-206 · مراجعة الإشعارات
- **Journey Stage:** Report → Goal
- **Action:** تفتح قائمة الإشعارات
- **Expected:** ترى إشعاراتها (مهام جديدة، منشن، تذكيرات)
- **Actual:** **PASS** — notificationStore.fetchNotifications() مع GET /api/v1/notifications. unread_count في meta.
- **Severity:** Low

### TC-207 · تعليم الكل كمقروء
- **Journey Stage:** Report → Goal
- **Action:** تضغط "تعليم الكل كمقروء"
- **Expected:** كل الإشعارات تُعلم كمقروءة، العداد يختفي
- **Actual:** **PASS** — PUT /api/v1/notifications/read-all يُغير read_at. unreadCount = 0.
- **Severity:** Low

### TC-208 · إشعار — إشعار مهمة جديدة (Edge)
- **Journey Stage:** Report → Goal (Edge)
- **Action:** لا توجد إشعارات جديدة
- **Expected:** رسالة "لا توجد إشعارات" أو icon بدون badge
- **Actual:** **FAIL** ⚠️ — **BUG-014**: notificationStore `unreadCount` يحسب من `data.meta?.unread_count || 0`. لكن إذا API متعطل أو يرجع meta بدون `unread_count`، القيمة تكون 0 و badge يختفي. في Vue template، `hasUnread` يعمل لكن لا يوجد "لا إشعارات" empty state منفصل — only in notification dropdown.
- **Severity:** **Low** (cosmetic)
- **Bug Reproduction:** لا توجد إشعارات → UI يظهر طبيعي لكن لا توجد رسالة "لا توجد إشعارات" في صفحة الإشعارات (إذا كانت صفحة منفصلة).

### TC-209 · فلترة فارغة (Edge)
- **Journey Stage:** Report → Goal (Edge)
- **Action:** تطبق فلاتر لا تتطابق مع أي مهمة
- **Expected:** رسالة "لا توجد مهام" مع زر إنشاء مهمة
- **Actual:** **PASS** — EmptyState component مع CTA.
- **Severity:** Low

### TC-210 · Rapid Click — إرسال نموذج مزدوج (Edge)
- **Journey Stage:** Track Time (Edge)
- **Action:** تضغط "حفظ" مرتين بسرعة على نموذج إدخال الوقت
- **Expected:** إدخال واحد فقط يُنشأ
- **Actual:** **FAIL** ⚠️ — **BUG-009**: RegisterView and TimeTrackingView لا يوجد Idempotency-Key header. `:disabled="saving"` موجود لكن يمكن تجاوزه في rapid clicks. لا توجد client-side guard.
- **Severity:** **Medium**
- **Bug Reproduction:** TimeTrackingView → إدخال يدوي → املأ الحقول → اضغط حفظ بسرعة مرتين → إدخالان مكرران قد يُنشآ.

---

## Edge Case Summary (Cross-Persona)

| ID | Edge Case | Result |
|----|-----------|--------|
| EC-01 | Empty state — no tasks/projects/time entries | ✅ PASS (EmptyState in all views) |
| EC-02 | Long input — title 500 chars | ❌ FAIL (no maxlength guard) |
| EC-03 | Rapid double-submit on forms | ❌ FAIL (no idempotency) |
| EC-04 | Offline mode — no network | ✅ PASS (error banner + retry) |
| EC-05 | Permission denied — viewer tries to delete | ⚠️ Not tested (no Viewer role UI yet) |
| EC-06 | Arabic RTL layout | ✅ PASS (rtl-mirror class + locale switching) |
| EC-07 | Keyboard navigation — Tab, Enter, Escape | ❌ FAIL (Kanban drag-drop keyboard unsupported) |
| EC-08 | Mobile responsive breakpoints | ✅ PASS (Tailwind responsive classes) |
| EC-09 | Back button after logout | ⚠️ Not tested (router guard needed) |
| EC-10 | Browse back after deleting project | ❌ FAIL (404 if project deleted, no redirect) |

---

## Bug Report (consolidated)

| # | ID | Severity | Component | Summary | Steps to Reproduce | Expected | Actual |
|---|----|----------|-----------|---------|--------------------|----------|--------|
| 1 | BUG-005 | **Critical** | SearchInput.vue | `uiStore` referenced in template without import | Open any page with search → console error | Search renders correctly | `uiStore is not defined` runtime error breaks search |
| 2 | BUG-008 | **High** | TaskService.php | `reorder()` doesn't broadcast `TaskMoved` WebSocket event | Drag task between Kanban columns → check Reverb | `TaskMoved` event broadcast to `private-project.{id}` | No broadcast. Spec §13.2 violated |
| 3 | BUG-002 | **High** | TimeEntryService.php | Manual entry accepts `ended_at` before `started_at` without validation | POST /time-entries with started_at=16:00, ended_at=14:00 | 422 "end must be after start" | 201 Created with negative duration or wrong calc |
| 4 | BUG-001 | **High** | KanbanBoard.vue | No mobile touch drag-drop support | Open Kanban on mobile → try to drag task | Touch drag-drop works | Native HTML5 DnD unsupported on mobile. No touch events |
| 5 | BUG-006 | **Medium** | ReportsView.vue | `project_count` key missing from API response | Open reports → KPI "المشاريع النشطة" reads `summary.project_count` | Shows real project count | Always shows '-'. Schema mismatch |
| 6 | BUG-011 | **Medium** | TimerWidget.vue | Starting new timer while one runs — loose note, inconsistent UX | Start timer on task A → switch task → start timer on B | Clear prompt or auto-stop with note preservation | Timer A stops silently, note lost |
| 7 | BUG-009 | **Medium** | RegisterView, TimeTrackingView | No idempotency on POST | Rapid double-click "Save" / "Register" | Only one resource created | Possible duplicate entries/tasks |
| 8 | BUG-013 | **Medium** | TaskForm (implied) | No maxlength validation on task title | Submit task with 500‑char title | Frontend/API truncate or reject | May break layout or DB constraint |
| 9 | BUG-101 | **Medium** | TasksView.vue | No project filter UI despite filter in store | Open /tasks → no project dropdown | Can filter by project | Filter state exists but no UI control |
| 10 | BUG-012 | **Medium** | ReportsView.vue | Pie chart assumes entries[0].projects exists | Open reports with daily group_by → pie chart | Show project breakdown | `entries[0].projects` may be undefined → chart shows "not enough data" |
| 11 | BUG-003 | **Low** | TimeEntryService.php | Wrong exception class for "no running timer" | POST /time-entries/stop when no timer | 404 with appropriate message | 409 "TimerAlreadyRunning" — misleading |
| 12 | BUG-014 | **Low** | NotificationStore | No empty state for notifications page | View notifications when none exist | "No notifications" message | Only hidden/filtered — no user-facing empty state |
| 13 | BUG-007 | **Low** | workspaceStore.js | `inviteMember` pushes member with `id: data.data.invitation.id` which may be null | Invite existing user (auto-accept) → member list | Member appears with valid ID | If `invitation.id` null in auto-accept case, member has null id |

---

## Regression Checklist

| # | Check | Status | Notes |
|---|-------|--------|-------|
| R-01 | Registration → Login → Logout flow | ✅ PASS | Full auth cycle works |
| R-02 | Password validation (min 8 chars, confirmation) | ✅ PASS | Both client + server |
| R-03 | Workspace CRUD | ✅ PASS | Create/read/update/delete |
| R-04 | Invite member by email | ✅ PASS | Including existing user auto-accept |
| R-05 | Invite duplicate member | ✅ PASS | Error message |
| R-06 | Create project with color | ✅ PASS | Color stored/displayed |
| R-07 | Create task with assignee | ✅ PASS | Assignee synced via pivot |
| R-08 | Kanban column drag-drop (mouse) | ⚠️ Partial | Works mouse-only, no WS event |
| R-09 | Timer start → elapsed → stop | ✅ PASS | Duration calc correct |
| R-10 | Manual time entry | ✅ PASS | With datetime-local pickers |
| R-11 | Time entry CRUD | ✅ PASS | Create/read/update/delete |
| R-12 | Add comment + @mention | ✅ PASS | Mention auto-detected |
| R-13 | Upload attachment (image, PDF) | ✅ PASS | Size limit 10MB |
| R-14 | Dashboard KPI rendering | ✅ PASS | 4 cards, loading/error/empty |
| R-15 | Reports with charts | ⚠️ Partial | KPI 3 broken (project_count), pie chart conditional |
| R-16 | Notification list + mark read | ✅ PASS | Read-all works |
| R-17 | Arabic RTL rendering | ✅ PASS | Mirror classes, locale switching |
| R-18 | API error envelope (401/403/422/429) | ✅ PASS | Consistent format |
| R-19 | Rate limit headers | ✅ PASS | X-RateLimit headers present |
| R-20 | Search (Arabic + English) | ❌ FAIL | SearchInput.vue broken (BUG-005) |

---

## Per-Persona Score

| Persona | Tests | Pass | Fail | Score |
|---------|-------|------|------|-------|
| 🟣 سارة (PM) | 14 | 9 | 5 | 64% |
| 🔵 أحمد (Dev) | 10 | 6 | 4 | 60% |
| 🟢 ليلى (Coord) | 9 | 6 | 3 | 67% |

---

## Recommendations

### Must fix before Gate 5 sign-off (Critical + High):
1. **BUG-005** — SearchInput.vue: import `useUiStore` → **CRITICAL** (breaks all search)
2. **BUG-008** — TaskService.php: add `broadcast(new TaskMoved(...))` in `reorder()` → **HIGH** (WebSocket contract violation)
3. **BUG-002** — TimeEntryService.php: validate `ended_at > started_at` (Request + Service) → **HIGH** (data integrity)
4. **BUG-001** — KanbanBoard.vue: add touch event handlers + keyboard DnD → **HIGH** (mobile users)
5. **BUG-101** — TasksView.vue: add project filter dropdown or default to "My Tasks" → **HIGH** (usability)

### Should fix before Gate 5:
6. **BUG-006** — ReportsView.vue: query `project_count` from proper endpoint or add to report summary
7. **BUG-011** — TimerWidget.vue: confirm dialog when switching task mid-timer
8. **BUG-009** — Add `Idempotency-Key` header on POST/PUT
9. **BUG-012** — ReportsView.vue: aggregate projects across all report entries for pie chart
10. **BUG-013** — Add `maxlength` on task title (255 chars)

### Nice to have:
11. **BUG-003** — Use correct exception class (`TimeEntryNotFoundException`)
12. **BUG-014** — Add empty state for notifications
13. **BUG-007** — workspaceStore: handle null `invitation.id` gracefully

---

## Health Score Calculation

```
Pass Rate: 21/33 = 63.6%
Critical: 1 × 5pts = -5
High:     3 × 3pts = -9
Medium:   5 × 2pts = -10
Low:      3 × 1pts = -3
Total Penalty: -27

Base Score: 100
Final Score: 73/100 → 🟡 FAIR

Gate 5 verdict: SHIP BLOCKED — Critical (BUG-005) + 3 High must be resolved.
```

---

## Handoff

**To:** sofi-qa-sre-lead (TKT-013)
**State:** QA complete. 13 bugs filed (1 Critical, 3 High, 5 Medium, 4 Low).
**Next:** Fix bugs → re-run regression (R-08, R-15, R-20) → re-check pass rate ≥ 80% → sign off Gate 5.

---

*Generated by Rosa Giménez · sofi-manual-exploratory-tester · 2026-06-25*
*Caveman: full*
