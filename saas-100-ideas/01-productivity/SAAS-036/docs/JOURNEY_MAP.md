# JOURNEY MAP — NoteSpace (SAAS-036)
> Owner: Journey Architect · Gate 1 · Persona: سلمى (طالبة)

## Flow (Mermaid)
```mermaid
flowchart LR
  trigger["📝 فكرة / محاضرة"] --> create_note["إنشاء ملاحظة جديدة"]
  create_note --> organize["تنظيم في مجلدات"]
  organize --> write["كتابة المحتوى"]
  write --> collaborate["مشاركة مع الزملاء"]
  collaborate --> edit["تحرير جماعي"]
  edit --> search["بحث لاحق"]
  search --> goal["✅ إنجاز المادة"]
  create_note -.-> offline["📴 بدون إنترنت"]
  offline --> local_save["حفظ محلي"]
  local_save --> sync["مزامنة عند الاتصال"]
  sync --> organize
  write -.-> conflict["⚠️ تعارض تحرير"]
  conflict --> resolve["حل التعارض"]
  resolve --> edit
  collaborate -.-> permission["🔒 لا صلاحية"]
  permission --> request_access["طلب صلاحية"]
  request_access --> edit
  organize -.-> too_many["📂 مجلدات كثيرة"]
  too_many --> search
```

## Stage Annotations
| Stage | User Action | Goal | Emotion | Friction | Screen |
|-------|-------------|------|---------|----------|--------|
| Trigger | سلمى تريد تدوين محاضرة | بدء الملاحظة | 😊 نشيطة | — | — |
| Create Note | تنقر زر "ملاحظة جديدة" | فتح محرر | 🙂 سريع | — | Empty editor |
| Organize | تختار المجلد والوسوم | تنظيم المحتوى | 😐 عادي | مجلدات كثيرة | Folder Tree |
| Write | تكتب المحتوى بالعربية | توثيق المادة | 😊 منتجة | تنسيق RTL | Rich Text Editor |
| Collaborate | تشارك مع زميلاتها | تعاون | 😊 متعاونة | — | Share Dialog |
| Edit | زميلاتها يعدلن آنياً | تحسين الملاحظات | 🙂 سعيد | تعارضات | Collaborative Edit |
| Search | تبحث عن معلومة قديمة | إيجاد سريع | 😐 قلق | كلمات مفتاحية غير دقيقة | Search Results |
| Goal | تستعد للامتحان | مراجعة | 😃 جاهزة | — | — |

## Ranked Friction Log
1. **[High]** RTL غير مدعوم كافياً في المحررات — دعم كامل للغة العربية والكتابة من اليمين
2. **[High]** فقدان الملاحظات — حفظ تلقائي + مزامنة سحابية
3. **[Med]** تعارض التحرير التعاوني — CRDT / OT مع حل بسيط
4. **[Med]** صعوبة البحث في الكم الكبير من الملاحظات — بحث كامل النص مع اقتراحات
5. **[Low]** مشاركة الفريق تحتاج إدارة صلاحيات — صلاحيات مشاهدة/تعديل لكل ملاحظة

**Rule:** Every later feature MUST trace to a stage above.
