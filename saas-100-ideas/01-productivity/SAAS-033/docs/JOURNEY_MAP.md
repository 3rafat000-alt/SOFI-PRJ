# JOURNEY MAP — SupportDesk (SAAS-033)
> Owner: Journey Architect · Gate 1 · Persona: نور (مديرة دعم)

## Flow (Mermaid)
```mermaid
flowchart LR
  trigger["📩 عميل يرسل شكوى"] --> intake["استقبال التذكرة"]
  intake --> classify["تصنيف + تحديد أولوية"]
  classify --> auto_assign["توزيع تلقائي"]
  auto_assign --> agent_work["وكيل يعمل على التذكرة"]
  agent_work --> resolve["حل المشكلة"]
  resolve --> customer_review["تقييم العميل"]
  customer_review --> close["إغلاق"]
  close --> goal["👍 عميل راضٍ"]
  intake -.-> spam["⚠️ بريد مزعج"]
  spam --> mark_spam["وضع في spam"]
  mark_spam --> close
  agent_work -.-> need_help["❌ يحتاج مساعدة"]
  need_help --> escalate["ترقية L2/L3"]
  escalate --> agent_work
  customer_review -.-> bad_review["😡 تقييم سيء"]
  bad_review --> reopen["إعادة فتح التذكرة"]
  reopen --> agent_work
  agent_work -.-> no_response["⏳ عميل لا يرد"]
  no_response --> auto_close["إغلاق تلقائي"]
  auto_close --> goal
```

## Stage Annotations
| Stage | User Action | Goal | Emotion | Friction | Screen |
|-------|-------------|------|---------|----------|--------|
| Trigger | عميل يرسل شكوى | حل المشكلة | 😠 غاضب | — | — |
| Intake | النظام يستقبل التذكرة | تسجيل فوري | 😐 محايد | — | Ticket Queue |
| Classify | تصنيف تلقائي حسب الموضوع | توجيه صحيح | 🤔 قلق | تصنيف خاطئ أحياناً | Auto-classify |
| Auto-assign | توزيع على الوكيل المناسب | توزيع عادل | 😊 راضٍ | — | Assignment Logic |
| Agent Work | وكيل يحقق ويحل | حل المشكلة | 😐 مركز | معلومات ناقصة من العميل | Ticket Detail |
| Resolve | يرسل الحل للعميل | إغلاق التذكرة | 🙂 راضٍ | — | Resolve Flow |
| Review | عميل يقيم الحل | تقييم التجربة | 😐 عادي | — | CSAT Form |
| Close | إغلاق وإحصاءات | توثيق | 😊 راضٍ | — | Ticket Archive |

## Ranked Friction Log
1. **[High]** التصنيف والتوزيع اليدوي يستغرق وقتاً — يحتاج توزيع ذكي تلقائي
2. **[High]** الوكيل يبحث عن حلول للتذاكر المتكررة — يحتاج قاعدة معرفة + ردود جاهزة
3. **[Med]** صعوبة متابعة أداء الفريق — يحتاج لوحة أداء آنية
4. **[Med]** العميل لا يرد على استفسارات المتابعة — يحتاج إشعارات تلقائية
5. **[Low]** التقارير الأسبوعية تستغرق وقتاً — يحتاج تقارير آلية

**Rule:** Every later feature MUST trace to a stage above.
