# PROTOTYPE SPEC — URLShort Pro (SAAS-037)
> Owner: UI/UX Designer · Gate 2

## Screen: Link Creator (maps to Journey Stage: Create → Paste → Customize → Generate)
- **Layout:** Single-page form with live preview — input URL, domain selector, slug field, QR preview, result card
- **Components:** URL input, domain dropdown, slug input (with availability check), QR preview, copy buttons
- **States:** Empty (no URL) | Validating (URL check) | Available (slug free) | Taken (slug taken → suggestions) | Generated (ready)
- **Key Interaction:** Paste URL → auto-fill slug from title → check availability → adjust → create → show result + QR
- **Friction Resolved:** #4 — اقتراحات slug بديلة

## Screen: Analytics Dashboard (maps to Journey Stage: Track → Analyze)
- **Layout:** KPI row (total clicks, unique clicks, top link, click rate) + charts (time series, geography pie, device bar, referrer)
- **Components:** Line chart (clicks over time), geo map, device breakdown, referrer table, date filter
- **States:** Empty (no clicks yet) | Loading (data fetch) | Error (fetch failed) | Edge case (1M+ clicks → aggregated data)
- **Key Interaction:** Click data point → drill-down to click list
- **Friction Resolved:** #3 — تحليلات مفصلة

## Screen: QR Code Designer (maps to Journey Stage: QR)
- **Layout:** Left panel — customization options, right panel — live QR preview
- **Components:** Color picker, logo upload, shape selector (square/rounded/dots), size slider, download button
- **States:** Default (standard QR) | Editing (customizing) | Loading (generating) | Error (generation fail)
- **Key Interaction:** Change color → QR updates instantly → click download → choose format (PNG/SVG/PDF)
- **Friction Resolved:** #2 — QR Code مدمج وقابل للتخصيص

## Screen: Domains Settings (maps to Journey Stage: Customize)
- **Layout:** List of custom domains with verification status + add domain form
- **Components:** Domain row (name, status badge, created date, DNS instructions), add domain modal
- **States:** Empty (no domains) | Loading (verification) | Error (DNS not set) | Verified (green check)
- **Key Interaction:** Add domain → DNS instructions modal → verify → ready to use
- **Friction Resolved:** #1 — نطاق مخصص

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| URL Input | Large, with paste icon | empty/valid/invalid/loading | URL validation, auto-detect |
| Slug Input | Text input + status icon | empty/checking/available/taken | debounce 500ms, suggest alternatives |
| Result Card | Success display with copy | default/copied | big URL display, copy button with check |
| QR Preview | Square SVG | default/customizing/loading/error | 200×200 default, real-time update |
| Chart | Line, Pie, Bar, Geo | loading/empty/error/data | responsive, tooltip on hover |
| Domain Row | Name + status + date | pending/verified/failed | DNS instructions expandable |
| Copy Button | With tooltip | idle/copied | "Copied!" toast feedback |
| Export Dropdown | CSV, PDF, PNG (QR) | closed/open | format selection, date range |
