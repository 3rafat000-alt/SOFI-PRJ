# PROTOTYPE SPEC — NoteSpace (SAAS-036)
> Owner: UI/UX Designer · Gate 2

## Screen: Workspace View (maps to Journey Stage: Organize)
- **Layout:** 2-column — left sidebar (folder tree + tags), right (note list with preview)
- **Components:** Folder tree (collapse/expand), note card (title, preview snippet, date, tags), search bar top
- **States:** Empty (welcome screen) | Loading (skeleton) | Error (sync fail) | Edge case (500+ notes → virtual scroll)
- **Key Interaction:** Drag note → drop into folder → auto-recategorize
- **Friction Resolved:** #5 — تنظيم وإدارة الصلاحيات

## Screen: Rich Text Editor (maps to Journey Stage: Write)
- **Layout:** Full-width editor with floating toolbar — formatting bar at top, content area below
- **Components:** Formatting toolbar (B/I/U/H1/H2/bullet/number/code/blockquote), image upload, table
- **States:** Empty (blank note) | Loading (saving) | Error (save failed → retry) | Edge case (10k+ words → virtual scroll)
- **Key Interaction:** Type `/` → command menu → insert block (heading, image, code, divider)
- **Friction Resolved:** #1 — RTL عربي + محرر متكامل

## Screen: Collaborative Edit (maps to Journey Stage: Collaborate → Edit)
- **Layout:** Same as editor with cursor indicators (colored avatars at cursor positions)
- **Components:** Cursor indicator, presence avatars, conflict toast, sync status icon
- **States:** Loading (connecting WebSocket) | Syncing (green dot) | Offline (red dot) | Conflict (resolve dialog)
- **Key Interaction:** See teammate's cursor moving in real-time → type simultaneously
- **Friction Resolved:** #3 — تحرير تعاوني آني

## Screen: Search Results (maps to Journey Stage: Search)
- **Layout:** Split view — search bar top, results list left, selected note preview right
- **Components:** Search input with filters (folder, tag, date), result card with highlighted match
- **States:** Empty (no results) | Loading | Error | Edge case (200+ results → grouped by folder)
- **Key Interaction:** Type query → instant results → click result → preview in right pane
- **Friction Resolved:** #4 — بحث كامل النص

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Folder Tree | Collapsed/Expanded | default/hover/active/dragover | drag-drop, context menu (rename/delete) |
| Note Card | Default, Pinned, Archived | default/hover/selected | preview 2 lines, relative date |
| Formatting Toolbar | Bold, Italic, H1, H2, Bullet, Number, Code, Quote | active/inactive | responds to selection change |
| Command Menu | Slash-menu (/) | closed/open/searching | filter by typing, Enter to select |
| Cursor Indicator | Colored circle + name label | active/offline/idle | smooth transition, auto-hide after 5s idle |
| Presence Avatars | Stacked avatars top-right | default | +N overflow badge |
| Search Input | Large with icon | focused/typing/results | debounce 300ms, clear button |
| Conflict Dialog | Modal | open/resolving | show diff, accept one or merge |
