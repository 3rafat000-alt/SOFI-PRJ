# 🏛️ SAKK / صكك — المخطط المعماري الشامل (MASTER PLAN)

> **PHASE 00 — الشرح الشامل المدموج + خارطة 25 طور**
> معيار $10,000+ · تحصين أمني (دروس Lirat) · هوية العنابي الدمشقي · سرب وكلاء
> آخر تحديث: 2026-06-21 · المالك: Asaad 👑 (CEO) · التنفيذ: سرب SOFI

---

## 0. الحقيقة الأرضية (Ground Truth — تم التحقق منه، ليس افتراضاً)

| المحور | الواقع المُتحقَّق |
|--------|------------------|
| **النطاق** | مشروع `carda-wallet` القائم — **ليس** مشروعاً جديداً. backend (Laravel) + mobile (Flutter) + deploy + docs. **لا يوجد web frontend منفصل** — لوحة التحكم = Blade. |
| **Backend** | Laravel. 140 web route، 218 API route، 21 admin controller، 49 admin blade view، 6 middleware. |
| **الهوية البصرية (مصدر الحقيقة)** | `mobile/lib/core/theme/app_colors.dart` — **العنابي الدمشقي** `#6E1B2D` + ذهبي معتق `#B58A3C` + أبيض رخامي `#F7F3EE`. **Light-only** (لا dark mode). |
| **الخط** | IBM Plex Sans Arabic (5 أوزان) موجود في `mobile/assets/fonts/`. كان **غير مسجّل في pubspec** → الخط لا يُحمَّل. ✅ **أُصلح في هذا الطور** (fonts block أُضيف، `pub get` نجح). |
| **الوضع الأمني الحالي** | `APP_DEBUG=false` ✓ · لا `public/storage` symlink ✓ · لا `.git` مكشوف في public ✓ · `.env` موجود محلياً · `APP_ENV=local`. |
| **Middleware موجود** | `AdminMiddleware`، `BlockDangerousUploads`، `CheckUserType`، `EnsureDeviceCanTransact`، `InstallerGuard`، `InstallerMiddleware`. |
| **layouts موجودة** | `layouts/admin.blade.php` · `layouts/landing.blade.php` · مجلد `views/landing/`. |

### قرارات معمارية حاسمة (Decisions)

1. **الهوية تفوز على القالب.** مستند الـ$10k يقترح *dark mode + neon*. أمر المالك الصريح: **«هويتي البصرية التي في تطبيق Flutter»** = العنابي الفاتح + الذهبي. ⟹ نبني فخامة الـ$10k داخل العنابي الفاتح: glassmorphism على الرخام (frosted-on-marble)، ذهبي للـ accents، 3D scroll، بدون dark/neon. *(يتوافق مع ذاكرة `sofi-visual-identity` و `carda-wallet-admin-panel`.)*
2. **النطاق = رفع carda-wallet**، لا بناء SuperApp من الصفر. كل عمل يُسقَط على الكود القائم.
3. **الأمن قبل الميزة (Security-Before-Feature).** كل أطوار P0 الأمنية تُغلق قبل أي إعادة تصميم.
4. **المصدر الواحد للهوية:** ألوان Flutter → `sakk-tokens.css` (CSS variables) → كل الويب/الـBlade تستهلكه. لا تكرار قيم لونية.

---

## 1. تشريح المنافسين (Competitive Deconstruction)

### العمالقة الأتراك
| المنصة | قوة | ضعف | كيف نتفوّق |
|--------|-----|-----|-----------|
| Papara | Gamification، إدارة بطاقات مرنة، dark احترافي | ازدحام الشاشة | 3D Scroll Cards بدل القوائم؛ تنفّس بصري على الرخام |
| Ozan | تعدّد عملات، SuperCard، فتح سريع | onboarding معقّد | Smart Tabs + 3D transition بين العملات |
| Ininal | انتشار فيزيائي، شحن نقدي | واجهة ضعيفة | glassmorphism + فخامة فورية |
| Oldubil | بساطة | نقص ميزات | easter eggs + micro-interactions |
| Param | POS، white-label، B2B | جاف للتجار | 3D data-viz dashboard |

