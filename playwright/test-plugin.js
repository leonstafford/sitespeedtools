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
 let browser;
 let context;
 try {
    browser = await chromium.launch();
    context = await browser.newContext({
      recordVideo: {
        dir: '/app/videos/',
      },
    });
    const page = await context.newPage();

    const wordpressUrl = process.env.WORDPRESS_URL;
    const pluginName = process.env.PLUGIN_NAME;
    const adminUser = process.env.WORDPRESS_ADMIN_USER;
    const adminPassword = process.env.WORDPRESS_ADMIN_PASSWORD;

    await page.goto(`${wordpressUrl}/wp-login.php`);

    await takeScreenshot(page, 'login.png');

    await page.fill('#user_login', adminUser);
    await page.fill('#user_pass', adminPassword);

    await takeScreenshot(page, 'userpassentered.png');

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

    // Save video recording to the project directory
    const video = await context.newVideo();
    if (video) {
      const localVideoPath = path.join(__dirname, '..', 'videos', path.basename(await video.path()));
      fs.copyFileSync(await video.path(), localVideoPath);
      console.log(`Video saved to: ${localVideoPath}`);
    }

    await context.close();
    await browser.close();
  } catch (error) {
    console.error('Error in test-plugin.js:', error);
    await context.close();
    await browser.close();
    process.exit(1);
  }
})();

