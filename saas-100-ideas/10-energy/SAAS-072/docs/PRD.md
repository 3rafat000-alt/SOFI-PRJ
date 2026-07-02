# PRD: RooftopSolar (SAAS-072)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة حساب تكلفة وفائدة تركيب الطاقة الشمسية للمنازل والشركات، تربط العملاء بمقدمي الخدمات المعتمدين
- **Problem:** صعوبة تقدير تكلفة الطاقة الشمسية للمنازل، عدم معرفة فترة الاسترداد، قلة الشفافية في عروض الموردين
- **Solution:** Laravel API + React Dashboard (solar companies) + Flutter App (homeowners/businesses)

## 2. Market & Opportunity
- **Target market:** $15B MENA residential solar market; 40% annual growth in rooftop solar installations
- **Customer segment:** B2B (solar installers, equipment suppliers) + B2C (homeowners, businesses)
- **Competitors:** Solar.com, EnergySage (US), Shams (KSA), YellowDoorEnergy (UAE), Sungevity
- **Differentiation:** Accurate local ROI calculator (location-specific tariffs, sun hours, net metering policies), automated installer matching, post-installation monitoring, financing integration

## 3. User Personas

### صاحب المنزل — خالد (Primary)
- **Role:** مالك منزل، فاتورة كهرباء مرتفعة شهرياً
- **Goals:** معرفة كم سيوفر بالطاقة الشمسية، تكلفة التركيب، فترة الاسترداد
- **Pain points:** أسعار غير واضحة، خوف من الصيانة المستقبلية، عدم الثقة بجودة الألواح

### شركة الطاقة الشمسية — منى (Secondary)
- **Role:** مديرة مبيعات في شركة تركيب أنظمة شمسية
- **Goals:** الوصول لعملاء جدد، تقديم عروض أسعار سريعة، إدارة المشاريع
- **Pain points:** تكلفة تسويق عالية، صعوبة تمييز عروضها عن المنافسين

### Admin — Dashboard Operator
- **Role:** مدير المنصة يراقب أداء الموردين، العمولات، جودة التركيبات

## 4. Features by Platform

### Laravel API (Backend)
- Solar calculator engine (location, consumption, tariff, roof size, orientation)
- Installer profiles, certifications, portfolio
- Quote request & bidding system
- Post-installation energy monitoring integration
- Payment & commission management
- Notifications (push/email/SMS)

### React Dashboard (Web)
- Solar ROI calculator with configurable parameters
- Installer management (certifications, insurance, reviews)
- Quote management (view, compare, award)
- Project tracking dashboard
- Financial reports (commissions, subscription revenue)
- Customer management & communication history

### Flutter App (Mobile)
- Roof analysis: input address, utility bill, roof photos
- Instant ROI report (savings/year, payback period, CO2 reduction)
- Browse & compare installers
- Request & compare quotes
- Track installation progress
- Monitor energy production (real-time dashboard)
- Push notifications for milestones

## 5. Data Model (MVP)
- **User:** id, name, email, phone, role, address, utility_company
- **Property:** id, user_id, address, roof_type, roof_area, orientation, monthly_bill
- **SolarQuote:** id, property_id, installer_id, system_size_kw, panel_count, total_cost, estimated_savings, payback_years, status
- **Installer:** id, user_id, company_name, license_number, service_area, rating, certifications (JSON)
- **Installation:** id, quote_id, status, start_date, completion_date, monitoring_token
- **Payment:** id, quote_id, amount, commission, status

## 6. API Endpoints (MVP)
- `POST /api/assessments` — Submit property for solar assessment
- `GET /api/assessments/{id}/roi` — Get ROI calculation
- `POST /api/quote-requests` — Request quotes from installers
- `GET /api/quotes` — List quotes for property
- `POST /api/quotes/{id}/accept` — Accept quote
- `GET /api/installers` — List installers (filter: area, rating)
- `POST /api/installations` — Create installation record
- `GET /api/installations/{id}/monitoring` — Energy production data
- `POST /api/installers/register` — Installer onboarding

## 7. User Interface (Screen List)
- **Dashboard screens:** Assessment management, Quote overview, Installer list, Reports, Settings
- **Mobile screens:** Home (new assessment), ROI report, Installer comparison, Quote detail, Installation tracker, Monitoring dashboard, Profile
- **Flow:** Home → Enter address/bill → ROI Result → Browse Installers → Request Quotes → Compare → Accept → Track Installation → Monitor Production

## 8. Business Model
- **Pricing:** Free for homeowners; Installers pay monthly subscription ($99/month base) + 5% commission on won projects
- **Free trial:** 14-day free for installers
- **Target MRR per installer:** $99–$499 (subscription + commission)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Solar calculator engine, User auth, Property CRUD, Installer profiles
- **Phase 2 (Weeks 3-4):** React Dashboard — ROI calculator widget, Quote management, Installer onboarding
- **Phase 3 (Weeks 5-6):** Flutter App — Assessment flow, Quote comparison, Monitoring dashboard
- **Phase 4 (Weeks 7-8):** Payment integration, Energy monitoring API connector, QA, Deploy

## 10. Risk & Mitigation
- **Data accuracy risk:** Solar savings estimates may be inaccurate → Partner with weather APIs, factor local degradation rates, include disclaimers
- **Policy risk:** Net metering policies change → Build parameterized calculator with admin-configurable tariff rates
- **Installer quality risk:** Poor installations damage reputation → Verify licenses, require insurance, review system
- **Seasonality:** Solar demand peaks in spring/summer → Off-season marketing campaigns, maintenance packages
