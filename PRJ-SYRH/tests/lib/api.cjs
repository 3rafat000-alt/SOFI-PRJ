/**
 * API Client — thin HTTP wrapper with token caching per role.
 * Uses axios-like fetch. No Playwright needed for API-only tests.
 */
const cfg = require('../config.cjs');

// Token cache: role -> { token, expires }
const tokenCache = {};

/**
 * Login as a role and cache the token.
 * @param {string} role - 'admin' | 'agency' | 'user'
 * @returns {Promise<string>} bearer token
 */
async function login(role) {
  const cred = cfg.creds[role];
  if (!cred) throw new Error(`Unknown role: ${role}`);

  if (tokenCache[role]) return tokenCache[role];

  const res = await fetch(`${cfg.baseURL}/api/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ email: cred.email, password: cred.password }),
  });

  if (!res.ok) {
    const body = await res.text();
    throw new Error(`Login failed for ${role} (${res.status}): ${body}`);
  }

  const data = await res.json();
  const token = data.token || data.data?.token;
  if (!token) throw new Error(`No token in login response for ${role}`);

  tokenCache[role] = token;
  return token;
}

/**
 * Make an authenticated API request.
 * @param {string} method - 'GET' | 'POST' | 'PUT' | 'DELETE'
 * @param {string} path - e.g. '/auth/me'
 * @param {object} opts
 * @param {object} [opts.body] - request body
 * @param {string} [opts.token] - bearer token (auto-login if omitted, needs role)
 * @param {string} [opts.role] - role to login as
 * @returns {Promise<{status: number, data: any, headers: Headers}>}
 */
async function request(method, path, opts = {}) {
  const url = `${cfg.baseURL}${path}`;
  const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };

  let token = opts.token;
  if (!token && opts.role) {
    token = await login(opts.role);
  }
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const fetchOpts = { method, headers };
  if (opts.body) fetchOpts.body = JSON.stringify(opts.body);

  const res = await fetch(url, fetchOpts);
  const data = res.headers.get('content-type')?.includes('application/json')
    ? await res.json()
    : await res.text();

  return { status: res.status, data, headers: res.headers };
}

/** Shorthand helpers */
const api = {
  get:    (path, opts) => request('GET', path, opts),
  post:   (path, body, opts) => request('POST', path, { ...opts, body }),
  put:    (path, body, opts) => request('PUT', path, { ...opts, body }),
  delete: (path, opts) => request('DELETE', path, opts),
  login,
};

module.exports = api;
