# HANDOFFS (ticket queue) — SAAS-071

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
task: Discovery — user research, journey mapping, problem validation for LegalConsult
consumes: docs/PRD.md
expected: docs/PERSONAS.md + docs/JOURNEY_MAP.md
route: opus-4-8 · high · lite
status: completed

## TKT-003 · gate 2
from: sofi-journey-architect
to:   ui-ux-designer
task: Design — prototype spec, accessibility matrix, design system for LegalConsult
consumes: docs/PERSONAS.md + docs/JOURNEY_MAP.md
expected: docs/PROTOTYPE_SPEC.md + docs/A11Y_MATRIX.md + docs/DESIGN_SYSTEM.md
route: sonnet-4-8 · medium · full
status: completed

## TKT-004 · gate 3
from: ui-ux-designer
to:   principal-system-architect
task: Architecture — system design, data flow, stack decisions for LegalConsult
consumes: all docs/ + _context/
expected: docs/ARCHITECTURE.md
route: opus-4-8 · high · lite
status: open
