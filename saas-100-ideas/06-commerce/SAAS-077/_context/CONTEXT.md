# CONTEXT — SAAS-077 (durable facts; append-only)
- title: TobaccoShop
- sector: 06-commerce
- description: إدارة محلات الدخان والنرجيلة: مخزون، موردين، طلبات
- target_customers: محلات دخان، موزعون
- stack: (set at gate 3 by principal-system-architect)
- enriched_description: نظام إدارة متخصص لمحلات التبغ والنرجيلة. يشمل كتالوج منتجات شامل (سجائر، معسل، فحم، اكسسوارات) بتصنيف حسب الماركة والنوع، إدارة المخزون (مخزون وارد/صادر/منتهي/تالف)، إدارة الموردين وأسعارهم، أوامر شراء للموزعين، فواتير البيع مع حساب الضريبة الانتقائية والـVAT، التحقق من سن العميل، تقارير الامتثال التنظيمي وإدارة متعددة الفروع.
- target_market_details: أكثر من مليون محل دخان في الشرق الأوسط وشمال أفريقيا. القطاع غير رسمي إلى حد كبير لكنه يتحول للمدفوعات الرقمية والامتثال. الضرائب الانتقائية تفرض الحاجة لحسابات دقيقة. السوق الكبير للمعسل والفحم في المنطقة يخلق حاجة لإدارة مخزون معقدة.
- competitive_landscape: معظم محلات التبغ تستخدم Excel أو ورق أو أنظمة POS عامة (ECRS، Loyverse، ToastTab). لا يوجد منافس متخصص بهذا القطاع في المنطقة العربية. التميز في التخصص بالمجال، تكامل التحقق من العمر، تقارير ضريبية جاهزة، وإدارة موردين متقدمة.
- decisions:
  - PRD: docs/PRD.md — Completed Gate 0. Handoff to sofi-journey-architect for Gate 1 (Discovery).
> Append one bullet per durable fact.
