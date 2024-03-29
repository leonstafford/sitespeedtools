<?php

function sst_speed_test_page() {
    $api_error = get_transient( 'sst_api_error_message' );
    $options = get_option('sst_settings', []);
    delete_transient( 'sst_api_error_message' );
    ?>
    <div class="wrap">
        <h1>Site Speed Tools</h1>
        <h2>Speed Test</h2>
         <?php
        if ($api_error) {
            echo $api_error;
        }

        ?>
        <p>
            Use the Site Speed Tools Speed Test to analyze and fix the most critical issues slowing down your WordPress site.
        </p>
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="sst_run_speed_test">
            <?php submit_button('Run Speed Test'); ?>
        </form>

        <h1>Results</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" id="time" class="manage-column column-time column-primary">Time</th>
                    <th scope="col" id="status" class="manage-column column-status">Status</th>
                    <th scope="col" id="scanned-urls" class="manage-column column-scanned-urls">Scanned URLs</th>
                    <th scope="col" id="score" class="manage-column column-score">Score</th>
                    <th scope="col" id="issues-detected" class="manage-column column-issues-detected">Issues Detected</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

        <p id="sst-max-results-shown-msg" style="display:none;">Only showing up to your 10 more recent speed tests for this site.</p>

        <div id="sstools-last-poll">
            <p>Last poll: <span id="sstools-last-poll-time">2019-01-01 12:00:00</span>
            <div id="sstools-loading-indicator" class="wp-loading-indicator"></div>
            </p>
        </div>

        <style>
            .wp-loading-indicator {
                width: 16px;
                height: 16px;
                background-image: url('<?php echo admin_url('images/loading.gif'); ?>');
                background-repeat: no-repeat;
                background-position: center;
                display: none;
                vertical-align: middle;
                margin-left: 10px;
            }

            #sstools-last-poll {
                display: none;
            }
        </style>

        <?php
        
            $site_url = get_site_url();

            $site_url = urlencode($site_url); 

            $url_override = $options['sst_url_override'] ?? '';

            // if the site URL is not the same as the URL override, then use the URL override
            if ($url_override && $site_url_no_protocol !== $url_override) {
                $site_url = $url_override;
            }

        ?>
        
        <input type="hidden" id="sst-site-uri" value="<?php echo $site_url; ?>">
        <input type="hidden" id="sst-unique-token" value="<?php echo $options['sst_unique_token'] ?? ''; ?>">
        <input type="hidden" id="sst-api-key" value="<?php echo $options['sst_api_key'] ?? ''; ?>">
        <input type="hidden" id="sst-privacy-policy-accepted" value="<?php echo $options['sst_privacy_policy_accepted'] ?? ''; ?>">

        <script>
            function pollApi() {
                const sstools_site_settings = {
                    api_key: jQuery('#sst-api-key').val(),
                    site_uri: jQuery('#sst-site-uri').val(),
                    unique_token: jQuery('#sst-unique-token').val(),
                    sst_privacy_policy_accepted: jQuery('#sst-privacy-policy-accepted').val(),
                };

                // return early if no unique_token
                if (!sstools_site_settings.unique_token || !sstools_site_settings.sst_privacy_policy_accepted) {
                    return;
                }

                jQuery('#sstools-loading-indicator').css('visibility', 'visible');
                jQuery('#sstools-loading-indicator').css('display', 'block');

                sstools_site_settings.last_time = jQuery('table.wp-list-table tbody tr:last-child td.column-time').attr('data-time');
                if (sstools_site_settings.last_time === '') {
                    sstools_site_settings.last_time = 0;
                }


                // store url encoded site_uri as const
                const site_uri = encodeURIComponent(sstools_site_settings.site_uri);
                const unique_token = sstools_site_settings.unique_token;
                
                const API_ENDPOINT = 'http://apitest.sitespeedtools.com/v1/speed-test-results/' + site_uri + '/' + unique_token;
                jQuery.ajax({
                    url: API_ENDPOINT,
                    type: 'GET',
                    // data: sstools_site_settings,
                    success: function(data) {
                        console.log(data);

                        if (data) {
                            // clear out the table before adding new results
                            jQuery('table.wp-list-table tbody').empty();

                            for (let i = 0; i < data.length; i++) {
                                const resultTime = new Date(data[i].time).toLocaleString();

                                jQuery('table.wp-list-table tbody').prepend(
                                    '<tr>' +
                                        '<td class="column-time column-primary" data-colname="Time" data-time="' + data[i].time + '">' + resultTime + '</td>' +
                                        '<td class="column-status" data-colname="Status">' + data[i].status + '</td>' +
                                        '<td class="column-scanned-urls" data-colname="Scanned URLs">' + data[i].scanned_urls + '</td>' +
                                        '<td class="column-score" data-colname="Score">' + data[i].score + '</td>' +
                                        '<td class="column-issues-detected" data-colname="Issues Detected">' + data[i].issues_detected + '</td>' +
                                    '</tr>'
                                );
                            }

                            if (data.length >= 10) {
                                jQuery('#sst-max-results-shown-msg').css('display', 'block');
                            } else {
                                jQuery('#sst-max-results-shown-msg').css('display', 'none');
                            }
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    },
                    complete: function() {
                        jQuery('#sstools-loading-indicator').css('visibility', 'hidden');
                        jQuery('#sstools-last-poll').css('display', 'block');
                        jQuery('#sstools-last-poll-time').text(new Date().toLocaleString());
                    }
                });
            }

            jQuery(document).ready(function() {
                 pollApi();
                setInterval(function() {
                    pollApi();
                }, 5000);
            });
        </script>

    </div>
    <?php
}
