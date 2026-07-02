# DECISIONS (ADR log) — SAAS-001
> One entry per irreversible choice.

## DEC-001: Production Domain Registration
- **Date:** 2026-06-25
- **Decision:** Use `tasksyncpro.com` as the production domain with wildcard SSL `*.tasksyncpro.com`
- **Rationale:** Single domain simplifies cookie management (SESSION_DOMAIN), SSL management (single wildcard cert), and Cloudflare WAF configuration. Subdomains `api`, `ws`, `cdn` for service separation without multi-domain complexity.
- **Consequences:** All cookies share `.tasksyncpro.com` scope. SESSION_DOMAIN must be set correctly to avoid cross-subdomain auth issues. CORS configuration restricted to `tasksyncpro.com` and `api.tasksyncpro.com`.
- **Status:** ✅ Accepted

## DEC-002: Production Deployment Strategy — Blue/Green
- **Date:** 2026-06-25
- **Decision:** Use Blue/Green deployment with two PHP-FPM containers behind Nginx upstream switching
- **Rationale:** Zero-downtime deploys, instant rollback by switching Nginx upstream, same host avoids DNS propagation delay. Simpler than canary for single-server topology.
- **Consequences:** Requires 2x PHP-FPM resource allocation (1GB RAM total for both). Nginx config must support upstream switching. Rollback is sub-second (Nginx reload).
- **Status:** ✅ Accepted

## DEC-003: Production File Storage — Cloudflare R2
- **Date:** 2026-06-25
- **Decision:** Use Cloudflare R2 (S3-compatible) for production file storage instead of MinIO or DigitalOcean Spaces
- **Rationale:** R2 has no egress fees, integrates with Cloudflare CDN, S3-compatible SDK works with Laravel Flysystem. Public bucket + CDN CNAME for asset serving.
- **Consequences:** R2 endpoint format differs from standard S3. Must set `AWS_USE_PATH_STYLE_ENDPOINT=false`. CDN cache invalidation via Cloudflare API.
- **Status:** ✅ Accepted

## DEC-004: Production Email — Mailgun/SES dual option
- **Date:** 2026-06-25
- **Decision:** Configure SMTP as default (Mailgun) with SES option commented in .env.production
- **Rationale:** Mailgun provides better deliverability for MENA region (Arabic emails, Saudi gateways). SES fallback if AWS region is preferred.
- **Consequences:** SPF/DKIM/DMARC records must be configured for Mailgun domain. SMTP credentials needed with rate limits.
- **Status:** ✅ Accepted

## DEC-005: Production Error Tracking — Sentry
- **Date:** 2026-06-25
- **Decision:** Use Sentry for production error tracking with 0.25 traces sample rate, 0.10 profiles sample rate
- **Rationale:** Sentry provides Laravel SDK with automatic error grouping, release tracking, and performance tracing. 0.25 sample rate balances cost vs coverage for MVP scale.
- **Consequences:** Monthly cost scales with events. Must configure Sentry release tracking for each deploy. PII handling requires `send_default_pii=true` awareness.
- **Status:** ✅ Accepted

## DEC-006: Production Rate Limiting
- **Date:** 2026-06-25
- **Decision:** Enforce rate limits at both Laravel (API) and Cloudflare (edge) layers
- **Rationale:** Defense in depth — Cloudflare blocks volumetric attacks at edge, Laravel rate limits prevent application-level abuse. Different limits per endpoint type (auth=5/min, API=60/min).
- **Consequences:** Must ensure 429 responses include Retry-After header. Cloudflare rate limits have 10s granularity; Laravel limits use 1-min windows. Coordinated to avoid double-blocking.
- **Status:** ✅ Accepted
