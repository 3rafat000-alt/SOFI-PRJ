// Thin backend API client for cross-tier verification (Node 18+ global fetch).
// Used to drive the real API directly and to assert that admin/mobile actions agree with the backend.
const cfg = require('./config.cjs');
const BASE = cfg.baseURL + cfg.apiBase;

async function req(method, p, { token, body, headers } = {}) {
  const h = {
    Accept: 'application/json',
    ...(body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: 'Bearer ' + token } : {}),
    ...(headers || {}),
  };
  let res, json = null, text = null;
  try {
    res = await fetch(BASE + p, { method, headers: h, body: body ? JSON.stringify(body) : undefined });
    text = await res.text();
    try { json = JSON.parse(text); } catch (_) {}
    return { status: res.status, ok: res.ok, json, text };
  } catch (e) {
    return { status: 0, ok: false, json: null, text: String(e.message), networkError: true };
  }
}

// Returns { token, user, res } — tolerates several token field names.
// Memoised per role for the lifetime of one `node run.cjs` (all suites share the process),
// so the strict `throttle:auth` limiter isn't tripped by re-logging-in in every suite.
const _tokenCache = {};
async function login(who = 'user1', { force = false } = {}) {
  if (!force && _tokenCache[who]) return _tokenCache[who];
  const c = cfg.creds[who];
  const res = await req('POST', '/auth/login', { body: { email: c.email, password: c.password } });
  const d = (res.json && res.json.data) || {};
  const token = d.token || d.access_token || (d.tokens && d.tokens.access) || (res.json && res.json.token) || null;
  const out = { token, user: d.user || null, res };
  if (token) _tokenCache[who] = out;
  return out;
}

module.exports = { req, login, BASE };
