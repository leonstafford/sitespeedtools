<?php

function sst_speed_test_page() {
    $api_error = get_transient( 'sst_api_error_message' );
    delete_transient( 'sst_api_error_message' );
    ?>
    <div class="wrap">
        <h1>Site Speed Tools</h1>
        <h2>Speed Test</h2>
         <?php
        if ($api_error) {
            echo '<div class="notice notice-error"><p>' . $api_error . '</p></div>';
        }
        ?>
        <p>
            Use the Site Speed Tools Speed Test to analyze and fix the most critical issues slowing down your WordPress site.
        </p>
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="sst_submit">
            <?php submit_button('Run Speed Test'); ?>
        </form>
    </div>
    <?php
}