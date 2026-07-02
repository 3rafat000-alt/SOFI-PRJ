# PRD: WikiBase (SAAS-040)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** نظام قواعد المعرفة الداخلية للشركات يتيح أرشفة المحتوى، بحثاً ذكياً، وصلات داخلية، وإدارة صلاحيات متقدمة
- **Problem:** الفرق والشركات تعاني من تشتت المعرفة — المستندات في Google Drive، المعلومات في Slack، السياسات في PDF — ولا توجد قاعدة مركزية قابلة للبحث
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم قاعدة معرفة داخلية بأسلوب Wiki مع محرر نصوص، تصنيف هرمي، بحث كامل النص، وصلات داخلية، وصلاحيات دقيقة

## 2. Market & Opportunity
- **Target market size:** سوق أنظمة المعرفة الداخلية ~$3.5B. الشركات الناشئة والفرق البعيدة تحتاج حلولاً بأسعار معقولة
- **Customer segment:** B2B — شركات تقنية، فرق عمل عن بعد، أقسام الموارد البشرية والتقنية
- **Competitor landscape:**
  1. Confluence (المهيمن لكن مكلف ومعقد للشركات الصغيرة)
  2. Notion (قوي لكن تصنيفه محدود للمعرفة العميقة)
  3. GitBook (ممتاز للتوثيق لكن ليس للقواعد الداخلية)
  4. Nuclino (جيد لكن محدود في الصلاحيات)
  5. Slite (بسيط لكن بدون دعم عربي جيد)
- **Differentiation:** تركيز على البساطة والسرعة، دعم كامل للغة العربية والبحث الذكي بها، صلاحيات دقيقة على مستوى الصفحة والمجلد، أسعار تبدأ من $9/شهر للفرق الصغيرة

## 3. User Personas

### Primary: مدير تقني (طارق)
- **Role:** مدير قسم تقنية المعلومات في شركة اتصالات
- **Goals:** توثيق سياسات الأمن، إجراءات التشغيل، أرشفة معرفة الفريق
- **Pain points:** صعوبة العثور على مستندات قديمة، مغادرة الموظفين تأخذ المعرفة معهم، لا توجد ربط بين المستندات ذات الصلة

### Secondary: موظف عن بُعد (مريم)
- **Role:** مطورة في شركة تعمل عن بُعد
- **Goals:** الوصول السريع لدليل المطور، سياسات الشركة، كيفية إعداد بيئة العمل
- **Pain points:** تضيع ساعات بالبحث عن معلومات في Slack وDrive، صعوبة معرفة أي مستند محدث، تحتاج مساحة واحدة للبحث

### Admin: مدير النظام
- **Dashboard operator:** يدير المستخدمين والصلاحيات، يراقب استخدام المعرفة، يضبط إعدادات البحث

## 4. Features by Platform

### Laravel API (Backend)
- Page CRUD with rich text / Markdown content
- Nested hierarchy — spaces → categories → pages
- Full-text search with Arabic stemming support (MeCab or custom)
- Internal linking — auto-suggest links to other pages, backlinks tracking
- Version history — diff view with rollback
- Permissions — view/edit/admin per space or page, role-based
- Page templates — predefined structures for common doc types
- Tags and metadata per page
- Export — PDF, Markdown, HTML, single-page HTML archive
- Import — Confluence and Notion migration scripts
- Analytics — most viewed, search terms, stale pages

### React Dashboard (Web)
- Wiki page tree — expand/collapse navigation
- WYSIWYG editor with slash-commands (/ for blocks, @ for links)
- Search bar with instant results (split view)
- Page detail — breadcrumbs, TOC, linked pages
- History sidebar — version list, diff viewer
- Page settings — permissions, tags, template
- Space management — create, archive, permissions
- Dashboard — popular pages, recent updates, stale content
- User management — invite, roles

### Flutter App (Mobile)
- Wiki tree navigation
- View pages with rendered HTML/Markdown
- Full-text search
- Quick bookmark / favorite pages
- Push notifications for page updates (@mentions, changes)
- Add to home screen — quick access
- Dark mode
- Share page via link

## 5. Data Model (MVP)
- **User:** id, name, email, role, avatar
- **Space:** id, name, description, icon, settings, created_at
- **SpaceMember:** id, space_id, user_id, permission (view/edit/admin)
- **Category:** id, space_id, parent_id, name, order
- **Page:** id, space_id, category_id, title, slug, content, is_published, created_at, updated_at
- **PageVersion:** id, page_id, content, title, created_by, created_at
- **PageLink:** id, page_id, linked_page_id, anchor_text
- **Tag:** id, name, color
- **PageTag:** id, page_id, tag_id
- **PageView:** id, page_id, user_id, viewed_at

## 6. API Endpoints (MVP)
- `GET /api/spaces` — list user spaces
- `POST /api/spaces` — create space
- `GET /api/spaces/{id}` — space with page tree
- `GET /api/spaces/{id}/pages` — list pages
- `POST /api/pages` — create page
- `GET /api/pages/{id}` — page content
- `PUT /api/pages/{id}` — update page
- `DELETE /api/pages/{id}` — soft delete
- `GET /api/pages/{id}/versions` — version history
- `GET /api/pages/{id}/versions/{vid}` — specific version
- `GET /api/pages/{id}/links` — backlinks
- `GET /api/search?q=&space_id=` — full-text search
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register
- `GET /api/dashboard/stats` — wiki-wide stats

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Space list
  - Page tree (sidebar navigation)
  - Page editor (WYSIWYG + slash commands)
  - Search results page (preview sidebar)
  - Version history + diff view
  - Space settings
  - Admin panel (users, roles, system settings)
- **Mobile:**
  - Login
  - Spaces list
  - Page tree navigation
  - Page view (rendered)
  - Search bar with results
  - Bookmarks
  - Notifications

## 8. Business Model
- **Pricing tiers:**
  - Free: 1 space, 50 pages, 5 users
  - Pro ($19/mo): 5 spaces, 500 pages, 20 users, version history
  - Business ($49/mo): 20 spaces, unlimited pages, unlimited users, export
  - Enterprise ($99/mo): self-hosted option, priority support, API
- **Free trial:** 14-day free Pro trial
- **Target MRR per client:** $19-$99/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — Spaces, Pages CRUD, Search engine, Auth
- **Phase 2 (Weeks 3-4):** React Dashboard — Wiki tree, Page editor, Search, Versions
- **Phase 3 (Weeks 5-6):** Flutter App — Page viewing, Search, Bookmarks, Notifications
- **Phase 4 (Weeks 7-8):** Permissions, Templates, Import/Export, Deploy

## 10. Risk & Mitigation
- **Technical risk:** Full-text Arabic search quality
  - *Mitigation:* Use custom Arabic tokenizer with Laravel Scout + Meilisearch/Typesense
- **Market risk:** Confluence/Notion dominance
  - *Mitigation:* Target price-sensitive small teams, offer simpler/faster experience
- **Adoption risk:** Teams revert to old habits (Slack/Drive)
  - *Mitigation:* Provide browser extension + Slack bot for quick save to WikiBase, email integration
