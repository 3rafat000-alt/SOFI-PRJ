# HANDOFFS (ticket queue) — SAAS-041

## TKT-001 · gate 0
from: ceo-sofi
to:   chief-product-strategist
task: produce Project_Blueprint.md + 5 deep questions.
consumes: user request
expected: docs/PRD.md
route: opus-4-8 · high · lite
status: completed

## TKT-002 · gate 1
from: chief-product-strategist
to:   sofi-journey-architect
task: map user journey and define feature epics for Gate 2 design review.
consumes: docs/PRD.md
expected: docs/PERSONAS.md, docs/JOURNEY_MAP.md
route: sonnet-4-0 · high · lite
status: completed

## TKT-003 · gate 2
from: sofi-journey-architect
to:   ui-ux-designer
task: design screens, accessibility matrix, design system for Gate 3 architecture review.
consumes: docs/PERSONAS.md, docs/JOURNEY_MAP.md
expected: docs/PROTOTYPE_SPEC.md, docs/A11Y_MATRIX.md, docs/DESIGN_SYSTEM.md
route: sonnet-4-0 · high · lite
status: completed

## TKT-004 · gate 3
from: ui-ux-designer
to:   principal-system-architect
task: design system architecture, data model finalization, stack selection, API contracts for Gate 4 build.
consumes: docs/PROTOTYPE_SPEC.md, docs/PRD.md
expected: docs/ARCHITECTURE.md, docs/API_SPEC.md
route: opus-4-8 · high · full
status: open
