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
  // sshConn.exec('printenv', (err, stream) => {
  //   if (err) throw err;
  //   
  //   stream.on('close', (code, signal) => {
  //     console.log('printenv command exited with code', code);
  //   }).on('data', (data) => {
  //     console.log('Environment variables:', data.toString());
  //   }).stderr.on('data', (data) => {
  //     console.log('stderr:', data.toString());
  //   });
  // });
});


sshConn.connect({
  // TODO: move SSH dets to env vars
  host: 'wpcli',
  port: 22,
  username: 'root',
  password: 'ubuntu'
});

// help debug scren recording or screenshots by showing element selectors
async function debugHighlightElements(page, containsText) {
  await page.evaluate((containsText) => {
    const style = document.createElement('style');
    style.textContent = `
      .debug-selector {
        position: absolute;
        font-size: 12px;
        color: white;
        background-color: rgba(0, 0, 0, 0.5);
        padding: 2px;
        border-radius: 3px;
        z-index: 9999;
      }
    `;
    document.head.append(style);

    const elements = containsText
      ? document.evaluate(
          `//*[contains(text(), '${containsText}')]`,
          document,
          null,
          XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
          null
        )
      : document.querySelectorAll('*');

    for (let i = 0; i < elements.snapshotLength; i++) {
      const el = containsText ? elements.snapshotItem(i) : elements[i];

      let cssSelector = '';
      if (el.id) {
        cssSelector = `#${el.id}`;
      } else if (el.className && typeof el.className === 'string') {
        cssSelector = '.' + el.className.trim().replace(/\s+/g, '.');
      } else {
        cssSelector = el.tagName.toLowerCase();
      }

      const debugElement = document.createElement('div');
      debugElement.classList.add('debug-selector');
      debugElement.textContent = cssSelector;
      el.style.position = 'relative';
      el.append(debugElement);
    }
  }, containsText);
}

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
    const pluginName = process.env.PLUGIN_NAME; // "sitespeedtools"
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

    // show selectors for all emenetns on the page
    await debugHighlightElements(page, 'Activate');
    await takeScreenshot(page, 'plugins page before activation.png');

    const activateButtonSelector = `#activate-site-speed-tools`;
    await page.waitForSelector(activateButtonSelector);
    await page.click(activateButtonSelector); // Click the element

    const deactivateButtonSelector = `#deactivate-site-speed-tools`;
    await page.waitForSelector(deactivateButtonSelector);
    await takeScreenshot(page, 'plugins page after activation.png');

    const activePluginsAfter = await getActivePluginsSSH();
    console.log('Active plugins after activation:', activePluginsAfter);

    const activatedPlugin = activePluginsAfter.find(plugin => plugin.name === pluginName);

    assert(activatedPlugin, `The plugin "${pluginName}" was not activated`);
    
    // Save video recording to the project directory
    const videoFilePath = path.join('/app/videos/', `${context._id}.webm`);

    if (fs.existsSync(videoFilePath)) {
      const localVideoPath = path.join(__dirname, '..', 'videos', path.basename(videoFilePath));
      fs.copyFileSync(videoFilePath, localVideoPath);
      console.log(`Video saved to: ${localVideoPath}`);
    }

    await context.close();
    await browser.close();
    process.exit(0);
  } catch (error) {
    console.error('Error in test-plugin.js:', error);
    await context.close();
    await browser.close();
    process.exit(1);
  }
})();

