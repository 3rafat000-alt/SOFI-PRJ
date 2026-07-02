# PRD: BudgetWave (SAAS-047)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة إدارة ميزانيات شخصية وعائلية — تتبع مصروفات، أهداف ادخار، تقسيم ذكي للميزانية.
- **Problem:** الأفراد والعائلات يجدون صعوبة في تتبع مصروفاتهم والالتزام بالميزانية. التطبيقات الحالية إما معقدة (YNAB) أو غير متخصصة (Excel).
- **Solution:** BudgetWave — تطبيق بسيط وذكي لتتبع الميزانية مع تصنيف تلقائي للمصروفات، أهداف ادخار، وتقارير أسبوعية.

## 2. Market & Opportunity
- **Target market:** سوق تطبيقات الميزانية الشخصية ~$1.5B (2025)، نمو 13% CAGR.
- **Customer segment:** B2C/B2B — أفراد، عائلات، فرق صغيرة، مستشارون ماليون.
- **Competitors:**
  - YNAB (You Need A Budget): ممتاز لكن منهجية صارمة وغالي ($14.99/شهر).
  - Mint (Intuit): مجاني لكن توقف، محدود عربياً.
  - Money Lover: بسيط وجيد لكن بدون ميزانيات ذكية.
  - Excel: مرن لكن بدون أتمتة ولا متابعة.
- **Differentiation:** واجهة عربية بسيطة، تصنيف تلقائي ذكي، تقسيم ميزانية تلقائي (قاعدة 50/30/20)، دعم للمشاركة العائلية.

## 3. User Personas

### الشخصية الأساسية: ليان — موظفة، 28 سنة
- **الدور:** تريد التحكم بمصروفاتها الشهرية والادخار لهدف معين
- **الأهداف:** معرفة أين تذهب أموالها، توفير 20% من الراتب
- **المشكلات:** تنسى تسجيل المصروفات، لا تعرف كيفية تقسيم الميزانية

### الشخصية الثانوية: علي وأمل — زوجان يديران ميزانية أسرية
- **الدور:** يديران ميزانية الأسرة (مصروفات البيت، الأطفال، الادخار)
- **الأهداف:** تتبع المصروفات المشتركة، تقسيم الميزانية، تحقيق أهداف ادخار للعائلة
- **المشكلات:** لا يوجد تطبيق يدعم المشاركة الأسرية، صعوبة تتبع الإنفاق المشترك

### Admin: مدير الفريق (B2B)
- يدير حسابات متعددة، تقارير الفريق، صلاحيات المستخدمين.

## 4. Features by Platform

### Laravel API (Backend)
- Models: Account, Transaction, Category, BudgetGoal, RecurringBill, Report
- Auto-categorization via keyword/merchant matching (ML)
- Budget calculation: income vs expenses, remaining, progress
- Recurring transaction detection and prediction
- Multi-currency support (exchange rate via external API)

### React Dashboard (Web)
- Dashboard: income/expense overview chart, remaining budget, savings rate
- Transaction list: search, filter, bulk categorize, split transaction
- Budget planner: set category limits, 50/30/20 auto-allocator
- Goals tracker: target amount, progress bar, deadline, auto-save suggestion
- Reports: monthly comparison, category breakdown, net worth trend
- Recurring bills manager: list, auto-categorize, reminder config

### Flutter App (Mobile)
- Quick transaction entry: amount → category → note (3 taps)
- Photo receipt scan (OCR) → auto-fill amount + merchant
- Home widget: daily spending, budget remaining
- Push notifications: overspending alert, bill reminder, savings milestone
- Shared account with family (real-time sync)
- Budget ring chart: visual spend per category

## 5. Data Model (MVP)
- **Account**: id, user_id, type (cash/bank/credit-card), name, balance, currency, is_shared
- **Category**: id, name_ar, name_en, icon, type (income/expense), budget_percentage, parent_id
- **Transaction**: id, account_id, category_id, amount, currency, description, merchant, date, is_recurring, receipt_url, notes
- **BudgetGoal**: id, user_id/account_id, name, target_amount, current_amount, deadline, auto_save_amount
- **RecurringBill**: id, user_id, name, amount, frequency (monthly/yearly/weekly), next_due, category_id, is_active
- **Report**: id, user_id, month, year, total_income, total_expense, category_breakdown_json, savings_rate

## 6. API Endpoints (MVP)
- `CRUD /api/accounts` — accounts management
- `CRUD /api/transactions` — with filters (date/category/account)
- `POST /api/transactions/bulk` — bulk import (CSV/manual)
- `POST /api/transactions/scan-receipt` — OCR receipt scan
- `CRUD /api/categories` — user custom categories
- `CRUD /api/budget-goals` — goals management
- `CRUD /api/recurring-bills` — bills management
- `GET /api/reports/monthly` — monthly report
- `GET /api/reports/annual` — annual overview
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): Summary cards (balance/income/expense/saved), spending chart (doughnut), recent transactions
- **Transactions** (React): List with search, filters, pull-to-refresh, bulk actions
- **Budget Planner** (React): Sliders per category, auto-calculate percentages, visual limits
- **Goals** (React): Progress cards, add goal form, auto-save suggestions
- **Bills** (React): Upcoming list, auto-pay toggle, reminder config
- **Reports** (React): Monthly comparison bar chart, category pie, net worth line
- **Settings** (React): Currency, categories, account linking, export data
- **Mobile** (Flutter): Swipeable screens — Dashboard → Transactions → Add (FAB) → Reports
- **Mobile Add Transaction**: Quick form with category picker, location, receipt photo

## 8. Business Model
- **Free**: 1 account, 50 transactions/month, 3 categories, basic dashboard
- **Plus**: $6/month — unlimited transactions, 5 accounts, budget planner, goals, reports
- **Family**: $12/month — shared accounts, 10 accounts, family goals, priority support
- **Pro (B2B)**: $24/month — unlimited accounts, team reports, CSV export, API
- **Free trial**: 30 days Plus
- **Target MRR/client**: $6–$24

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — Account/Transaction/Category/BudgetGoal models + CRUD
- **Phase 2 (Weeks 3-4)**: React Dashboard — full dashboard, transaction list, budget planner, reports
- **Phase 3 (Weeks 5-6)**: Flutter App — quick entry, receipt scan, charts, push notifications
- **Phase 4 (Weeks 7-8)**: Auto-categorization ML, recurring detection, shared accounts, testing

## 10. Risk & Mitigation
- **Technical**: OCR accuracy for Arabic receipts → Mitigation: Google Vision API (Arabic OCR support), manual fallback
- **Privacy**: Financial data sensitivity → Mitigation: AES-256 at rest, no external data sharing, SOC2 compliance path
- **Market**: User retention (people stop tracking) → Mitigation: gamification (streaks, milestones), weekly digest email, push reminders
