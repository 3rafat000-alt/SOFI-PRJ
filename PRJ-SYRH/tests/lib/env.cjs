/**
 * Environment pre-check — verify backend is reachable, DB seeded, accounts exist.
 * Run before any test suite to fail fast on infrastructure issues.
 */
const cfg = require('../config.cjs');

async function check() {
  const ok = [];
  const fail = [];

  // 1. Backend reachable
  try {
    const res = await fetch(`${cfg.baseURL}/api/health`, { signal: AbortSignal.timeout(5000) });
    if (res.ok) ok.push(`Backend reachable at ${cfg.baseURL}`);
    else fail.push(`Backend returned ${res.status} at /api/health`);
  } catch (e) {
    fail.push(`Backend unreachable at ${cfg.baseURL}: ${e.message}`);
  }

  // 2. Vite dev server reachable
  try {
    const res = await fetch(cfg.frontendURL, { signal: AbortSignal.timeout(5000) });
    if (res.ok) ok.push(`Frontend reachable at ${cfg.frontendURL}`);
    else fail.push(`Frontend returned ${res.status}`);
  } catch (e) {
    fail.push(`Frontend unreachable at ${cfg.frontendURL}: ${e.message}`);
  }

  // 3. Test accounts exist
  for (const [role, cred] of Object.entries(cfg.creds)) {
    try {
      const res = await fetch(`${cfg.baseURL}/api/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: cred.email, password: cred.password }),
        signal: AbortSignal.timeout(5000),
      });
      if (res.ok) ok.push(`Account '${role}' (${cred.email}) — OK`);
      else fail.push(`Account '${role}' (${cred.email}) — login failed (${res.status})`);
    } catch (e) {
      fail.push(`Account '${role}' (${cred.email}) — unreachable: ${e.message}`);
    }
  }

  return { ok, fail, passed: fail.length === 0 };
}

module.exports = { check };
