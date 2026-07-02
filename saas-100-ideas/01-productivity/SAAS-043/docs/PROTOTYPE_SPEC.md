# PROTOTYPE SPEC — FileVault (SAAS-043)
> Owner: UI/UX Designer · Gate 2

## Screen: File Manager (maps to Journey Stage: Upload)
- **Layout:** Breadcrumb + grid/list toggle + upload button (top), file cards with thumbnail/icon (main)
- **Components:** FileCard, Breadcrumb, UploadButton, ViewToggle, SearchInput, EmptyState
- **States:**
  - Empty: "No files yet. Upload your first file." + cloud upload CTA
  - Loading: Skeleton cards × 6
  - Error: "Failed to load files" + retry
  - Edge: 1000+ files — virtualized grid, pagination
- **Key Interaction:** Click file → detail; drag-drop upload area; right-click context menu
- **Friction Resolved:** [#1] رفع سريع مع تقدم مرئي

## Screen: Share Dialog (maps to Journey Stage: Share)
- **Layout:** Modal with link settings (expiry, password, max downloads, permission level) + copy link button
- **Components:** DatePicker, ToggleSwitch, NumberStepper, PermissionSelect, CopyButton, QRCode
- **States:**
  - Default: Expiry (24h), password (off), unlimited downloads
  - Active: Generated link with copy button
  - Error: "Link generation failed" + retry
- **Key Interaction:** Toggle password → show password input → copy link → auto-close
- **Friction Resolved:** [#2, #3] رابط آمن مع صلاحيات دقيقة

## Screen: Access Logs (maps to Journey Stage: Audit)
- **Layout:** Table with columns (file, user, action, timestamp, IP, device), filters on top
- **Components:** DataTable, FilterBar, DateRangePicker, ExportButton
- **States:**
  - Empty: "No access logs yet"
  - Loading: Skeleton rows
  - Error: "Logs unavailable"
  - Edge: 10000+ logs — pagination + date range filter
- **Key Interaction:** Click row → expand details (user agent, location)
- **Friction Resolved:** [#5] سجل تدقيق كامل

## Screen: Public File View (maps to Journey Stage: Client Access)
- **Layout:** File preview (if possible) + download button + password form (if protected)
- **Components:** FilePreview, DownloadButton, PasswordInput, ExpiryBadge
- **States:**
  - Password required: Form with single input
  - Expired: "This link has expired" + request new link button
  - Loading: "Preparing download..."
  - Error: "File not found"
- **Key Interaction:** Enter password → authenticated → show download
- **Friction Resolved:** [#2] تخمين كلمة السر مع إشارة واضحة

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| FileCard | grid/list | default, hover, selected, uploading | Upload progress overlay |
| UploadButton | single/multiple | default, active, disabled, uploading | Drag zone highlight |
| ShareLink | with password/without | generated, copied, expired | Auto-copy on click |
| PermissionSelect | view/download/edit/admin | default, active, disabled | Icon per permission level |
| CopyButton | default, link | idle, copied (checkmark), error | 2s feedback |
| FilePreview | image/video/pdf/text | loading, loaded, error, unsupported | Thumbnail fallback for unsupported |
| Breadcrumb | default, clickable | active page, hover | Folder path navigation |
