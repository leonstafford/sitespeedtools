const { chromium } = require('playwright');
const assert = require('assert');
const fs = require('fs');
const path = require('path');

async function takeScreenshot(page, screenshotName) {
  const screenshotPath = `/app/screenshots/${screenshotName}`;
  await page.screenshot({ path: screenshotPath });
  console.log(`Screenshot saved: ${screenshotPath}`);
}

(async () => {
 try {
    const browser = await chromium.launch();
    const context = await browser.newContext({
      recordVideo: {
        dir: '/app/videos/',
      },
    });
    const page = await context.newPage();

    const wordpressUrl = process.env.WORDPRESS_URL;
    const pluginName = process.env.PLUGIN_NAME;

    await page.goto(`${wordpressUrl}/wp-login.php`);

    await takeScreenshot(page, 'login.png');

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

    // Save video recording to the project directory
    const video = await context.video();
    if (video) {
      const localVideoPath = path.join(__dirname, '..', 'videos', path.basename(video.path()));
      fs.copyFileSync(video.path(), localVideoPath);
      console.log(`Video saved to: ${localVideoPath}`);
    }
  } catch (error) {
    console.error('Error in test-plugin.js:', error);
  } finally {
    await context.close();
    // TODO: should vid sync go in here?
  }
})();

