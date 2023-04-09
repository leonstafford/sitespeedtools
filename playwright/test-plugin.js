const { chromium } = require('playwright');
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const axios = require('axios');
const Client = require('ssh2').Client;

const sshConn = new Client();

sshConn.on('ready', () => {
  console.log('SSH connection established');
  
  sshConn.exec('wp --allow-root plugin list', (err, stream) => {
    if (err) throw err;
    
    stream.on('close', (code, signal) => {
      console.log('wp plugin list command exited with code', code);
      sshConn.end();
    }).on('data', (data) => {
      console.log('stdout:', data.toString());
    }).stderr.on('data', (data) => {
      console.log('stderr:', data.toString());
    });
  });
});


sshConn.connect({
  host: 'wordpress',
  port: 22,
  username: 'root',
  password: 'rootpassword'
});

async function getActivePlugins() {
  try {
    const apiUser = process.env.WORDPRESS_ADMIN_USER;
    const apiPassword = process.env.WORDPRESS_ADMIN_PASSWORD;
    // const response = await axios.get('http://wordpress/wp-json/wp/v2/plugins?status=active', {
    const response = await axios.get('http://wordpress/wp-json/wp/v2/users/me', {
      auth: {
        username: apiUser,
        password: apiPassword
      }
    });
    const activePlugins = response.data;
    console.log('RESPONSEDATA');
    console.log(activePlugins);
    return activePlugins;
  } catch (error) {
    console.error(error);
    throw new Error('Failed to retrieve active plugins from WordPress');
  }
}

async function runWpCliCommand(command) {
  return new Promise((resolve, reject) => {
    exec(command, (error, stdout, stderr) => {
      if (error) {
        reject(error);
      } else if (stderr) {
        reject(stderr);
      } else {
        resolve(stdout);
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
    // const pluginRowSelector = `tr[data-slug="${pluginName}"]`;
    // await page.waitForSelector(pluginRowSelector);

    // get active plugins from WP container's Rest API
    const activePlugins = await getActivePlugins();
    console.log('Active plugins:', activePlugins);

    // const activateButtonSelector = `${pluginRowSelector} a[href*="action=activate"]`;
    const activateButtonSelector = `#activate-${pluginName}`;
    await page.click(activateButtonSelector);
    // await page.waitForSelector(`${pluginRowSelector}.active`);

    const deactivateButtonSelector = `#deactivate-${pluginName}`;
    await page.waitForSelector(deactivateButtonSelector);


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

