# Gate 1+2 Output Template — Per Project

## Files to create/update per project:

### 1. `docs/PERSONAS.md` — UX Research (Gate 1)
```markdown
# PERSONAS — <Project Name> (SAAS-XXX)
> Owner: UX Researcher · Gate 1

## Primary Persona: [Name]
- **Role:** [Job title]
- **Context:** [Daily environment, tools used]
- **Goals:** [What they want to achieve]
- **Frustrations:** [Current pains]
- **JTBD:** [Job To Be Done — one sentence]
- **Digital fluency:** [Low/Medium/High]

## Secondary Persona: [Name]
- ... (same structure)

## Pain/Gain Table
| Pain | Severity | Gain of Solving | Priority |
|------|----------|-----------------|----------|

## Competitor Comparison
| Competitor | Strengths | Weaknesses | Gap for Us |
|------------|-----------|------------|------------|
```

### 2. `docs/JOURNEY_MAP.md` — Journey Architecture (Gate 1)
```markdown
# JOURNEY MAP — <Project Name> (SAAS-XXX)
> Owner: Journey Architect · Gate 1 · Persona: [Primary]

## Flow (Mermaid)
```mermaid
flowchart LR
  [trigger] --> [discover] --> [act] --> [confirm] --> [goal]
  [act] -.->[error]-->[recover]-->[confirm]
```

## Stage Annotations
| Stage | User Action | Goal | Emotion | Friction | Screen |
|-------|-------------|------|---------|----------|--------|

## Ranked Friction Log
1. [High] ...
2. [Med] ...
3. [Low] ...

**Rule:** Every later feature MUST trace to a stage above.
```

### 3. `docs/PROTOTYPE_SPEC.md` — UI/UX Design (Gate 2)
```markdown
# PROTOTYPE SPEC — <Project Name> (SAAS-XXX)
> Owner: UI/UX Designer · Gate 2

## Screen: [Name] (maps to Journey Stage: [Stage])
- **Layout:** [Brief description]
- **Components:** [List]
- **States:** Empty | Loading | Error | Edge case
- **Key Interaction:** [What user does]
- **Friction Resolved:** [#]

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|

...one Screen section per journey stage.
```

### 4. `docs/A11Y_MATRIX.md` — Accessibility (Gate 2)
```markdown
# WCAG 2.2 AA Matrix — <Project Name>
| Criteria | Target | Status | Notes |
|----------|--------|--------|-------|
| Contrast ≥ 4.5:1 | Pass | ✅ | |
| Focus visible | Pass | ✅ | |
| Screen-reader labels | All inputs | ✅ | |
```

### 5. `docs/DESIGN_SYSTEM.md` — Visual Identity (Gate 2)
```markdown
# Design System — <Project Name>
> Visual identity, color palette, typography, spacing, and brand elements.

## Brand Concept
- **Name meaning:** [Arabic/English]
- **Logo concept:** [Icon + typography style]
- **Brand personality:** [3-5 adjectives]

## Color Palette
- **Primary:** `#HEX` — [usage]
- **Secondary:** `#HEX` — [usage]  
- **Accent:** `#HEX` — [usage]
- **Neutral:** `#HEX` — [usage]
- **Semantic:** Success `#HEX` · Warning `#HEX` · Error `#HEX`

## Typography
- **Headings:** [Font] — sizes: 24/20/18/16px
- **Body:** [Font] — 14px
- **Arabic:** [Arabic font] — support

## Spacing
- Base unit: 4px/8px
- Padding: 16/24/32px
- Border radius: 8px

## Iconography
- Style: [Outline/Filled]
- Library: [Lucide/Material]

## Component Tokens
| Component | Style | States |
|-----------|-------|--------|
| Button Primary | bg-primary, white text, 8px radius | hover/active/disabled/loading |
| Input Field | border 1px, 12px padding | focus/error/disabled |
```

### 6. Update `_context/HANDOFFS.md`
Mark TKT-002 completed (Gate 1), TKT-003 completed (Gate 2), open TKT-004 → `principal-system-architect`

### 7. Update `_context/STATE.md`
Update gate to 2, active agent to ui-ux-designer, status to completed

---

## Instructions:
For each project in your batch:
1. Read `docs/PRD.md` and `_context/CONTEXT.md` for context
2. Create/update all 7 files above
3. Persona content in Arabic, technical specs in English
4. Each file must be thorough and specific to the industry/sector
5. Design system colors should match the industry (health=blues/greens, energy=orange/yellow, etc.)
