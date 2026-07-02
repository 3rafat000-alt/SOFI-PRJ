# JOURNEY MAP — BookingPro (SAAS-034)
> Owner: Journey Architect · Gate 1 · Persona: منى (مدربة)

## Flow (Mermaid)
```mermaid
flowchart LR
  trigger["📋 مدربة تضيف أوقات متاحة"] --> set_avail["تحديد الأوقات"]
  set_avail --> client_browse["عميل يتصفح الخدمات"]
  client_browse --> select_service["اختيار الخدمة"]
  select_service --> pick_time["اختيار الوقت"]
  pick_time --> book["تأكيد الحجز"]
  book --> pay["دفع إلكتروني"]
  pay --> confirm["تأكيد + إشعار"]
  confirm --> session["الجلسة"]
  session --> complete["إكمال"]
  complete --> review["تقييم"]
  review --> goal["⭐ رضا متبادل"]
  pick_time -.-> slot_taken["⏰ الوقت محجوز"]
  slot_taken --> pick_time
  book -.-> cancel["❌ إلغاء"]
  cancel --> refund["استرداد حسب السياسة"]
  refund --> goal
  pay -.-> payment_fail["💳 فشل الدفع"]
  payment_fail --> retry_pay["إعادة المحاولة"]
  retry_pay --> pay
  session -.-> no_show["🚫 عدم حضور"]
  no_show --> charge_fee["خصم رسوم"]
  charge_fee --> goal
```

## Stage Annotations
| Stage | User Action | Goal | Emotion | Friction | Screen |
|-------|-------------|------|---------|----------|--------|
| Trigger | منى تحدد أوقاتها | فتح الحجز | 😐 محايد | إعداد التقويم أول مرة | Availability Settings |
| Browse | عميل يتصفح المدربات | إيجاد الخدمة | 😊 متفائل | كثرة الخيارات | Provider List |
| Select | يختار الخدمة المناسبة | تحديد الجلسة | 🙂 مصمم | — | Service Detail |
| Pick Time | يختار وقتاً متاحاً | حجز الوقت | 😐 قلق | الوقت قد يكون محجوزاً | Calendar Picker |
| Book | يؤكد الحجز | تأمين الموعد | 🙂 سريع | — | Booking Review |
| Pay | يدفع عبر Stripe/Tap | تأكيد الدفع | 😟 متوتر | فشل الدفع أحياناً | Payment |
| Confirm | يتلقى الإشعار | راحة البال | 😊 راضٍ | — | Confirmation |
| Session | الحضور للجلسة | الاستفادة | 😃 إيجابي | — | — |
| Review | تقييم التجربة | مشاركة رأيه | 🙂 متفاعل | — | Review Form |

## Ranked Friction Log
1. **[High]** جدولة المواعيد يدوياً عبر المراسلة — تقويم آني مع فتح أوقات
2. **[High]** تأخر الدفع — دفع مسبق إجباري
3. **[Med]** إلغاء اللحظة الأخيرة — سياسة إلغاء مع رسوم
4. **[Med]** تذكير العملاء — إشعارات تلقائية قبل 24 ساعة وساعة
5. **[Low]** العميل يحجز نفس الوقت مع اثنين — منع تعارض الحجوزات

**Rule:** Every later feature MUST trace to a stage above.
