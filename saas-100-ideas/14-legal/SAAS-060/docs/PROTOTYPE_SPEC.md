# PROTOTYPE SPEC — WorkPermit (SAAS-060)
> Owner: UI/UX Designer · Gate 2

## Screen: Dashboard (maps to Journey Stage: استلام التذكير)
- **Layout:** Expiry countdown cards (next 3) + compliance score ring + alerts list + quick actions
- **Components:** CountdownCard, ComplianceRing, AlertList, QuickActionButton, CalendarBadge
- **States:** Empty (no licenses added → "أضف رخصتك الأولى"), Loading (skeleton), Error, Edge (all expired → "جميع التراخيص منتهية!")

## Screen: License List (maps to Journey Stage: عرض التراخيص)
- **Layout:** Categorized tabs (تجارية/مهنية/شخصية) + status indicators + search
- **Components:** CategoryTab, LicenseCard, StatusBadge (active/expiring/expired), SearchBar
- **States:** Empty (no licenses in category), Loading (skeleton cards), Error, Edge (expiring within 7 days → yellow badge)
- **Key Interaction:** Tap card → detail view with actions
- **Friction Resolved:** [#2] صعوبة العثور على الوثائق → تصنيف وبحث

## Screen: Renewal Guide (maps to Journey Stage: تجهيز المستندات)
- **Layout:** Step-by-step checklist + document requirements + links to portals + upload
- **Components:** StepList, DocumentRequirementCard, ExternalLinkButton, DocumentUploader, StatusTracker
- **States:** Empty (no renewal started), Loading (guide data), Error (guide unavailable), Edge (external portal → link out)
- **Key Interaction:** Follow steps → upload each document → track progress
- **Friction Resolved:** [#3] عدم معرفة الإجراءات → دليل تفاعلي خطوة بخطوة

## Screen: Document Vault (maps to Journey Stage: تخزين الوثائق)
- **Layout:** Grid/list view + search + category filter + add button
- **Components:** DocCard, CategoryFilter, DocSearchBar, UploadFAB, ExpiryBadge
- **States:** Empty (no documents → "ارفع مستنداتك"), Loading, Error, Edge (expired document → red badge "منتهي")

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| CountdownCard | days count | safe, warning, critical, expired | Color-coded urgency |
| ComplianceRing | percentage | 0-100% | Green to red gradient |
| LicenseCard | commercial, professional, personal | active, expiring, expired | Swipe for quick action |
| StepList | numbered | pending, current, completed | Checkmark animation |
| DocumentUploader | camera, file | empty, uploading, uploaded | Camera capture + crop |
| ExpiryBadge | green, yellow, red | valid, expiring-soon, expired | Days remaining text |
