/**
 * Utility helpers
 */

function randomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function randomFloat(min, max) {
  return Math.random() * (max - min) + min;
}

/** Format timestamp for filenames */
function timestamp() {
  const d = new Date();
  return d.toISOString().replace(/[:.]/g, '-').slice(0, 19);
}

/** Sleep (promise) */
function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

module.exports = { randomInt, randomFloat, timestamp, sleep };
