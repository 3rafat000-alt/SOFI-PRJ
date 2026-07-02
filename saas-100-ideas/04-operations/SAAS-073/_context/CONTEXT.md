# CONTEXT — SAAS-073 (durable facts; append-only)
- title: WaterMgt
- sector: 04-operations
- description: إدارة شبكات المياه: تتبع استهلاك، فواتير، صيانة شبكات
- target_customers: مؤسسات مياه، بلديات
- stack: (set at gate 3 by principal-system-architect)
- enriched_description: نظام متكامل لإدارة شبكات المياه موجه للبلديات ومؤسسات المياه. يشمل إدارة المشتركين والعدادات، قراءة العدادات (يدوية + تكامل AMI)، إصدار الفواتير (شرائح تعرفة متعددة)، إدارة أوامر العمل للصيانة، كشف التسربات عبر تحليل التدفق، تقارير المياه غير المحصلة (NRW)، وتطبيق ميداني للفنيين. مصمم خصيصاً للبلديات المتوسطة التي تبحث عن بديل ميسور للأنظمة التقليدية الباهظة.
- target_market_details: سوق برامج إدارة المياه في الشرق الأوسط وشمال أفريقيا بقيمة 4 مليارات دولار. أكثر من 2000 بلدية وهيئة مياه في المنطقة. اعتماد العدادات الذكية ينمو بمعدل 25% سنوياً. الفاقد المائي يصل إلى 40% في بعض الدول، مما يخلق حاجة ملحة لأنظمة كشف التسربات والتحليل.
- competitive_landscape: منافسون مثل Bentley WaterGEMS، Schneider EcoStruxure، IBM Intelligent Water، منصة تَمّ، SAP Water. التميز في كونه SaaS ميسور للبلديات المتوسطة، واجهة عربية بالكامل، كشف تسربات بالذكاء الاصطناعي، تطبيق ميداني للفنيين مع خاصية العمل دون اتصال.
- decisions:
  - PRD: docs/PRD.md — Completed Gate 0. Handoff to sofi-journey-architect for Gate 1 (Discovery).
> Append one bullet per durable fact.
