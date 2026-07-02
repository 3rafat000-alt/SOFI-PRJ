/**
 * SYRH Test Config — Central settings for all test suites.
 * "الدقيق البشر" mode: human-speed interactions with realistic delays.
 */
const path = require('path');
const ROOT = __dirname;

// Detect CI — disable human delays in CI for speed
const CI = !!process.env.CI;

module.exports = {
  // Playwright path (global install)
  PW: process.env.PW_PATH || path.resolve(require('child_process')
    .execSync('which playwright').toString().trim(), '../../lib/node_modules/playwright'),

  // App
  baseURL: process.env.BASE_URL || 'http://127.0.0.1:8004',  // Laravel backend (Vite proxies /api here)
  frontendURL: process.env.FRONTEND_URL || 'http://127.0.0.1:5173', // Vite dev server
  apiBase: '/api',

  // Locale
  locale: 'ar',  // default test locale

  // Viewport
  viewport: { width: 1440, height: 900 },

  // Human simulation — "الدقيق البشري"
  human: {
    enabled: !CI,               // Disable in CI for speed
    typingDelay: { min: 60, max: 160 },   // ms between keystrokes
    clickDelay: { min: 100, max: 400 },   // hesitation before click
    navigationDelay: { min: 300, max: 1000 }, // wait after page load
    actionDelay: { min: 400, max: 1500 },  // wait between actions
    scrollChance: 0.3,          // 30% chance to scroll randomly between actions
    mouseMoveChance: 0.2,       // 20% chance to wiggle mouse
  },

  // Timeouts (ms)
  timeouts: {
    navigation: 30000,
    element: 10000,
    assertion: 5000,
    action: 8000,
  },

  // Paths
  paths: {
    root: ROOT,
    artifacts: path.join(ROOT, 'artifacts'),
    video: path.join(ROOT, 'artifacts', 'video'),
    trace: path.join(ROOT, 'artifacts', 'trace'),
    shots: path.join(ROOT, 'artifacts', 'shots'),
    state: path.join(ROOT, 'artifacts', 'state'),
    reports: path.join(ROOT, 'reports'),
  },

  // Test accounts (must exist in DB — seeded by backend)
  creds: {
    admin: {
      email: 'admin@syrh.com',
      password: 'password',
      name: 'Admin',
      kind: 'admin',
    },
    agency: {
      email: 'agency@syrh.com',
      password: 'password',
      name: 'وكالة البيت المثالي',
      kind: 'agency',
    },
    user: {
      email: 'user@syrh.com',
      password: 'password',
      name: 'أحمد محمد',
      kind: 'user',
    },
  },

  // Search terms (real data expected in dev DB)
  search: {
    governorate: 'دمشق',
    propertyType: 'شقة',
    minPrice: '50000',
    maxPrice: '200000',
  },
};
