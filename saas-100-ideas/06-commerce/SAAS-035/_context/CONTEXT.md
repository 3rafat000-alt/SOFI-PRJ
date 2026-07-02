# CONTEXT — SAAS-035 (durable facts; append-only)
- title: InventoryPro
- sector: 06-commerce
- description: إدارة المخازن والجرد: تتبع منتجات، تنبيهات نفاد، إدارة موردين
- target_customers: متاجر جملة، مستودعات
- stack: (set at gate 3 by principal-system-architect)
> Append one bullet per durable fact.
- enriched_desc: نظام إدارة المخازن والجرد — تتبع المنتجات بالمستودعات، تنبيهات نفاد المخزون، إدارة الموردين وأوامر الشراء، مسح باركود، تتبع تواريخ انتهاء الصلاحية. يستهدف متاجر الجملة والمستودعات
- target_market: سوق إدارة المخزون ~$5B. نمو التجارة الإلكترونية يضاعف الطلب على حلول المخزون
- competitors: TradeGecko (غالي), Zoho Inventory (معقد), Odoo Inventory (خبرة تقنية), Cin7 (كبير مكلف), inFlow (لا جوال عربي)
- differentiation: عربي كامل، مسح باركود بالجوال، أسعار $19-$99، تكامل متاجر محلية، جرد دون إنترنت
- business_model: 3 tiers ($19/$49/$99 شهرياً). 14-day trial. Starter: 500 products/1 warehouse
- persona_primary: عمر — مدير مستودع يحتاج جرد آني وتنبيهات وإدارة صلاحية
- persona_secondary: هدى — مسؤولة مشتريات تحتاج طلب كميات ومقارنة موردين
- prd_created: docs/PRD.md — بتاريخ 2026-06-25
- handoff_001_completed: TKT-001 — PRD تم إنتاجه
- handoff_002_created: TKT-002 → sofi-journey-architect لإنتاج Journey Map
