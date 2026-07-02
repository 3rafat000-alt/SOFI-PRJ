# PROTOTYPE SPEC — ReviewRadar (SAAS-025)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard — لوحة السمعة (مقابل Stage: لوحة)
- **Layout:** KPI كبير (متوسط التقييم). توزيع النجوم (شريط أفقي). آخر 10 تقييمات مع تحليل المشاعر. أيقونة تنبيه عند تقييم سلبي.
- **Components:** RatingGauge, StarDistribution, ReviewFeed, SentimentBadge, AlertBanner
- **States:** Empty (لا تقييمات بعد), Loading (جلب), Error (API منتهي), Edge (تقييم بدون نص)
- **Key Interaction:** نقر على نجمة ← فلترة التقييمات بهذه الدرجة
- **Friction Resolved:** #2 (API tokens تنتهي - مع إشعار)

## Screen: Inbox — صندوق التقييمات (مقابل Stage: مراقبة)
- **Layout:** قائمتان: "غير مجاب عليه" و "تم الرد". بطاقة تقييم مع نص وتصنيف المشاعر. زر رد.
- **Components:** ReviewCard, ReplyButton, SentimentLabel, PlatformIcon, TimeAgo
- **States:** Empty (كل التقييمات مجاب عليها), Loading, Error, Edge (500+ تقييم)
- **Key Interaction:** نقر على بطاقة ← يتمدد ← كتابة رد ← إرسال
- **Friction Resolved:** #3 (تأخير سحب التقييمات)

## Screen: Reply — صياغة الرد (مقابل Stage: رد)
- **Layout:** بطاقة التقييم الأصلي في الأعلى. حقل رد مع اقتراحات ذكية (AI-generated). زر إرسال.
- **Components:** OriginalReview, ReplyField, SuggestionChips, AIToggle, SendButton
- **States:** Empty (رد جديد), Loading (توليد اقتراحات), Error (AI غير متاح), Edge (رد طويل)
- **Key Interaction:** نقر على اقتراح ← يملأ الحقل ← تعديل ← إرسال
- **Friction Resolved:** #4 (الردود المقترحة عامة)

## Screen: Analytics — تحليلات السمعة (مقابل Stage: تحليل)
- **Layout:** خط زمني (متوسط التقييم عبر الوقت). مقارنة الفترات. كلمات مفتاحية متكررة (word cloud). توزيع المشاعر.
- **Components:** TrendChart, PeriodCompare, WordCloud, SentimentPie, ExportButton
- **States:** Empty (لا بيانات كافية), Loading (جلب التحليلات), Error (نطاق تاريخ غير صحيح), Edge (سنة من البيانات)
- **Key Interaction:** تحريك الفترة ← تحديث الرسوم البيانية
- **Friction Resolved:** #1 (لا يعرف اتجاه السمعة)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| RatingGauge | 1-5 stars | Animated/Static | يتغير لون المقياس حسب المتوسط (أخضر ≥4، أصفر ≥3، أحمر <3) |
| ReviewCard | Replied/Unreplied | Default/Hover/Expanded | يتمدد لإظهار الرد |
| SentimentBadge | Positive/Negative/Neutral | Colored/Animated | إيجابي أخضر، سلبي أحمر، محايد رمادي |
| SuggestionChips | 3 اقتراحات | Default/Selected/Loading | إعادة توليد مع أيقونة تحديث |
| TrendChart | 7d/30d/90d/1y | Interactive | نقر على نقطة ← تفاصيل ذلك اليوم |
