<?php

function sst_speed_test_page() {
    $api_error = get_transient( 'sst_api_error' );
    delete_transient( 'sst_api_error' );
    ?>
    <div class="wrap">
        <h1>Site Speed Tools - Speed Test</h1>
         <?php
        if ($api_error) {
            $error_message = get_transient('sst_api_error_message');
            if ($error_message) {
                echo '<div class="notice notice-error"><p>' . $error_message . '</p></div>';
            }
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