### الإقليمي + المتخصص (المرجعيات)
KazaWallet (3D currency slider) · **Lirat.store** (مرجع *سلبي* — 34 ثغرة، اختُرق؛ كل درس منها = TODO أمني) · Carda.app (Card-Flip 3D لإظهار CVV) · Revolut/Monzo/N26/Wise/Curve (المعيار الذهبي للـUI المالي) · Telda/STC Pay/Pyypl/Urpay (MENA) · RedotPay/Wallester/Lithic (إصدار بطاقات — مرجع لوحات التحكم).

### مرجعيات الـ$10k (للتطبيق العملي)
Awwwards (WebGL/3D) · Refactoring UI (تكتيكات الواجهة) · Mobbin (شاشات بنكية حقيقية) · Baymard (تدفقات الدفع) · Laws of UX (Fitts/Hick) · NN/g (أبحاث UX). التقنيات: GSAP + ScrollTrigger، Three.js / R3F، Framer Motion، Spline.

---

## 2. الدوكترين الأمني — 34 درساً من دم Lirat → ضوابط

| # | درس Lirat | الضابط المضاد | حالة carda-wallet (recon) |
|---|-----------|----------------|----------------------------|
| 1 | Path Traversal من public/storage → تسريب .env | فصل storage، منع `%2f`، nginx `root` لا `alias` | لا symlink حالياً ✓ — يُدقَّق في الأطوار |
| 2 | APP_KEY قديم شغال بعد التحديث | سياسة: تغيير APP_KEY ⟹ restart PHP-FPM + workers | يُوثَّق في deploy |
| 3 | Firebase SDK private key في storage/app | نقل لـ Vault/Secrets Manager | يُفحَص (Squad Secrets) |
| 4 | Middleware: CheckUserType قبل HMAC | إعادة ترتيب: auth → authorization → business | `CheckUserType` موجود — **يُدقَّق ترتيبه** |
| 5 | User Enumeration (رسائل خطأ مختلفة) | رسائل عامة موحّدة | يُفحَص login/forgot |
| 6 | Swagger في production | تعطيل `L5_SWAGGER_ENABLED` | يُفحَص |
| 7 | CORS Wildcard `*` | قائمة origins محدّدة | يُفحَص `config/cors.php` |
| 8 | `.git/` في web root | إزالة + deny | غير مكشوف ✓ |
| 9 | Guzzle SSRF (CVE-2024-32980) | `composer audit` + ترقية | يُفحَص lock |
| 10 | SVG Upload → XSS | رفض SVG أو `Content-Disposition: attachment` | `BlockDangerousUploads` موجود — **يُدقَّق تغطيته** |

→ التفصيل الكامل (50 ضابط بنية تحتية + أسرار) في الأطوار 01–04.

---

## 3. نظام التصميم $10k — هوية العنابي (Design System)

