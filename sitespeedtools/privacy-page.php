<?php

function sst_privacy_page() {

    ?>

    <div class="wrap">
        <h1>Site Speed Tools</h1>
        <h2>Privacy Policy</h2>
        <p>Please review and agree to the terms on this page in order for Site Speed Tools to Speed Test your site.</p>

        <p>Site Speed Tools is a service that provides a performance testing for your website. We collect the minimal amount of information to help diagnose and fix your site's performance.</p>

        <p>Information we will collect from your site if permitted:</p>

        <h3>Site related information</h3>
        <ul>
            <li>Your Site URL</li>
            <li>Information about your installed plugins and themes</li>
            <li>Information about your number of posts and pages</li>
            <li>Other information about your WP site, such as permalink structure</li>
            <li>Any of the Site Speed Tools settings you configure on the Settings page</li>
        </ul>

        <h3>Emails</h3>
        <p>We also collect your email address in order to send you the results of your Speed Test.</p>

        <h3>Services we use</h3>
        <p>Site Speed Tools uses the following third party services:</p>
        <ul>
            <li><a href="https://www.cloudflare.com/privacypolicy/" target="_blank">Cloudflare</a></li>
            <li><a href="https://www.google.com/policies/privacy/" target="_blank">Google</a></li>
        </ul>

        <p>In order to use Site Speed Tools, please review and agree to the terms of this Privacy Policy.</p>
        
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="sst_accept_privacy_policy">
            <!-- show Accept Privacy Policy button only if the user has not already accepted, else show a message that they can revoke their consent by resetting the plugin's settings on Settings page -->
            <?php
                $options = get_option('sst_settings');
                $privacy_policy_accepted = isset($options['sst_privacy_policy_accepted']) ? $options['sst_privacy_policy_accepted'] : false;
                if (! $privacy_policy_accepted) {
                    submit_button('Accept Privacy Policy');
                } else {
                    // green checkbox dashicon, float left
                    echo "<span class='dashicons dashicons-yes' style='color: #46b450; float: left;'></span>";
                    echo "<p class='description'>You have already accepted the Privacy Policy. You can revoke your consent by resetting the plugin's settings on the <a href='" . admin_url('admin.php?page=site_speed_tools_settings') . "'>Settings page</a>.</p>";
                }
            ?>
        </form>
    </div>

<?php

}
