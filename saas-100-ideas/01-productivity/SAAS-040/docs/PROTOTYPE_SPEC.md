# PROTOTYPE SPEC — WikiBase (SAAS-040)
> Owner: UI/UX Designer · Gate 2

## Screen: Page Tree / Wiki Navigation (maps to Journey Stage: Organize)
- **Layout:** 2-column — left sidebar (collapsible page tree), right (content area / page list)
- **Components:** Tree node (icon, name, expand/collapse), breadcrumb bar, search at top
- **States:** Empty (first space) | Loading (tree fetch) | Error (sync fail) | Edge case (500+ pages → virtual tree)
- **Key Interaction:** Click node → open page in right pane / Drag node → reorder in tree
- **Friction Resolved:** #1 — تنظيم هرمي لكل المعرفة

## Screen: WYSIWYG Page Editor (maps to Journey Stage: Write → Link)
- **Layout:** Full editor with slash-command menu — formatting toolbar, content area with breadcrumb
- **Components:** Toolbar (B/I/U/H1-H3/bullet/number/code/blockquote/link/image), slash-command menu, link picker
- **States:** Empty (new page) | Loading (auto-save) | Error (save failed) | Edge case (10k+ words → virtual scroll)
- **Key Interaction:** Type `/` → command palette → select block type / Type `@` → search pages → insert link
- **Friction Resolved:** #3 — وصلات داخلية سهلة

## Screen: Search Results (maps to Journey Stage: Search → Find)
- **Layout:** Full-page search with results list + inline preview panel
- **Components:** Search input (large, top), filter chips (space, tag, author), result cards, highlight preview
- **States:** Empty (no query) | No Results (try different terms) | Loading | Results | Edge case (200+ results → grouped)
- **Key Interaction:** Type query → instant results with highlighted matches → click → preview + navigate
- **Friction Resolved:** #2 — بحث عربي ذكي

## Screen: Version History (maps to Journey Stage: Update)
- **Layout:** Sidebar slide-over — version list (date, author, summary) + diff view on selection
- **Components:** Version row (number, date, author, summary), diff view (side-by-side or unified)
- **States:** Empty (no versions yet) | Loading | Error | Edge case (100+ versions → paginated)
- **Key Interaction:** Click version → show diff with previous (green/red highlight)
- **Friction Resolved:** #4 — سجل إصدارات

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Tree Node | Folder / Page / External link | default/hover/selected/dragover | expand/collapse, context menu |
| Page Content | Full-width rendered page | view/edit/history | responsive, RTL aware |
| Slash Command | Inline menu | closed/open/searching | filter by typing, Enter to insert |
| Link Picker | Search + select | closed/open/loading/searching | search by page title, recent pages |
| Search Result | Card with highlighted match | default/hover/selected | show breadcrumb path, match highlight |
| Version Row | Date + author + summary | default/hover/selected | click to show diff |
| Diff View | Side-by-side / Unified | loading/diff | green additions, red deletions |
| Breadcrumb | Space > Category > Page | interactive | click any segment to navigate |