### الـ Tokens (مصدر واحد → CSS + Blade + Flutter متطابقة)
```
--sakk-primary:        #6E1B2D   /* العنابي الدمشقي */
--sakk-primary-dark:   #4A1320
--sakk-primary-light:  #F7E9EC
--sakk-secondary:      #8E2A3D
--sakk-accent:         #B58A3C   /* ذهبي معتق */
--sakk-bg:             #F7F3EE   /* أبيض رخامي */
--sakk-surface:        #FFFFFF
--sakk-text:           #2A1A1F
--sakk-text-2:         #6E5F63
--sakk-success:#1F9D55  --sakk-warning:#B58A3C  --sakk-error:#C0392B
/* gradients */ visa:#7A2236→#4A1320 · gold:#C9A24B→#8F6B2A · platinum:#8A7E74→#5C534C
--sakk-font: "IBM Plex Sans Arabic", system-ui, sans-serif;  /* tnum للأرقام */
```
### مبادئ
- **Glassmorphism على الرخام**: بطاقات frosted (`backdrop-filter: blur`) بحدود ذهبية رفيعة، ظلال دافئة.
- **3D Scroll**: GSAP ScrollTrigger — بطاقة افتراضية تدور مع التمرير، Card-Flip لإظهار CVV، parallax hero.
- **Tabular Nums** لكل الأرقام المالية. **RTL-first** (عربي). **Light-only** يطابق Flutter.
- **A11y**: تباين AA على العنابي/الذهبي، focus-visible، prefers-reduced-motion يوقف 3D.
- **Performance**: lazy-load لأصول 3D، degrade على الموبايل، CSS containment.

---

## 4. هندسة السرب (Swarm) — قانون ≥20 وكيل/دفعة

```
P0 Critical → اختراق كامل (Path Traversal, Secret Leak, RCE)
P1 High     → اختراق جزئي (Data Leak, Auth Bypass, IDOR)
P2 Medium   → تحسين أمني/UX
P3 Low      → تحسينات عامة
```
**الدفعات بالترتيب الصارم:** المحلّلون/المدقّقون → المخترِقون → المصمّمون → البنّاؤون → المختبِرون → الأمن → المستشارون الخمسة → loop. **كل دفعة ≥20 وكيل، مهام دقيقة (≤3 دقائق/مهمة)، فحص جذر عند أي فشل (Zero-Tolerance).**

---

## 5. خارطة الـ25 طوراً (THE 25-PHASE ROADMAP)

> كل طور: **دفعة سرب ≥20 وكيل** · مخرج ملموس · بوابة Loop-Master (لا يبدأ التالي قبل PASS).

