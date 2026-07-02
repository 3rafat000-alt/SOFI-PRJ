/**
 * Human simulation helpers — "الدقيق البشري"
 * Realistic delays between actions: typing, clicking, scrolling, waiting.
 * Configurable via cfg.human — disabled in CI.
 */
const { randomInt, randomFloat } = require('./utils.cjs');

/**
 * Create human-simulation API bound to a config + page context.
 * @param {object} cfg - test config (cfg.human block)
 * @param {import('playwright').Page} page
 */
function createHuman(cfg, page) {
  const h = cfg.human;
  if (!h.enabled) {
    // No-op passthrough when disabled
    return {
      type: async (loc, text, opts) => {
        const el = typeof loc === 'string' ? page.locator(loc) : loc;
        await el.fill(text);
      },
      click: async (loc) => {
        const el = typeof loc === 'string' ? page.locator(loc) : loc;
        await el.click();
      },
      wait: async () => {},
      scroll: async () => {},
      mouseMove: async () => {},
      select: async (loc, value) => {
        const el = typeof loc === 'string' ? page.locator(loc) : loc;
        await el.selectOption(value);
      },
      navigate: async (url) => { await page.goto(url); },
    };
  }

  /** Random delay in range */
  function delay(range) {
    return randomInt(range.min, range.max);
  }

  /** Pause for human-like wait */
  async function wait(range) {
    if (!range) range = h.actionDelay;
    await page.waitForTimeout(delay(range));
  }

  /** Maybe scroll randomly */
  async function maybeScroll() {
    if (Math.random() < h.scrollChance) {
      await page.evaluate((delta) => {
        window.scrollBy({ top: delta, behavior: 'smooth' });
      }, randomInt(-150, 150));
      await page.waitForTimeout(200);
    }
  }

  /** Maybe move mouse in a natural arc */
  async function maybeMoveMouse() {
    if (Math.random() < h.mouseMoveChance) {
      const vp = page.viewportSize() || { width: 1440, height: 900 };
      await page.mouse.move(randomInt(0, vp.width), randomInt(0, vp.height));
      await page.waitForTimeout(50);
    }
  }

  return {
    /** Type text character by character with realistic delays */
    type: async (loc, text, opts = {}) => {
      const el = typeof loc === 'string' ? page.locator(loc) : loc;
      await el.click();
      await wait(h.clickDelay);
      // Clear existing content
      await el.fill('');
      await wait({ min: 50, max: 150 });
      // Type char by char
      for (const char of text.split('')) {
        await el.press(char);
        await page.waitForTimeout(delay(h.typingDelay));
        // Occasional pause between words
        if (char === ' ') {
          await page.waitForTimeout(randomInt(150, 400));
        }
      }
      await maybeMoveMouse();
    },

    /** Click with hesitation — like a human deciding where to click */
    click: async (loc) => {
      const el = typeof loc === 'string' ? page.locator(loc) : loc;
      await maybeScroll();
      await wait(h.clickDelay);
      await el.scrollIntoViewIfNeeded();
      await page.waitForTimeout(50);
      // Move mouse to element gradually
      const box = await el.boundingBox();
      if (box) {
        const steps = randomInt(3, 8);
        for (let i = 1; i <= steps; i++) {
          await page.mouse.move(
            box.x + box.width * (i / steps),
            box.y + box.height * (i / steps),
          );
          await page.waitForTimeout(randomInt(10, 30));
        }
      }
      await el.click();
      await maybeMoveMouse();
    },

    /** Wait between actions */
    wait: async (range) => {
      if (range) await wait(range);
      else await wait(h.actionDelay);
    },

    /** Random scroll */
    scroll: async () => {
      await maybeScroll();
    },

    /** Move mouse randomly */
    mouseMove: async () => {
      await maybeMoveMouse();
    },

    /** Select option from dropdown like a human */
    select: async (loc, value) => {
      const el = typeof loc === 'string' ? page.locator(loc) : loc;
      await el.click();
      await wait(h.clickDelay);
      await el.selectOption(value);
      // Human lingers after selecting
      await wait({ min: 200, max: 500 });
    },

    /** Navigate with post-load pause */
    navigate: async (url) => {
      await page.goto(url, { waitUntil: 'networkidle', timeout: cfg.timeouts.navigation });
      // Human pauses after page loads — reads the screen
      await page.waitForTimeout(delay(h.navigationDelay));
    },

    /** Wait for a specific time range */
    waitFor: async (ms) => {
      await page.waitForTimeout(ms);
    },
  };
}

module.exports = { createHuman };
