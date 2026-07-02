# Ops Runbook Note — reverse-proxy trust for php-fpm (CORRECTED)

**Project:** PRJ-SAKK — SAKK Payment / Wallet Platform
**Date:** 2026-07-01 (superseded verdict corrected same day)
**Owner (this note):** Security & Compliance Architect · **Executor of the guardrail:** DevOps / Cloud Lead
**Surface:** `backend/bootstrap/app.php` — `$middleware->trustProxies(...)`
**Classification:** Internal · infrastructure security guardrail · this project only
**Status:** Fixed (committed) — trust X-Forwarded-For only, `at:` scoped to loopback.

---

## CORRECTION NOTICE — my prior verdict was wrong

An earlier version of this note concluded that `trustProxies(at: '*')` was "correct and
required" and closed it as accepted-risk, on the reasoning that the UNIX-socket bind made
`'*'` equivalent to trusting the one local proxy. That accepted-risk verdict is now
**withdrawn.** The prior note was correct that `'*'` is *safe over the loopback socket*,
but it MISSED the actual defect: `'*'` (and any value that trusts the default header set)
also trusts **X-Forwarded-Host and X-Forwarded-Proto**, and in this topology those headers
are poisoned by the local Caddy hop. That is what broke live login on sakk.zanjour.com.
The fix and the accurate mechanism are below.

## What actually happened

1. `at: '*'` trusts the DEFAULT trusted-header set, which includes
   X-Forwarded-Host / -Port / -Proto (Laravel `TrustProxies::getTrustedHeaderNames()`
   default branch — verified in vendor).
2. Caddy's `php_fastcgi` REWRITES X-Forwarded-Host / -Proto to the upstream hop values
   (`localhost` / `http`), overwriting whatever cloudflared sent.
3. Because those headers were trusted, Symfony preferred them over the real request host,
   so every generated URL collapsed to `http://localhost`.
4. On the public host `sakk.zanjour.com`, the CSP (`script-src` / `form-action 'self'`)
   then blocked the localhost asset loads AND the login form POST → symptom:
   **"login button dead."**

The perf agent that first landed a CIDR/`X-Forwarded-For`-only edit had the RIGHT fix but
gave a WRONG mechanism in its comment. Its claim — that `at: '*'` "fails Symfony IpUtils
match because REMOTE_ADDR over the socket is 127.0.0.1, so X-Forwarded-Host is silently
untrusted" — is **FALSE**, and the framework proves it:

## Framework evidence (verified against installed vendor, not memory)

`vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php`:

- Line 83–84: `if ($trustedIps === '*' || $trustedIps === '**') { return
  $this->setTrustedProxyIpAddressesToTheCallingIp($request); }` — `'*'` is **special-cased
  BEFORE any IpUtils call**.
- Line 120–122: that method does
  `$request->setTrustedProxies([$request->server->get('REMOTE_ADDR')], ...)` — it trusts
  `REMOTE_ADDR` (which over the UNIX socket is `127.0.0.1`) **directly, with no IpUtils
  match**.

Therefore `'*'` does NOT fail to trust X-Forwarded-* over a loopback socket. It trusts the
socket peer and then applies the full default header set — including the poisoned
X-Forwarded-Host/Proto. The outage was caused by trusting the WRONG HEADERS, not by any
IpUtils failure. IpUtils never enters the `'*'` path.

## The fix that is shipped (correct)

```php
$middleware->trustProxies(
    at: ['127.0.0.1', '::1'],
    headers: Request::HEADER_X_FORWARDED_FOR,
);
```

Two independent decisions, both correct:

- **`headers: HEADER_X_FORWARDED_FOR` (load-bearing).** Trust X-Forwarded-For ONLY. Do NOT
  trust X-Forwarded-Host / -Proto. This is what fixes the outage: with Host/Proto untrusted,
  Laravel reads the real host+scheme straight from the fastcgi env the tunnel vhost feeds
  (`HTTP_HOST` = public host, `HTTPS` = on), so generated URLs stay on `https://sakk.zanjour.com`
  and clear the CSP.
- **`at: ['127.0.0.1', '::1']` (hardening).** Scope trust to the loopback peer only. Over the
  UNIX socket the connecting peer is always loopback, so this is the tightest correct set.
  The prior CIDR list also included RFC1918 ranges (`10/8`, `172.16/12`, `192.168/16`); those
  never match over the socket and are removed to eliminate unused spoofing surface. `'*'` is
  NOT used even though it would function, because the explicit loopback list is exact and
  future-proof against a bind change.

Real client IP (for rate limiting and audit) is preserved: X-Forwarded-For remains trusted
from the loopback hop.

## The guardrail (do NOT loosen the FPM bind)

1. **Keep php-fpm loopback-bound.** The pool must stay on the UNIX socket
   (`/run/php/php8.4-fpm.sock`) or, if TCP is ever needed, on `127.0.0.1` only. Never bind to
   `0.0.0.0`, a LAN IP, or expose it via a container port-map. This is infra-frozen.
2. **If php-fpm must ever bind beyond loopback, revisit `at:` FIRST.** The current `at:` is
   loopback-only, so a beyond-loopback bind would simply stop trusting a legitimately remote
   Caddy hop rather than open a spoofing vector — but the coupling must still be reviewed
   before any such change ships.
3. **Never re-add X-Forwarded-Host / -Proto to `headers:` while Caddy rewrites them.** Doing so
   reintroduces the `http://localhost` URL collapse and kills login again.
4. **This note is the gate.** Any ticket touching the FPM listener, adding an FPM port-map,
   or fronting the app with an additional proxy/LB must re-open and satisfy this note.

## Verdict

**Perf agent's claim: FALSE (wrong mechanism) — but its FIX was correct and is kept + hardened.**
The login outage was real: `at: '*'` trusted poisoned X-Forwarded-Host/Proto, collapsing URLs
to `http://localhost` and tripping the CSP. The fix (X-Forwarded-For only + loopback `at:`) is
correct and now committed. My prior accepted-risk verdict is **withdrawn** — `'*'` was not
merely a future coupling risk, it was an ACTIVE production login break. Prod login on
sakk.zanjour.com is **NOT at risk** with the shipped fix; it WOULD be broken by any revert to
`'*'` or by re-trusting Host/Proto. `php -l` clean.
