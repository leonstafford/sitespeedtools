const { chromium } = require('playwright');
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const Client = require('ssh2').Client;

const sshConn = new Client();

sshConn.on('ready', async () => {
  console.log('SSH connection established');
  
  // Print all environment variables
  sshConn.exec('printenv', (err, stream) => {
    if (err) throw err;
    
    stream.on('close', (code, signal) => {
      console.log('printenv command exited with code', code);
    }).on('data', (data) => {
      console.log('Environment variables:', data.toString());
    }).stderr.on('data', (data) => {
      console.log('stderr:', data.toString());
    });
  });
});


sshConn.connect({
  // TODO: move SSH dets to env vars
  host: 'wpcli',
  port: 22,
  username: 'root',
  password: 'ubuntu'
});

async function getActivePluginsSSH() {
  return new Promise((resolve, reject) => {
    const cmd = `wp --allow-root --path=/var/www/html plugin list --status=active --format=json`;
    sshConn.exec(cmd, (err, stream) => {
      if (err) {
        reject(err);
      } else {
        let output = '';
        stream.on('data', (data) => {
          output += data.toString();
        }).on('end', () => {
          try {
            const activePlugins = JSON.parse(output);
            resolve(activePlugins);
          } catch (error) {
            reject(error);
          }
        }).stderr.on('data', (data) => {
          reject(new Error(`Error running WP CLI command: ${data.toString()}`));
        });
      }
    });
  });
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

    // Get active plugins via wp-cli using ssh2
    const activePluginsBefore = await getActivePluginsSSH();
    console.log('Active plugins before activation:', activePluginsBefore);

    const activateButtonSelector = `#activate-${pluginName}`;
    await page.click(activateButtonSelector);

    const activePluginsAfter = await getActivePluginsSSH();
    console.log('Active plugins after activation:', activePluginsAfter);

    const activatedPlugin = activePluginsAfter.find(plugin => plugin.name === pluginName);
    assert(activatedPlugin, `The plugin "${pluginName}" was not activated`);

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

