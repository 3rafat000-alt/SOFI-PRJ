# PRD: DocuSign Pro (SAAS-029)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة التوقيع الإلكتروني للوثائق — رفع مستندات، توقيع رقمي آمن، أرشفة ذكية.
- **Problem:** الشركات والمحامون والعقاريون يعتمدون على الطباعة والتوقيع اليدوي و المسح الضوئي. بطيء، مكلف، غير آمن.
- **Proposed solution:** Laravel API + React Dashboard لإدارة الوثائق + Flutter App للتوقيع المتنقل.

## 2. Market & Opportunity
- **Target market size:** سوق التوقيع الإلكتروني ~$15B. النمو ~25% سنوياً بعد COVID.
- **Customer segment:** B2B (شركات، مكاتب محاماة، عقاريون، بنوك).
- **Competitor landscape:** DocuSign, Adobe Sign, HelloSign, Esign.ly.
- **Differentiation:** التوافق مع النظام السعودي للتوقيع الإلكتروني، تسعير أقل 60% من DocuSign، دمج مع منصات محلية.

## 3. User Personas
- **Primary 1 — محامٍ (تركي):** يحتاج إرسال عقود للتوقيع، تتبع حالة التوقيع، أرشفة آمنة.
- **Primary 2 — عقاري (سارة):** تريد توقيع عقود الإيجار إلكترونياً مع المستأجرين عن بعد.
- **Admin — مدير المنصة:** يدير المستخدمين، يراقب الاستخدام، فواتير.

## 4. Features by Platform

### Laravel API (Backend)
- Core models: Document, Envelope, Signer, SignatureField, AuditLog
- RESTful endpoints: CRUD documents/envelopes, signing workflow
- Auth & roles: JWT, roles (admin, sender, signer)
- Document processing: PDF manipulation, field detection
- Digital signature: hash-based signing, PKI support
- Notifications: إيميل لدعوة التوقيع، تأكيد التوقيع

### React Dashboard (Web)
- رفع مستندات وإنشاء مغلفات التوقيع
- إضافة حقول التوقيع (سحب وإفلات)
- تتبع حالة التوقيع في الوقت الحقيقي
- قوالب العقود المتكررة
- سجل التدقيق (audit log كامل)
- إدارة جهات الاتصال

### Flutter App (Mobile)
- عرض وتوقيع المستندات
- التوقيع بالإصبع أو القلم
- معاينة المستند
- إشعارات دعوة التوقيع
- أرشفة المستندات الموقعة

## 5. Data Model (MVP)
- **Document:** id, envelope_id, filename, file_path, file_hash, page_count, size
- **Envelope:** id, title, message, status (draft/sent/signed/completed/declined), created_by, expires_at
- **Signer:** id, envelope_id, name, email, role (signer/cc), signing_order, signed_at, status
- **SignatureField:** id, document_id, signer_id, page, x, y, width, height, type (signature/date/initial/text)
- **AuditLog:** id, envelope_id, action, ip_address, user_agent, timestamp
- **Template:** id, name, document_id, fields_config, created_by

## 6. API Endpoints (MVP)
- `POST /auth/register`, `POST /auth/login`
- `POST /documents/upload`, `GET /documents`, `DELETE /documents/{id}`
- `POST /envelopes`, `GET /envelopes`, `GET /envelopes/{id}`
- `POST /envelopes/{id}/send`
- `POST /sign/{envelope_id}/{signer_token}` (signing URL)
- `GET /envelopes/{id}/audit-log`
- `GET /templates`, `POST /templates`, `POST /templates/{id}/use`

## 7. User Interface (Screen List)
- **Dashboard:** حالة المغلفات (مرسل، موقع، مكتمل)
- **New Envelope:** رفع مستند ← إضافة موقعين ← وضع حقول ← إرسال
- **Envelope Detail:** تتبع التوقيع، معاينة، تنزيل
- **Templates:** قوالب العقود
- **Contacts:** قائمة جهات الاتصال المتكررة
- **Mobile - Sign:** شاشة توقيع (scroll, tap field, draw sign)

## 8. Business Model
- **Pricing tiers:**
  - Personal (5 envelopes/month): $FREE
  - Pro (50 envelopes/month): $29/شهر
  - Business (500 envelopes/month): $99/شهر
- **Free trial:** 5 مغلفات مجاناً شهرياً
- **Target MRR per client:** $29-$99

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Document upload + PDF processing
- Phase 2 (Weeks 3-4): React Dashboard + Envelope builder + Fields
- Phase 3 (Weeks 5-6): Signing flow + Signature capture
- Phase 4 (Weeks 7-8): Flutter signing app + Audit log + Compliance

## 10. Risk & Mitigation
- **Technical risk:** PDF manipulation معقد. → استخدام PDFLib أو مكتبة قوية.
- **Legal risk:** التوقيع الإلكتروني غير معترف به. → التوافق مع نظام التوقيع الإلكتروني السعودي.
- **Security risk:** أمان المستندات الحساسة. → تشفير AES-256 في السكون + HTTPS.
