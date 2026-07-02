# PRD: ExamPro (SAAS-076)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة اختبارات إلكترونية مع تصحيح آلي، نتائج فورية، تقارير تحليلية متقدمة للجامعات والمدارس
- **Problem:** تصحيح الاختبارات الورقية يستغرق أياماً، أخطاء بشرية في التصحيح، تحليل نتائج محدود، صعوبة إدارة بنوك أسئلة مركزية
- **Solution:** Laravel API + React Dashboard (instructors, admins) + Flutter App (students)

## 2. Market & Opportunity
- **Target market:** 100M+ students in MENA; 50K+ educational institutions; E-exam market growing 20% CAGR post-COVID
- **Customer segment:** B2B (universities, schools, training centers, certification bodies)
- **Competitors:** ExamSoft (US), ProProfs, EasyLMS (Egypt), Classera (MENA), Google Forms
- **Differentiation:** Arabic question bank with RTL support, AI anti-cheating (face detection + browser lockdown), offline exam mode for low-connectivity areas, granular analytics per topic

## 3. User Personas

### الدكتور — أستاذ أحمد (Primary)
- **Role:** أستاذ جامعي في كلية الهندسة
- **Goals:** إنشاء اختبارات إلكترونية بسرعة، تصحيح تلقائي، تحليل أداء الطلاب لكل سؤال
- **Pain points:** تصحيح 200 ورقة يدوياً، عدم معرفة نقاط ضعف الطلاب، صعوبة مشاركة الأسئلة مع زملائه

### الطالبة — مريم (Secondary)
- **Role:** طالبة جامعية في السنة الثالثة
- **Goals:** أداء الاختبارات بسلاسة، رؤية النتيجة فوراً، مراجعة الإجابات الصحيحة
- **Pain points:** قلق الامتحانات، ضعف الإنترنت في منطقتها، عدم وضوح آلية التصحيح

### مشرف الاختبارات — نايف (Tertiary)
- **Role:** مدير مركز الاختبارات في الجامعة
- **Goals:** جدولة الاختبارات، مراقبة الأداء، ضمان النزاهة الأكاديمية
- **Pain points:** تزوير النتائج، صعوبة إعادة الاختبارات للطلاب المتغيبين

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب المؤسسات، خطط الاشتراك، الدعم الفني

## 4. Features by Platform

### Laravel API (Backend)
- Question bank (MCQ, essay, true/false, matching, fill-blank)
- Exam builder (randomization, sections, time limits, grading rubrics)
- Auto-grading engine (MCQ instant, essay with AI-assisted rubric)
- Proctoring module (face recognition, browser lock, screen recording)
- Result management (scores, percentiles, grade distribution)
- Analytics engine (per-question difficulty, discrimination index, student performance trends)
- Offline exam sync (for low-connectivity areas)

### React Dashboard (Web)
- Exam creation wizard (drag-drop question builder)
- Question bank management (tags, difficulty, topics)
- Exam scheduling & assignment
- Live proctoring dashboard (flagged students)
- Grading queue (essay review + AI suggestions)
- Analytics dashboard (pass rates, question analysis, cohort comparison)
- Gradebook export (PDF/CSV/Excel)
- Institution & user management

### Flutter App (Mobile) — Student App
- Upcoming exams list
- Take exam (timer, progress bar, question navigator)
- Auto-submit on time expiration
- Instant results display
- Review answers (correct/incorrect with explanations)
- Exam history & performance trends
- Offline exam mode (download, take, sync when online)
- Push notifications (exam reminders, results ready)

## 5. Data Model (MVP)
- **Institution:** id, name, type (university/school/training), subscription_tier, admin_id
- **User:** id, institution_id, name, email, role (admin/instructor/student), student_id ref
- **Course:** id, institution_id, name, code, instructor_id, semester
- **Question:** id, course_id, type, body (JSON for rich text/images), options (JSON for MCQ), correct_answer, marks, difficulty, tags
- **Exam:** id, course_id, title, instructions, duration, total_marks, start_date, end_date, shuffle_questions, proctoring_enabled, status
- **ExamQuestion:** id, exam_id, question_id, order_index
- **Attempt:** id, exam_id, student_id, started_at, submitted_at, score, status (in_progress/submitted/graded)
- **Answer:** id, attempt_id, question_id, given_answer, marks_awarded, is_correct, graded_by (auto/instructor)
- **ProctoringLog:** id, attempt_id, event_type, timestamp, severity, screenshot_url

## 6. API Endpoints (MVP)
- `POST /api/exams` — Create exam
- `POST /api/questions` — Add question (batch upload supported)
- `GET /api/exams/{id}/assign` — Assign exam to students
- `POST /api/exams/{id}/start` — Start exam attempt
- `POST /api/exams/{id}/submit` — Submit exam
- `POST /api/answers` — Save answer (per question, auto-save)
- `GET /api/exams/{id}/results` — Exam results (instructor)
- `GET /api/student/results` — My results (student)
- `GET /api/exams/{id}/analytics` — Question analytics
- `POST /api/proctoring/log` — Log proctoring event
- `POST /api/exams/{id}/grade-essay` — Grade essay question
- `GET /api/reports/institution` — Institution grade report

## 7. User Interface (Screen List)
- **Dashboard screens:** Exam list, Question bank, Proctoring monitor, Grading queue, Analytics, Institution settings
- **Mobile (Student):** Home (upcoming exams), Take exam, Results history, Performance graph, Profile
- **Flow (Student):** Login → Upcoming Exams → Start Exam → Answer Questions → Submit → View Result → Review
- **Flow (Instructor):** Login → Create Exam → Add Questions → Assign → Monitor → Grade → View Analytics

## 8. Business Model
- **Pricing:** Per-institution tier: Basic ($199/mo, 500 students, 50 exams/mo), Pro ($499/mo, 2K students, unlimited), Enterprise ($1,499/mo, 10K+ students, AI proctoring)
- **Free trial:** 30-day free (100 students, 10 exams)
- **Target MRR per institution:** $199–$1,499
- **Add-ons:** AI proctoring ($199/mo), Advanced analytics ($99/mo), White-label ($299/mo)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Question bank CRUD, Exam builder, Auto-grading (MCQ), User/institution management
- **Phase 2 (Weeks 3-4):** React Dashboard — Exam wizard, Question management, Grading interface, Basic analytics
- **Phase 3 (Weeks 5-6):** Flutter App — Exam taker (with auto-save, timer), Results display, Offline mode
- **Phase 4 (Weeks 7-8):** AI-assisted essay grading, Proctoring module, Advanced analytics, QA, Load testing

## 10. Risk & Mitigation
- **Cheating risk:** Students share answers during exam → AI proctoring, browser lockdown, question randomization, time limits
- **Technical risk:** Server crash during high-stakes exam → Auto-scaling, exam pause/resume, offline fallback
- **Adoption risk:** Faculty reluctant to change → Training workshops, import from existing LMS, hybrid paper+digital option
- **Equity risk:** Students with poor internet → Offline exam mode, lightweight app, extended time options for connectivity issues
