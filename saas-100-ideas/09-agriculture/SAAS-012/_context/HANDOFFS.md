# HANDOFFS (ticket queue) — SAAS-012
> Project: FarmTech

## TKT-001 · gate 0
from: ceo-sofi
to:   chief-product-strategist
task: produce PRD.md
consumes: batch-02.json
expected: docs/PRD.md
route: opus-4-8 · high · lite
status: completed
completed_at: 2026-06-25
deliverable: docs/PRD.md

## TKT-002 · gate 1
from: chief-product-strategist
to:   ux-researcher
task: produce PERSONAS.md
consumes: docs/PRD.md
expected: docs/PERSONAS.md
route: sonnet-4-8 · high · lite
status: completed
completed_at: 2026-06-25
deliverable: docs/PERSONAS.md

## TKT-003 · gate 1→2
from: ux-researcher
to:   journey-architect
task: produce JOURNEY_MAP.md (Mermaid flow, annotations, friction log)
consumes: docs/PERSONAS.md
expected: docs/JOURNEY_MAP.md
route: sonnet-4-8 · high · lite
status: completed
completed_at: 2026-06-25
deliverable: docs/JOURNEY_MAP.md

## TKT-004 · gate 2
from: journey-architect
to:   ui-ux-designer
task: produce PROTOTYPE_SPEC.md + A11Y_MATRIX.md + DESIGN_SYSTEM.md
consumes: docs/JOURNEY_MAP.md
expected: design docs
route: sonnet-4-8 · high · lite
status: completed
completed_at: 2026-06-25
deliverable: 3 design docs

## TKT-005 · gate 2→3
from: ui-ux-designer
to:   principal-system-architect
task: farm management system architecture — models, irrigation scheduling engine, IoT sensor integration
consumes: all docs/ output
expected: docs/ARCHITECTURE.md
route: opus-4-8 · high · lite
status: open
priority: HIGH
