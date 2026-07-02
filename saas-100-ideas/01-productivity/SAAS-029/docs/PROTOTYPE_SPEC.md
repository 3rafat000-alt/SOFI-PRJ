# PROTOTYPE SPEC — DocuSign Pro (SAAS-029)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard — لوحة المستندات (مقابل Stage: لوحة)
- **Layout:** 3 بطاقات (مرسل، منتظر التوقيع، مكتمل). جدول آخر المغلفات. زر إنشاء مغلف جديد عائم.
- **Components:** StatusCards, EnvelopeTable, FAB, StatusBadge, SearchBar
- **States:** Empty (لا مستندات), Loading (جلب), Error (تعذر التحميل), Edge (500+ مغلف)
- **Key Interaction:** نقر على بطاقة ← فلترة القائمة حسب الحالة
- **Friction Resolved:** #3 (بطء تحديث الحالة)

## Screen: NewEnvelope — إنشاء مغلف (مقابل Stage: حقول)
- **Layout:** خطوات: (1) رفع مستند (سحب وإفلات) (2) إضافة مستقبلين (3) وضع حقول التوقيع على المستند (4) إرسال.
- **Components:** DropZone, SignerList, DocumentCanvas, FieldToolbar, SendButton
- **States:** Empty (مغلف جديد), Loading (رفع/معالجة PDF), Error (PDF غير مدعوم), Edge (مستند 100+ صفحة)
- **Key Interaction:** سحب حقل توقيع إلى المكان المناسب على المستند
- **Friction Resolved:** #1 (رفع PDF كبير بطيء → معالجة خلفية)

## Screen: Signing — التوقيع (مقابل Stage: توقيع)
- **Layout:** مستند PDF مع حقول التوقيع محددة. نقر على حقل ← يفتح لوحة التوقيع (رسم/كتابة/رفع صورة توقيع). زر تأكيد.
- **Components:** DocumentViewer, SignaturePad, InitialField, DateField, ConfirmButton
- **States:** Empty (يُحمّل), Ready (جاهز للتوقيع), Signed (تم), Error (رابط منتهي), Edge (مستند فارغ)
- **Key Interaction:** نقر على حقل ← يرسم توقيعه بإصبعه ← تأكيد
- **Friction Resolved:** #2 (واجهة التوقيع لا تدعم اللمس)

## Screen: Tracking — تتبع المغلف (مقابل Stage: متابعة)
- **Layout:** Timeline بصري لتقدم التوقيع (كل مستقبل مع علامة ✓ أو ⏳). زر إعادة إرسال. معاينة و تنزيل.
- **Components:** SigningTimeline, SignerStatus, ResendButton, PreviewLink, DownloadButton
- **States:** Empty, Loading, Completed (كلهم وقعوا), Error, Edge (10+ موقع)
- **Key Interaction:** نقر على اسم مستقبل ← إرسال تذكير
- **Friction Resolved:** #4 (رابط التوقيع منتهي → إعادة إرسال)

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| DropZone | Single/Multiple | Empty/DragOver/Uploading/Uploaded/Error | حدود متقطعة مع أيقونة سحب |
| DocumentCanvas | View/Edit | ZoomIn/ZoomOut/Pan | تمرير وتكبير/تصغير بالإصبعين |
| FieldToolbar | Signature/Initial/Date/Text | Selected/Placed | سحب من toolbar إلى canvas |
| SignaturePad | Draw/Type/Upload | Empty/Drawing/Confirmed | رسم بإصبع أو قلم مع زر مسح |
| SigningTimeline | Horizontal/Vertical | Pending/Signed/Expired/Declined | خط متصل بين الخطوات |
