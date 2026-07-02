# HANDOFFS (ticket queue) — SAAS-095

## TKT-001 · gate 0
from: ceo-sofi
to:   chief-product-strategist
task: produce Project_Blueprint.md + 5 deep questions.
consumes: user request
expected: docs/PRD.md
route: opus-4-8 · high · lite
status: completed
result: docs/PRD.md written

## TKT-002 · gate 1
from: chief-product-strategist
to:   sofi-journey-architect
task: produce Journey Map for SAAS-095 (MobileFix)
consumes: docs/PRD.md, _context/CONTEXT.md
expected: projects/saas-100-ideas/11-services/SAAS-095/docs/JOURNEY_MAP.md
route: sonnet-4-8 · high · full
status: completed
result: Full journey map with Mermaid flow, 6 stage annotations, ranked friction log

## TKT-003 · gate 2
from: chief-product-strategist
to:   ui-ux-designer
task: produce Design Spec for SAAS-095 (MobileFix)
consumes: docs/PRD.md, docs/JOURNEY_MAP.md, _context/CONTEXT.md
expected: docs/PROTOTYPE_SPEC.md, docs/A11Y_MATRIX.md, docs/DESIGN_SYSTEM.md, docs/PERSONAS.md
route: sonnet-4-8 · high · full
status: completed
result: 4 design docs + PERSONAS written — full Gate 2 package

## TKT-004 · gate 3
from: chief-product-strategist
to:   principal-system-architect
task: produce Architecture Spec for SAAS-095 (MobileFix)
consumes: docs/PRD.md, docs/PROTOTYPE_SPEC.md, _context/CONTEXT.md
expected: docs/ARCHITECTURE.md
route: sonnet-4-8 · high · full
status: open
