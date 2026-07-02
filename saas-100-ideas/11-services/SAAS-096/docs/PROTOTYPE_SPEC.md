# PROTOTYPE SPEC — NGOmgt (SAAS-096)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (Journey Stage: نظرة عامة)
- **Layout:** بطاقات إحصائية (إجمالي التبرعات/المشاريع/المستفيدين) + رسم بياني + آخر التبرعات
- **Components:** StatCard, ChartWidget, RecentDonationList, ProjectProgressCard
- **States:** Empty (بداية الجمعية)، Loading (Skeleton)، Error (فشل تحميل), Edge (مشاريع كثيرة)
- **Key Interaction:** تصفح إحصائيات ← ضغط على مشروع للتفاصيل
- **Friction Resolved:** #1 — الشفافية

## Screen: Campaign (Journey Stage: حملة التبرع)
- **Layout:** شريط تقدم الحملة + قائمة متبرعين + أدوات تواصل
- **Components:** ProgressBar, DonorList, SMSPublisher, ShareButton, GoalMeter
- **States:** Empty (حملة جديدة), Loading, Error, Edge (حملة منتهية)
- **Key Interaction:** إطلاق حملة ← مشاركة ← متابعة التبرعات
- **Friction Resolved:** #2 — إدارة المتبرعين

## Screen: Impact Report (Journey Stage: توثيق الأثر)
- **Layout:** معرض صور + قصص + إحصائيات أثر + PDF download
- **Components:** ImageGallery, StoryCard, ImpactChart, PDFExportButton, BeneficiaryCounter
- **States:** Empty (لا أثر بعد), Loading, Error, Edge (أثر متعدد المشاريع)
- **Key Interaction:** إضافة صور ← كتابة قصة ← نشر التقرير
- **Friction Resolved:** #3 — توثيق الصرف

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| StatCard | donation, project, beneficiary, volunteer | default, hover, animate | إحصائية مع رسم بياني صغير |
| GoalMeter | percentage, amount | under-target, on-target, exceeded | عداد تقدم الحملة |
| DonorList | recent, top, all | default, expanded, filtered | قائمة متبرعين مع تاريخ |
| StoryCard | image, video, text | default, expanded, featured | بطاقة قصة أثر |
| ImpactChart | bar, pie, timeline | with-data, empty, loading | رسوم بيانية للأثر |
| CampaignCard | active, completed, draft | default, shared | بطاقة حملة تبرع |
