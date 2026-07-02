# Micro-PRD Template — SOFI AI

> Use this template to produce a complete project spec for each SAAS-XXX idea.
> Target: Laravel API + React Dashboard + Flutter Mobile App.

## Files to create/update per project:

### 1. `docs/PRD.md` — Full Product Requirements Document
Structure:
```markdown
# PRD: <Project Name> (SAAS-XXX)
> Status: Draft | Owner: Chief Product Strategist | Gate: 0

## 1. Executive Summary
- One-liner: what, for whom, why
- Problem statement
- Proposed solution (Laravel API + React Dashboard + Flutter App)

## 2. Market & Opportunity
- Target market size / niche
- Customer segment (B2B / B2C)
- Competitor landscape (3-5 competitors)
- Differentiation

## 3. User Personas
- Primary: name, role, goals, pain points
- Secondary: name, role, goals, pain points
- Admin: dashboard operator

## 4. Features by Platform

### Laravel API (Backend)
- Core domain models
- RESTful endpoints
- Auth & roles
- Notifications (push/email/SMS)

### React Dashboard (Web)
- Admin panel features
- Data visualization
- User management
- Settings & configuration

### Flutter App (Mobile)
- Customer-facing features
- Real-time updates
- Offline capability
- Push notifications

## 5. Data Model (MVP)
- Core entities
- Relationships
- Key fields per entity

## 6. API Endpoints (MVP)
- Full CRUD per resource
- Auth endpoints
- Webhook endpoints if applicable

## 7. User Interface (Screen List)
- Dashboard screens (React)
- Mobile screens (Flutter)
- Screen flow diagrams (text)

## 8. Business Model
- Pricing tiers (monthly)
- Free trial? Y/N
- Target MRR per client: $XX-$XX

## 9. Implementation Plan
- Phase 1 (Weeks 1-2): API + Auth + Core models
- Phase 2 (Weeks 3-4): React Dashboard
- Phase 3 (Weeks 5-6): Flutter App
- Phase 4 (Weeks 7-8): Polish + Testing + Deploy

## 10. Risk & Mitigation
- Technical risks
- Market risks
- Mitigation strategies
```

### 2. Update `_context/CONTEXT.md`
Append full product description, target market, and decisions.

### 3. Update `_context/HANDOFFS.md`
Mark TKT-001 as completed, add TKT-002 for next gate (Journey Architect).

---

## Instructions for subagent:
For each project in your batch:
1. Read the existing `_context/CONTEXT.md` and `README.md`
2. Write `docs/PRD.md` with the full spec
3. Update `CONTEXT.md` with enriched details
4. Update `HANDOFFS.md` — close TKT-001, open TKT-002

Output all files with professional Arabic content (project description in Arabic, technical specs in English).