| Phase | الاسم | الدفعة (وكلاء) | الأولوية | المخرج / البوابة |
|-------|-------|----------------|----------|-------------------|
| **00** | الدمج + الشرح الشامل (هذا المستند) | CEO + recon | — | ✅ MASTER_PLAN.md + إصلاح الخط |
| **01** | تدقيق Backend شامل (routes/controllers/models/migrations/API) | 20 مدقّق | P0 | تقرير خريطة + أخطاء + روابط مكسورة |
| **02** | تدقيق الأمن — OWASP Top 10 على الكود | 20 مخترِق | P0 | تقرير ثغرات مُصنّف P0–P3 |
| **03** | تدقيق الأسرار + Supply Chain (.env، composer/npm audit، git history) | 20 مدقّق أسرار | P0 | جرد أسرار + CVEs |
| **04** | تحصين البنية التحتية (nginx/deploy/headers/rate-limit/WAF/CORS) | 20 devops | P0 | deploy hardened + checklist |
| **05** | إصلاح ثغرات P0 (من 02/03) | 20 بنّاء أمن | P0 | كل P0 مُغلق + إعادة فحص |
| **06** | Auth & Authorization (ترتيب middleware، enumeration، IDOR، 2FA) | 20 | P0/P1 | إصلاح + اختبارات auth |
| **07** | SQLi & Path Traversal sweep (whereRaw/DB::raw، file serve) | 20 | P0 | صفر injection + اختبارات |
| **08** | XSS & File Upload (SVG، MIME، `{!! !!}`، CSP) | 20 | P0 | رفض ملفات خطرة + CSP |
| **09** | منطق الأعمال المالي (atomic transfers، deadlock، negative، KYC gate) | 20 | P1 | معاملات ذرّية مُختبَرة |
| **10** | API & Rate Limiting (throttle لكل endpoint حسّاس، أحجام payload) | 20 | P1 | rate-limit مُختبَر شامل |
| **11** | التشفير at-rest + سياسة المفاتيح + تدقيق logs | 20 | P1 | PII/مالي مشفّر |
| **12** | المراقبة + الإنذار + Incident Response playbook | 20 | P1 | تنبيهات + لوحة |
| **13** | Design System — sakk-tokens.css + مكتبة glassmorphism | 20 مصمّم | P2 | tokens + مكوّنات أساس |
| **14** | إصلاح الخط على الويب (تضمين IBM Plex Sans Arabic في Blade/CSS) | 20 | P2 | خط موحّد ويب+موبايل |
| **15** | إعادة تصميم لوحة التحكم — الصفحات الأساسية (dashboard/users/cards/tx) | 20 بنّاء UI | P2 | شاشات بهوية العنابي |
| **16** | إعادة تصميم لوحة التحكم — باقي الصفحات (49 view كلها) | 20 | P2 | كل اللوحة موحّدة |
| **17** | 3D + Animation (ScrollTrigger، Card-Flip 3D، parallax، micro-interactions) | 20 | P2 | تفاعلات 3D سلسة |
| **18** | صفحة الهبوط (Landing) — hero 3D، features، pricing، CTA | 20 | P2 | landing بمعيار $10k |
| **19** | تطابق هوية Flutter (مزامنة ألوان/خط/radius mobile↔web) | 20 | P2 | تطابق بصري كامل |
| **20** | E2E — محاكاة العامل البشري (ملء/نقر/تمرير، سيناريو كل صفحة) | 20 QA | P1 | تقارير E2E لكل صفحة |
| **21** | اختبار الاختراق E2E (path traversal، SQLi، XSS، IDOR، CSRF، SSRF، brute) | 20 مخترِق | P0/P1 | كل الهجمات تُرفَض |
| **22** | الأداء (N+1، caching، slow queries، bundle، 3D degrade) | 20 | P2 | profiling + إصلاح |
| **23** | الوثائق (README، CHANGELOG، OpenAPI، C4، deploy guide) | 20 | P2 | docs كاملة |
| **24** | مراجعة الكود + المستشارون الخمسة (architecture/security/perf/UX/ethics) | 20 + Council | P0 | score ≥8/10 لكل محور |
| **25** | Launch Checklist + بوابة go-live النهائية | Loop-Master | P0 | كل البنود TRUE |

### بوابة go-live (مختصر)
`كل الأطوار PASS · صفر P0/P1 مفتوح · E2E PASS · composer/npm audit صفر CVE · APP_DEBUG=false · Swagger off · CORS مقيّد · لا symlink storage · rate-limit مُختبَر · WAF فعّال · security headers · backup مشفّر مُختبَر`

---

## 6. بروتوكول التنفيذ (Execution Protocol)

- **Zero-Tolerance Loop:** فشل وكيل ⟹ تجميد الدفعة ⟹ تحليل الجذر (logs/console/trace) ⟹ إعادة الإصلاح ⟹ استئناف. **ممنوع** try-catch صامت، **ممنوع** تجاهل تحذير console، **ممنوع** تخطّي فشل أمني.
- **Micro-Tasking:** كل مهمة = دالة/مكوّن/اختبار واحد، ≤3 دقائق.
- **Flexible Guidance:** اكتشاف أداء/أمن ⟹ يُرفَع للـLoop-Master فوراً. المستشارون: Sprint Review كل 10 مهام.
- **الأدلة:** كل اكتشاف بـ `file:line` أو مصدر؛ لا ادّعاء بلا دليل.

---

## 7. مُنجَز في PHASE 00 (هذا الطور)
- ✅ تشخيص + إصلاح ثغرة الخط (pubspec fonts block، `pub get` نجح).
- ✅ recon أرضي كامل (بنية، هوية، أمن، routes).
- ✅ هذا المستند (الدمج الشامل + 25 طوراً).
- ▶️ التالي مباشرةً: إطلاق **دفعة المدقّقين (Phase 01، ≥20 وكيل)**.
