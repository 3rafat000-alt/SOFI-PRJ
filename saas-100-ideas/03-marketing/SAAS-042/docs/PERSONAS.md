# PERSONAS — PollPro (SAAS-042)
> Owner: UX Researcher · Gate 1

## Primary Persona: ليلى — منسقة مؤتمرات
- **Role:** منسقة فعاليات ومؤتمرات في شركة تنظيم مؤتمرات
- **Context:** تنظم 10-20 فعالية سنوياً، تحتاج أدوات تفاعل سريعة للحضور (500-2000 شخص)
- **Goals:** إنشاء تصويتات خلال دقائق، عرض النتائج على الشاشة الكبيرة، إشراك الجمهور
- **Frustrations:** Mentimeter غالي ($25/شهر)، Slido معقد، Google Forms بدون نتائج حية
- **JTBD:** "When running a conference session, I want to launch a live poll in under 30 seconds so that the audience stays engaged."
- **Digital fluency:** Medium

## Secondary Persona: خالد — معلم مدرسة
- **Role:** معلم رياضيات في مدرسة ثانوية
- **Context:** 30 طالباً في الفصل، يستخدم جهاز عرض (projector)، الطلاب لديهم جوالات
- **Goals:** اختبار فهم الطلاب أثناء الحصة، نتائج فورية، تقارير أداء
- **Frustrations:** لا يوجد حل مجاني مناسب، الطلاب لا يمتلكون أجهزة كمبيوتر (جوالات فقط)
- **JTBD:** "When checking student understanding, I want a quick anonymous poll so that I can assess the whole class instantly."
- **Digital fluency:** Medium

## Pain/Gain Table
| Pain | Severity | Gain of Solving | Priority |
|------|----------|-----------------|----------|
| الأدوات الحالية غالية ($25-50/شهر) | High | توفير 80% من التكلفة | P0 |
| لا تدعم العربية | High | واجهة ونماذج عربية كاملة | P0 |
| معقدة وتحتاج تدريب | Medium | إنشاء تصويت ب 3 نقرات | P1 |
| بدون QR للدخول السريع | Medium | انضمام فوري بمسح QR | P1 |
| نتائج غير حية | Low | تحديث فوري عبر WebSocket | P2 |

## Competitor Comparison
| Competitor | Strengths | Weaknesses | Gap for Us |
|------------|-----------|------------|------------|
| Mentimeter | تفاعلات متعددة، قوالب جميلة | $25/شهر، لا يدعم العربية جيداً | سعر أقل 70%، عربي |
| Slido | تكامل مع Webex/Teams | معقد، باهظ ($30/شهر) | أبسط بكثير، QR فوري |
| Google Forms | مجاني، سهل | بدون نتائج حية، بدون QR | حية + QR + تفاعل |
| Poll Everywhere | SMS مدعوم | باهظ ($34/شهر) | أرخص، واجهة عربية |
