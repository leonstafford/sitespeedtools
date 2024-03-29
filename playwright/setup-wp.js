const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function waitForDBReady(page) {
  let maxRetries = 20;
  while (maxRetries > 0) {
    try {
      await page.goto('http://wordpress', { waitUntil: 'networkidle' });
      if (await page.$('.language-chooser') !== null) {
        console.log('Database is ready. Continuing with setup...');
        return;
      }
      throw new Error('DB connection error screen found');
    } catch (error) {
      console.log('Error connecting to the database/WP installer. Retrying...');
      maxRetries--;
      await new Promise((resolve) => setTimeout(resolve, 3000));
    }
  }
  throw new Error('Unable to establish a connection to the database/WP installer');
}

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
    const siteTitle = process.env.WORDPRESS_TITLE;
    const adminUser = process.env.WORDPRESS_ADMIN_USER;
    const adminPassword = process.env.WORDPRESS_ADMIN_PASSWORD;
    const adminEmail = process.env.WORDPRESS_ADMIN_EMAIL;

    // Navigate to the language selection page
    await page.goto(`${wordpressUrl}/wp-admin/install.php`, { waitUntil: 'networkidle' });

    await waitForDBReady(page);

    await takeScreenshot(page, 'language-selection.png');

    // Click the 'Continue' button to proceed with the default language
    await page.click('#language-continue');

    // Navigate to the installation page
    // await page.goto(`${wordpressUrl}/wp-admin/install.php`);

    await takeScreenshot(page, 'after lang continue.png');

    await page.fill('#weblog_title', siteTitle);
    await page.fill('#user_login', adminUser);
    await page.fill('#pass1', adminPassword);
    await page.fill('#admin_email', adminEmail);
    await page.click('#submit');

    await page.waitForSelector('table.install-success');

    // Save video recording to the project directory
    const videoFilePath = path.join('/app/videos/', `${context._id}.webm`);

    if (fs.existsSync(videoFilePath)) {
      const localVideoPath = path.join(__dirname, '..', 'videos', path.basename(videoFilePath));
      fs.copyFileSync(videoFilePath, localVideoPath);
      console.log(`Video saved to: ${localVideoPath}`);
    }

    await context.close();
    await browser.close();
  } catch (error) {
    console.error('Error in setup-wp.js:', error);
    await context.close();
    await browser.close();
    process.exit(1);
  }
})();

