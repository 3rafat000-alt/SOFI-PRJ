# JOURNEY MAP — ChatFlow (SAAS-044)
> Owner: Journey Architect · Gate 1 · Persona: نور (صاحبة متجر)

## Flow (Mermaid)
```mermaid
flowchart LR
    A[Customer Opens Widget\nفتح المحادثة] --> B[Bot Greeting\nترحيب آلي]
    B --> C[Ask Question\nيسأل سؤال]
    C --> D{Bot Knows?}
    D -->|Yes - Rule Match| E[Bot Answers\nرد آلي]
    D -->|Yes - LLM| F[LLM Generates\nرد ذكي]
    D -->|No| G[Route to Agent\nتحويل لوكيل]
    E --> H{Satisfied?}
    F --> H
    G --> I[Agent Responds\nالوكيل يرد]
    I --> H
    H -->|Yes| J[Close + CSAT\nإغلاق + تقييم]
    H -->|No| G
    C -.->|Timeout| K[Offline Form\nنموذج خارج الدوام]
    K -.-> L[Email Reply\nرد عبر البريد]
    L -.-> H
```

## Stage Annotations
| Stage | User Action | Goal | Emotion | Friction | Screen |
|-------|-------------|------|---------|----------|--------|
| Open Widget | ينقر أيقونة chat في الموقع | بدء محادثة | 😊 متحمس | widget يغطي محتوى الصفحة | Widget |
| Bot Greeting | يرى رسالة ترحيب | تواصل فوري | 😌 مرتاح | الترحيب طويل جداً | Widget |
| Ask Question | يكتب سؤاله | حل المشكلة | 🤔 مركز | البوت لا يفهم السؤال | Widget |
| Bot Answers | يحصل على رد سريع | توفير وقت | 😊 سعيد | الرد غير دقيق | Widget |
| Route to Agent | يُحوَّل لوكيل | مساعدة بشرية | 😤 محبط (انتظر) | وقت الانتظار طويل | Widget |
| CSAT | يقيم المحادثة | تحسين الخدمة | 😐 محايد | التقييم يظهر مبكراً | Widget |

## Ranked Friction Log
1. [High] البوت لا يفهم صياغة الأسئلة العربية المختلفة
2. [High] وقت انتظار الوكيل طويل (أكثر من دقيقتين)
3. [Med] widget يغطي محتوى مهم في الجوال
4. [Med] الوكيل لا يرى تاريخ العميل السابق
5. [Low] العميل يقيم قبل أن يحصل على حل كامل
