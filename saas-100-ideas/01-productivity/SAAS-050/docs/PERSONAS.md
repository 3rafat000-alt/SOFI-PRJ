# PERSONAS — DevSync (SAAS-050)
> Owner: UX Researcher · Gate 1

## Primary Persona: يوسف — قائد فريق برمجة (Tech Lead)
- **Role:** Tech Lead في شركة ناشئة (فريق 8 مطورين)
- **Context:** يدير فريقاً يعمل على 3 مشاريع متزامنة، يستخدمون Jira + GitHub + Slack حالياً
- **Goals:** تتبع سرعة السبرنت، ضمان جودة الـ code reviews، إدارة المهام في مكان واحد
- **Frustrations:** Jira بطيء ومعقد، GitHub PRs منفصلة عن المهام (التشتت بين 3 أدوات يقضي 30% من الوقت في الـ context switching)
- **JTBD:** "When planning a sprint, I want to see all tasks, PRs, and code reviews in one board so that I never lose context switching between tools."
- **Digital fluency:** High

## Secondary Persona: لينا — مطورة برمجيات
- **Role:** مطورة Full-Stack في فريق 6 أشخاص
- **Context:** تكتب كوداً يومياً، ترفع PRs، تحل bugs، تشارك في sprint planning
- **Goals:** التركيز على البرمجة بدون تغيير سياق بين الأدوات، رؤية PRs المرتبطة بالمهام
- **Frustrations:** تضيع بين Jira (مهام) + GitHub (PRs) + Slack (تواصل)، كل أسبوع تضيع 8 ساعات في التبديل بينهم
- **JTBD:** "When I finish coding a feature, I want to create a PR linked to the ticket without leaving DevSync so that I stay in flow state."
- **Digital fluency:** High

## Pain/Gain Table
| Pain | Severity | Gain of Solving | Priority |
|------|----------|-----------------|----------|
| التشتت بين Jira + GitHub + Slack | Critical | منصة واحدة موحدة | P0 |
| مراجعة الكود منفصلة عن المهمة | High | Code review مدمج مع diff view | P0 |
| Jira بطيء ومعقد للإدارة اليومية | High | واجهة سريعة وبسيطة | P1 |
| صعوبة تتبع سرعة الفريق (velocity) | Medium | تحليلات سبرنت + بورن داون | P1 |
| الـ bugs تتوه بين الأدوات | Medium | تتبع bugs مع severity + ربط PR | P2 |

## Competitor Comparison
| Competitor | Strengths | Weaknesses | Gap for Us |
|------------|-----------|------------|------------|
| Jira (Atlassian) | المعيار، تكاملات واسعة | $10/مستخدم، بطيء، معقد | أخف 10x، أرخص، مدمج مع Git |
| Linear | سريع، واجهة جميلة | $16/مستخدم، لا code review | Code review مدمج، سعر أقل |
| GitHub Projects | مجاني مع GitHub | محدود، لا Sprints حقيقية | Sprints + backlog + velocity |
| ClickUp | شامل | عام جداً، ليس للمبرمجين | مخصص للبرمجة، Git مدمج |
