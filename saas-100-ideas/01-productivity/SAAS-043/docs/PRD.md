# PRD: FileVault (SAAS-043)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- **One-liner:** منصة مشاركة ملفات آمنة — رفع، تنزيل، صلاحيات دقيقة، روابط منتهية الصلاحية، تشفير كامل.
- **Problem:** الفرق وشركات المحاماة تحتاج مشاركة ملفات آمنة بدون تعقيد. WeTransfer غير آمن، Google Drive يفتقر للصلاحيات الدقيقة، Dropbox مكلف.
- **Solution:** FileVault — مشاركة ملفات مشفرة مع صلاحيات قابلة للتخصيص، روابط مؤقتة، وسجل تدقيق كامل.

## 2. Market & Opportunity
- **Target market:** سوق مشاركة الملفات الآمنة ~$7B (2025). الفئة القانونية ~$1.7B.
- **Customer segment:** B2B — فرق عمل، شركات محاماة، مكاتب استشارات، محاسبون.
- **Competitors:**
  - Dropbox Business: قوي لكن غالي ($25/شهر/مستخدم).
  - Google Drive: مجاني لكن صلاحيات محدودة.
  - WeTransfer: بسيط لكن غير مشفر وغير آمن.
  - Tresorit: آمن جداً لكن غالي جداً ($50/شهر).
- **Differentiation:** تشفير من طرف لطرف، روابط ذاتية التدمير، صلاحيات على مستوى الملف، تسعير مناسب للسوق العربي.

## 3. User Personas

### الشخصية الأساسية: مريم — محامية
- **الدور:** تشارك مستندات قانونية حساسة مع عملاء وشركاء
- **الأهداف:** ضمان سرية المستندات، تحكم بصلاحيات الوصول، سجل تدقيق
- **المشكلات:** الحلول الحالية غير آمنة، لا توفر صلاحيات دقيقة، صعوبة إبطال الوصول

### الشخصية الثانوية: سامي — مدير فريق تسويق
- **الدور:** يشارك ملفات إبداعية (صور، فيديوهات) مع العملاء والفرق
- **الأهداف:** روابط سريعة للعملاء، صلاحية محددة، تتبع التحميلات
- **المشكلات:** الملفات الكبيرة تتعطل، العملاء يضيعون الروابط

### Admin: مشرف المؤسسة
- يدير مساحة التخزين، سياسات الأمان، سجل النشاطات، المستخدمين.

## 4. Features by Platform

### Laravel API (Backend)
- Models: File, Folder, ShareLink, Permission, AccessLog, StorageQuota
- Chunked upload (large files), S3/MinIO storage adapter
- AES-256 encryption at rest, TLS in transit
- S3 presigned URLs for direct download
- Role-based permissions (view/download/edit/delete)

### React Dashboard (Web)
- File manager UI (grid/list view, breadcrumbs, drag-drop upload)
- Share link generator with expiry, password, max downloads
- Access log viewer (who viewed/downloaded what, when)
- Storage quota monitoring
- Trash / recycle bin with auto-purge

### Flutter App (Mobile)
- Upload files from device camera/gallery/files
- Browse shared files/folders
- Offline file access (download for offline)
- Auto-backup camera photos
- Share via OS share sheet → generates link

## 5. Data Model (MVP)
- **User**: id, name, email, password, storage_used, storage_limit
- **File**: id, uuid, name, mime_type, size, path, checksum, encryption_key_id, folder_id, owner_id, created_at
- **Folder**: id, name, parent_id, owner_id, path, color
- **ShareLink**: id, file_id, token, password_hash, expires_at, max_downloads, downloads_count, is_revoked
- **Permission**: id, file_id, user_id, permission_level (view/download/edit/admin)
- **AccessLog**: id, file_id, user_id, ip, user_agent, action (view/download/upload/delete), timestamp
- **StorageQuota**: id, user_id, used_bytes, limit_bytes, tier

## 6. API Endpoints (MVP)
- `POST /api/files/upload` — chunked upload (init/part/complete)
- `GET /api/files` — list files (paginated, filter by folder)
- `GET /api/files/{id}` — file detail + download URL
- `DELETE /api/files/{id}` — soft delete
- `POST /api/folders` — create folder
- `GET /api/folders/{id}` — get folder contents
- `POST /api/share-links` — generate link
- `GET /api/share-links/{token}` — access shared file (public)
- `DELETE /api/share-links/{id}` — revoke
- `GET /api/access-logs` — filtered log list
- `POST /api/auth/login`, `POST /api/auth/register`

## 7. User Interface (Screen List)
- **Dashboard** (React): File manager with search → upload → select → share
- **Share Dialog** (React): Expiry picker, password toggle, max downloads slider, copy link
- **Access Logs** (React): Table with filters (date, user, action, file)
- **Storage Analytics** (React): Pie chart by file type, usage trend, quota bar
- **Settings** (React): Security policies, branding, team management, API keys
- **Mobile** (Flutter): Browse → tap → preview / download → share link
- **Mobile Upload**: FAB → pick source → upload progress → auto-share option

## 8. Business Model
- **Free**: 1GB storage, 10 shares/month, 100MB max file
- **Pro**: $12/month — 50GB, unlimited shares, 2GB files, password links
- **Team**: $29/month — 200GB, 5 users, shared folders, access logs
- **Enterprise**: Custom — unlimited storage, SSO, audit logs, on-prem
- **Free trial**: 14 days Team plan
- **Target MRR/client**: $12–$29 (Team avg)

## 9. Implementation Plan
- **Phase 1 (Weeks 1-2)**: Laravel API — File/Folder/ShareLink models + S3 storage + chunked upload
- **Phase 2 (Weeks 3-4)**: React Dashboard — file manager UI, share dialog, access logs
- **Phase 3 (Weeks 5-6)**: Flutter App — browse, upload, offline download, share extension
- **Phase 4 (Weeks 7-8)**: Encryption hardening, trash system, performance testing, deploy

## 10. Risk & Mitigation
- **Technical**: Large file uploads timeout → Mitigation: chunked upload with resumable capability (Tus protocol)
- **Security**: Data leak via shared links → Mitigation: password + expiry + revoke + encryption
- **Market**: Free tools dominance → Mitigation: focus on security & compliance niche (legal firms)
