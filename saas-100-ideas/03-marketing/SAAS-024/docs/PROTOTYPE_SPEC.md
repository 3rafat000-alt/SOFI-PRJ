# PROTOTYPE SPEC — SocialKit (SAAS-024)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard — لوحة التحكم (مقابل Stage: لوحة)
- **Layout:** شريط جانبي بالحسابات المتصلة. لوحة: آخر المنشورات، ملخص الأسبوع (وصول، تفاعل، متابعين). زر إنشاء منشور عائم.
- **Components:** AccountSidebar, SummaryCards, PostFeed, FAB, WeekSelector
- **States:** Empty (لا حسابات بعد - معالج ربط), Loading, Error (منصة غير متصلة), Edge (50+ حساب)
- **Key Interaction:** ربط حساب OAuth ← توجيه إلى المنصة ← عودة
- **Friction Resolved:** #1 (OAuth يفشل)

## Screen: Calendar — تقويم المحتوى (مقابل Stage: تقويم)
- **Layout:** عرض أسبوعي أفقي drag & drop. كل يوم به أعمدة للمنصات. منشورات مصغرة قابلة للسحب.
- **Components:** WeekGrid, PostCard, PlatformBadge, DropZone, CreateSlot
- **States:** Empty (أسبوع بدون محتوى), Loading (جلب الجدول), Error (تعذر التحميل), Edge (منشور مكرر)
- **Key Interaction:** سحب منشور من يوم لآخر أو من منصة لأخرى
- **Friction Resolved:** #3 (صعوبة سحب بين الأيام)

## Screen: PostEditor — محرر منشورات (مقابل Stage: إنشاء)
- **Layout:** حقل نص مع تنسيق بسيط، معاينة حية لكل منصة، رفع وسائط مع ضغط تلقائي، اختيار وقت الجدولة.
- **Components:** TextEditor, MediaUpload, PreviewTabs, SchedulePicker, PlatformToggle
- **States:** Empty (منشور جديد), Loading (رفع الوسائط), Error (وسائط كبيرة جداً), Edge (نص طويل > 4000 حرف)
- **Key Interaction:** يكتب نصاً ← معاينة تلقائية ← يضيف وسائط ← يحدد منصة ← يجدول
- **Friction Resolved:** #2 (معاينة مختلفة لكل منصة)

## Screen: Inbox — صندوق وارد موحد (مقابل Stage: رد)
- **Layout:** قائمة تعليقات ورسائل من كل المنصات. شريط رد. علامات تصنيف (غير مقروء، يحتاج رد).
- **Components:** UnifiedList, MessageCard, ReplyBox, QuickReplies, FilterTabs
- **States:** Empty (لا رسائل), Loading (جلب), Error (منصة لا تدعم), Edge (1000+ رسالة)
- **Key Interaction:** نقر على رسالة ← يظهر الرد ← يكتب أو يختار رد سريع ← إرسال
- **Friction Resolved:** #5 (اكتشاف التعليق متأخراً)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| AccountSidebar | Collapsed/Expanded | Connected/Disconnected/Loading | أيقونة + اسم المنصة + لون حسب المنصة |
| PostCard | Draft/Scheduled/Published/Failed | Default/Hover/Dragging | سحب وإفلات مع ظل |
| PreviewTabs | Instagram/Twitter/Snapchat/LinkedIn | Active/Inactive | يعرض نفس المحتوى بتنسيق مختلف |
| MediaUpload | Single/Multiple | Empty/Loading/Uploaded/Error | ضغط تلقائي + معاينة مصغرة |
| QuickReplies | Custom/Template | Default/Selected/Edited | حفظ الردود المتكررة كقوالب |
