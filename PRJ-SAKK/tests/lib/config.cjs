// Central test config — single source of truth for the whole suite.
const path = require('path');
const ROOT = path.resolve(__dirname, '..');

module.exports = {
  // Global Playwright lib (no @playwright/test runner installed — we drive the lib directly).
  PW: process.env.PW_PATH || '/home/es3dlll/.local/share/npm-global/lib/node_modules/playwright',

  baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
  apiBase: '/api/v1',
  locale: 'ar-SY',
  viewport: { width: 1440, height: 900 },

  // Headed video is heavier; keep headless for CI but real chromium (not headless-shell) so the UI renders true.
  headless: process.env.HEADED ? false : true,

  paths: {
    root: ROOT,
    artifacts: path.join(ROOT, 'artifacts'),
    video: path.join(ROOT, 'artifacts', 'video'),
    shots: path.join(ROOT, 'artifacts', 'shots'),
    trace: path.join(ROOT, 'artifacts', 'trace'),
    state: path.join(ROOT, 'artifacts', 'state'),
    reports: path.join(ROOT, 'reports'),
  },

  // Seeded accounts (backend/database/seeders/DatabaseSeeder.php).
  creds: {
    admin: { email: 'admin@sakk.com', password: 'password', kind: 'admin' },
    user1: { email: 'ahmad@test.com', password: 'password', kind: 'user', note: 'L2 / $500' },
    user2: { email: 'sara@test.com',  password: 'password', kind: 'user', note: 'L1 / $100' },
  },
};
