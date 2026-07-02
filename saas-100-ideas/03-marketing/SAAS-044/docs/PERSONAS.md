# PERSONAS — ChatFlow (SAAS-044)
> Owner: UX Researcher · Gate 1

## Primary Persona: نور — صاحبة متجر إلكتروني
- **Role:** مالكة متجر أزياء أونلاين (فريق 5 أشخاص، 200 طلب/يوم)
- **Context:** تدير متجراً على Shopify، تحتاج دعم عملاء فوري للاستفسارات المتكررة
- **Goals:** تقليل ضغط الأسئلة المتكررة، تحسين تجربة العميل، توفير وقت الفريق
- **Frustrations:** تقضي 4 ساعات يومياً بأسئلة متكررة (الأسعار، الشحن، المقاسات)، العميل يغادر دون إجابة
- **JTBD:** "When a customer asks about shipping, I want the bot to answer instantly so that my team can focus on complex issues."
- **Digital fluency:** Medium

## Secondary Persona: ياسر — مدير خدمة عملاء
- **Role:** مدير فريق دعم في شركة اتصالات (15 وكيلاً)
- **Context:** يشرف على فريق دعم متعدد الأقسام، يحتاج توجيه المحادثات للقسم المناسب
- **Goals:** توزيع عادل للمحادثات، تقارير رضا العملاء (CSAT)، تحسين سرعة الاستجابة
- **Frustrations:** لا توجد رؤية موحدة، صعوبة تحليل أداء الفريق، العملاء ينتظرون طويلاً
- **JTBD:** "When a chat comes in, I want it routed to the right agent automatically so that customers never wait more than 30 seconds."
- **Digital fluency:** High

## Pain/Gain Table
| Pain | Severity | Gain of Solving | Priority |
|------|----------|-----------------|----------|
| أسئلة متكررة تستهلك وقت الفريق | Critical | بوت يجيب على 80% تلقائياً | P0 |
| العملاء ينتظرون طويلاً | High | توجيه ذكي، تقليل وقت الانتظار 60% | P0 |
| لا توجد تحليلات رضا | High | CSAT تلقائي بعد كل محادثة | P1 |
| chatbot موجود ضعيف بالعربية | High | بوت عربي ذكي بقواعد + LLM | P1 |
| صعوبة دمج widget في الموقع | Medium | كود embed بنسخة واحدة | P2 |

## Competitor Comparison
| Competitor | Strengths | Weaknesses | Gap for Us |
|------------|-----------|------------|------------|
| Tidio | chatbot + live chat، سهل | $29/شهر، عربي محدود | سعر أقل، عربي كامل |
| Tawk.to | مجاني، مستخدمين كثر | بوت بسيط جداً | بوت ذكي + تحليلات |
| Intercom | ممتاز جداً | $74/شهر، معقد للصغار | سعر معقول + بساطة |
| Zendesk Chat | قوي، تكاملات | معقد، حزمة كاملة مطلوبة | أخف وأسرع وأرخص |
