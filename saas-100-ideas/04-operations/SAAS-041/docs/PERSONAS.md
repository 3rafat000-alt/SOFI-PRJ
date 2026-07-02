# PERSONAS — AlertHub (SAAS-041)
> Owner: UX Researcher · Gate 1

## Primary Persona: م. أحمد — مهندس DevOps
- **Role:** مهندس أنظمة و DevOps في شركة تقنية (200 موظف)
- **Context:** يدير 50+ خادماً، يستخدم Grafana/Prometheus/Zabbix للمراقبة، PagerDuty للتنبيهات
- **Goals:** توحيد التنبيهات من مصادر متعددة، تقليل الضوضاء والإشعارات المكررة، ضمان وصول التنبيهات الحرجة فوراً
- **Frustrations:** كثرة الأدوات (PagerDuty + Slack + Email)، تنبيهات متكررة، صعوبة تتبع حالة التنبيه وإقراره
- **JTBD:** "When monitoring alerts fire, I want to acknowledge and escalate through one unified channel so that critical issues never get lost."
- **Digital fluency:** High

## Secondary Persona: سارة — مديرة فريق تقني
- **Role:** مديرة فريق DevOps (8 أشخاص)، تحتاج تقارير أداء وزمن استجابة
- **Context:** تشرف على عمليات التشغيل، تتابع مؤشرات الأداء (MTTA, MTTR)
- **Goals:** رؤية واضحة لوقت الاستجابة للتنبيهات، تحليل فجوات التغطية، تقارير أسبوعية
- **Frustrations:** لا توجد لوحة تحكم مركزية، صعوبة قياس أداء الفريق، عدم توفر تقارير جاهزة
- **JTBD:** "When reviewing team performance, I want to see delivery metrics across all channels so that I can improve response times."
- **Digital fluency:** High

## Pain/Gain Table
| Pain | Severity | Gain of Solving | Priority |
|------|----------|-----------------|----------|
| تنبيهات مكررة من مصادر مختلفة | Critical | تقليل الضوضاء 70%، تركيز على التنبيهات الحرجة | P0 |
| عدم وجود رؤية موحدة للحالة | High | لوحة تحكم واحدة، تتبع فوري | P0 |
| صعوبة تتبع إقرار التنبيهات | High | معرفة من أقَر ومتى، مساءلة الفريق | P1 |
| قنوات إشعارات غير موحدة | Medium | Push/SMS/Email من نظام واحد | P1 |
| عدم وجود تقارير أداء | Medium | تحسن MTTA/MTTR بنسبة 30% | P2 |

## Competitor Comparison
| Competitor | Strengths | Weaknesses | Gap for Us |
|------------|-----------|------------|------------|
| PagerDuty | تكامل واسع، تقارير قوية | سعر مرتفع ($21/شهر)، معقد | تسعير أقل 50%، تبسيط |
| Opsgenie | مدمج مع Atlassian، قوي | غالي، لا يدعم مزودين محليين | دعم SMS محلي، عربي |
| Twilio | مرونة عالية | بدون إدارة تنبيهات، مجرد قنوات | إدارة + تحليلات + قوالب |
| Slack | مجاني مع الفرق | محدود جداً في القنوات | 3 قنوات متكاملة |
