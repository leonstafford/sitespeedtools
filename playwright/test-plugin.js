const { chromium } = require('playwright');
const assert = require('assert');

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  const wordpressUrl = process.env.WORDPRESS_URL;
  const pluginName = process.env.PLUGIN_NAME;

  await page.goto(`${wordpressUrl}/wp-admin/install.php`);
  await page.fill('#weblog_title', 'Test Site');
  await page.fill('#user_login', 'admin');
  await page.fill('#pass1-text', 'password');
  await page.fill('#admin_email', 'test@example.com');
  await page.click('#submit');

  await page.waitForSelector('.wp-signup-complete');

  await page.goto(`${wordpressUrl}/wp-login.php`);
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'password');
  await page.click('#wp-submit');

  await page.waitForSelector('#wpadminbar');

  await page.goto(`${wordpressUrl}/wp-admin/plugins.php`);
  const pluginRowSelector = `tr[data-slug="${pluginName}"]`;
  await page.waitForSelector(pluginRowSelector);

  const activateButtonSelector = `${pluginRowSelector} a[href*="action=activate"]`;
  await page.click(activateButtonSelector);
  await page.waitForSelector(`${pluginRowSelector}.active`);

  const pluginStatus = await page.$eval(pluginRowSelector, (el) => el.className);
  assert(pluginStatus.includes('active'), 'Plugin activation failed');

  await browser.close();
})();
