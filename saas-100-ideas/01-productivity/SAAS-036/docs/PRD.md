# PRD: NoteSpace (SAAS-036)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** مساحة تدوين ملاحظات تعاونية تتيح الكتابة الجماعية، إدارة المهام، ومشاركة المعرفة مع الفريق في الوقت الفعلي
- **Problem:** الطلاب وفرق العمل يحتاجون أداة بسيطة للكتابة التعاونية وتنظيم الملاحظات ولكن التطبيقات الحالية إما باهظة (Notion) أو سحابية فقط (Google Docs) أو تفتقر لميزات عربية
- **Proposed solution:** Laravel API + React Dashboard + Flutter App تقدم محرر نصوص تعاونياً، مجلدات منظمة، قوائم مهام، ومشاركة آنية

## 2. Market & Opportunity
- **Target market size:** سوق تطبيقات الملاحظات ~$1.5B. الطلاب والفرق الصغيرة يمثلون شريحة ضخمة غير مخدومة بشكل كافٍ في المنطقة
- **Customer segment:** B2C + B2B — طلاب جامعات، فرق عمل صغيرة، باحثون، كتاب مستقلون
- **Competitor landscape:**
  1. Notion (قوي لكن واجهته معقدة للمبتدئين وسعره مرتفع للفرق)
  2. Google Docs (مجاني لكن غير مناسب للملاحظات الهيكلية)
  3. Evernote (قديم، الواجهة ثقيلة، التركيز محدود على التعاون)
  4. OneNote (جيد لكن مرتبط بـ Microsoft ecosystem)
  5. Obsidian (ممتاز للأفراد لكن التعاون محدود)
- **Differentiation:** توازن بين البساطة والقوة، دعم كامل للغة العربية والكتابة من اليمين لليسار، الطبقة المجانية سخية، تطبيق جوال سريع وخفيف مع العمل دون اتصال

## 3. User Personas

### Primary: طالب جامعي (سلمى)
- **Role:** طالبة في السنة الثالثة هندسة برمجيات
- **Goals:** تدوين محاضرات، مشاركة ملاحظات مع زملائها، تنظيم المواد الدراسية
- **Pain points:** تنسيق الملاحظات بين أكثر من تطبيق، صعوبة مشاركة الملاحظات بشكل منظم، فقدان الملاحظات عند تغيير الجهاز

### Secondary: مدير فريق تقني (زياد)
- **Role:** مدير فريق تطوير في شركة ناشئة
- **Goals:** توثيق قرارات الفريق، مشاركة المعرفة، كتابة وثائق سريعة
- **Pain points:** لا وقت لتعلم أدوات معقدة، يريد شيئاً مثل Notion لكن أبسط وأرخص، يحتاج مشاركة سريعة عبر رابط

### Admin: مدير النظام
- **Dashboard operator:** يدير مساحات العمل، يراقب الاستخدام، يضبط الصلاحيات

## 4. Features by Platform

### Laravel API (Backend)
- Note CRUD with rich text editor backend (Delta/Quill format)
- Nested folders and tags organization
- Real-time collaboration via WebSockets (Operational Transform/CRDT)
- Markdown support with HTML conversion
- Search engine (full-text search across notes, folders, tags)
- Permission system (view/edit/comment per note or folder)
- Share links (public or team-only, with expiry)
- Version history — track changes per note
- Export — PDF, Markdown, TXT, DOCX
- Team workspaces with member management

### React Dashboard (Web)
- Note editor (rich text with formatting bar, image upload, code blocks)
- Folder tree navigation (collapse/expand, drag-drop reorder)
- Note list with preview, tags, last edited date
- Collaborative editing with cursor presence indicators
- Search with highlighting
- Trash / recently deleted
- Workspace management — create, invite, settings
- Quick notes (floating mini-editor)

### Flutter App (Mobile)
- Note list with search
- Full rich text editor for mobile
- Offline-first — create/edit notes without internet, sync on reconnect
- Quick note widget (home screen)
- Share content to NoteSpace via share sheet
- Voice note recording (transcription optional)
- Push notifications for @mentions and shared notes
- Dark mode

## 5. Data Model (MVP)
- **User:** id, name, email, avatar, created_at
- **Workspace:** id, name, description, owner_id, settings (JSON)
- **WorkspaceMember:** id, workspace_id, user_id, role (admin/member/viewer)
- **Folder:** id, workspace_id, parent_id, name, order, color
- **Note:** id, folder_id, title, content (JSON — Delta), plain_text, is_pinned, is_archived, created_at, updated_at
- **NoteCollaborator:** id, note_id, user_id, permission (read/write)
- **NoteVersion:** id, note_id, content_snapshot, created_by, created_at
- **Tag:** id, name, color, workspace_id
- **NoteTag:** id, note_id, tag_id

## 6. API Endpoints (MVP)
- `GET /api/workspaces` — list user workspaces
- `POST /api/workspaces` — create workspace
- `GET /api/workspaces/{id}/notes` — list notes in workspace
- `POST /api/notes` — create note
- `GET /api/notes/{id}` — get note content
- `PUT /api/notes/{id}` — update note
- `DELETE /api/notes/{id}` — soft delete
- `GET /api/notes/{id}/versions` — version history
- `POST /api/notes/{id}/collaborators` — add collaborator
- `GET /api/search?q=` — full-text search
- `POST /api/auth/login` — login
- `POST /api/auth/register` — register

## 7. User Interface (Screen List)
- **Dashboard:**
  - Login/Register
  - Workspace view (folder tree + note list)
  - Note editor (full rich text toolbar)
  - Search results page
  - Trash
  - Settings (profile, workspace, billing)
- **Mobile:**
  - Login/Register
  - Note list (folders as tabs)
  - Note editor (rich text simplified toolbar)
  - Search bar
  - Quick add (floating button)
  - Offline indicator
  - Share sheet integration

## 8. Business Model
- **Pricing tiers:**
  - Free: 50 notes, 2 workspaces, basic formatting
  - Pro ($9/mo): unlimited notes, 10 workspaces, version history, export
  - Team ($19/mo): unlimited workspaces, team management, priority support
- **Free trial:** 30-day Pro trial
- **Target MRR per client:** $9-$19/month

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2):** API — User auth, Workspace/Folder/Note CRUD, Search
- **Phase 2 (Weeks 3-4):** React Dashboard — Editor, Folder tree, Search, Sharing
- **Phase 3 (Weeks 5-6):** Flutter App — Offline notes, Editor, Sync, Widget
- **Phase 4 (Weeks 7-8):** Real-time collaboration, Version history, Export, Deploy

## 10. Risk & Mitigation
- **Technical risk:** Real-time collaboration sync conflicts
  - *Mitigation:* Use CRDT-like approach with operational transform, simpler than Google Docs-level
- **Market risk:** Notion dominates the space
  - *Mitigation:* Target Arabic-speaking students and small teams who find Notion expensive/complex
- **Offline risk:** Sync conflicts after offline edits
  - *Mitigation:* Last-write-wins for first iteration, implement conflict resolution in v2
