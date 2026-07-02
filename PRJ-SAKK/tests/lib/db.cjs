// DB verification helper — the third angle. Runs read/queries through `artisan tinker` so a
// scenario can assert the backend's persisted truth, not just API responses.
const { execFileSync } = require('child_process');
const path = require('path');
const BACKEND = path.resolve(__dirname, '..', '..', 'backend');

// Run a PHP snippet in the app context; return trimmed stdout (echo your result).
// execFileSync (no shell) so PHP `$vars` and backslashes pass through untouched (no shell expansion).
function tinker(php) {
  const out = execFileSync('php', ['artisan', 'tinker', '--execute', php], {
    cwd: BACKEND, encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'], timeout: 30000,
  });
  // tinker may echo a leading PHP warning banner on some setups; take the last non-empty line.
  const lines = out.split('\n').map((l) => l.trim()).filter(Boolean);
  return lines.length ? lines[lines.length - 1] : '';
}

const walletBalance = (id) => parseFloat(tinker(`echo \\DB::table('wallets')->where('id',${id})->value('balance');`) || '0');
const walletAvailable = (id) => parseFloat(tinker(`echo \\DB::table('wallets')->where('id',${id})->value('available_balance');`) || '0');
// Spendable funding: transfers gate on available_balance, so set both to keep the row consistent.
const setWalletBalance = (id, bal) => tinker(`\\DB::table('wallets')->where('id',${id})->update(['balance'=>${bal},'available_balance'=>${bal}]); echo 'ok';`);
const count = (table, where) => parseInt(tinker(`echo \\DB::table('${table}')${where ? `->where(${where})` : ''}->count();`) || '0', 10);

module.exports = { tinker, walletBalance, setWalletBalance, count, BACKEND };
