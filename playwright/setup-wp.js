const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function takeScreenshot(page, screenshotName) {
  const screenshotPath = `/app/screenshots/${screenshotName}`;
  await page.screenshot({ path: screenshotPath });
  console.log(`Screenshot saved: ${screenshotPath}`);
}

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    recordVideo: {
      dir: '/app/videos/',
    },
  });
  const page = await context.newPage();

  const wordpressUrl = process.env.WORDPRESS_URL;
  const siteTitle = process.env.WORDPRESS_TITLE;
  const adminUser = process.env.WORDPRESS_ADMIN_USER;
  const adminPassword = process.env.WORDPRESS_ADMIN_PASSWORD;
  const adminEmail = process.env.WORDPRESS_ADMIN_EMAIL;

  // Navigate to the language selection page
  await page.goto(`${wordpressUrl}/wp-admin/install.php`);

  await takeScreenshot(page, 'language-selection.png');

  // Click the 'Continue' button to proceed with the default language
  await page.click('#language-continue');

  // Navigate to the installation page
  // await page.goto(`${wordpressUrl}/wp-admin/install.php`);

  await page.fill('#weblog_title', siteTitle);
  await page.fill('#user_login', adminUser);
  await page.fill('#pass1-text', adminPassword);
  await page.fill('#admin_email', adminEmail);
  await page.click('#submit');

  await page.waitForSelector('table.install-success');

  await browser.close();

  // Save video recording to the project directory
  const video = await context.video();
  if (video) {
    const localVideoPath = path.join(__dirname, '..', 'videos', path.basename(video.path()));
    fs.copyFileSync(video.path(), localVideoPath);
    console.log(`Video saved to: ${localVideoPath}`);
  }
})();

