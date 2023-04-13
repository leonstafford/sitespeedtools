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

        <h1>Results</h2>
        <!-- make dummy data for now -->
        <!-- table of speed test results data with these table columns: Time | Status | Scanned URLs | Score | Issues Detect -->
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
                <tr>
                    <td class="column-time column-primary" data-colname="Time">2019-01-01 12:00:00</td>
                    <td class="column-status" data-colname="Status">Complete</td>
                    <td class="column-scanned-urls" data-colname="Scanned URLs">99</td>
                    <td class="column-score" data-colname="Score">99</td>
                    <td class="column-issues-detected" data-colname="Issues Detected">-1</td>
                </tr>
                <tr>
                    <td class="column-time column-primary" data-colname="Time">2019-01-01 12:00:00</td>
                    <td class="column-status" data-colname="Status">Complete</td>
                    <td class="column-scanned-urls" data-colname="Scanned URLs">99</td>
                    <td class="column-score" data-colname="Score">99</td>
                    <td class="column-issues-detected" data-colname="Issues Detected">-1</td>
                </tr>
                <tr>
                    <td class="column-time column-primary" data-colname="Time">2019-01-01 12:00:00</td>
                    <td class="column-status" data-colname="Status">Complete</td>
                    <td class="column-scanned-urls" data-colname="Scanned URLs">99</td>
                    <td class="column-score" data-colname="Score">99</td>
                    <td class="column-issues-detected" data-colname="Issues Detected">-1</td>
                </tr>
            </tbody>
        </table>

        <!-- add section to show last time data was polled from API, along with WordPress' loading indicator, set to greyed out while inactive -->
        <div class="sstools-last-poll">
            <p>Last poll: <span id="sstools-last-poll-time">2019-01-01 12:00:00</span></p>
            <div id="sstools-loading-indicator" class="loading-indicator"></div>
        </div>

        <!-- store API key in hidden input field on page -->
        <input type="hidden" id="sst-api-key" value="<?php echo get_option('sst_api_key'); ?>">
        <input type="hidden" id="sst-uri" value="<?php echo get_option('sst_uri'); ?>">
        <input type="hidden" id="sst-url-override" value="<?php echo get_option('sst_url_override'); ?>">

        <!-- example JSON data to create the table above 
        {
            "time": "2019-01-01 12:00:00",
            "status": "Complete",
            "scanned_urls": 99,
            "score": 99,
            "issues_detected": -1
        } -->	

        <script>
            function pollApi() {
                // get API key, URI, and URL override values from hidden input fields on this page
                const sstools_site_settings = {
                    api_key: jQuery('#sst-api-key').val(),
                    uri: jQuery('#sst-uri').val(),
                    url_override: jQuery('#sst-url-override').val()
                };
                
                // last date/time value from table data
                sstools_site_settings.last_time = jQuery('table.wp-list-table tbody tr:last-child td.column-time').text();
                // if no data in table, set to 0
                if (sstools_site_settings.last_time === '') {
                    sstools_site_settings.last_time = 0;
                }

                // make API request to get speed test results
                const API_ENDPOINT = 'https://api.sitespeedtools.com/v1/speed-test-results';
                // do the API request
                jQuery.ajax({
                    url: API_ENDPOINT,
                    type: 'GET',
                    data: sstools_site_settings,
                    success: function(data) {
                        // if data is returned, update table data
                        if (data) {
                            // loop through data and add to table
                            for (let i = 0; i < data.length; i++) {
                                // add new row to table
                                jQuery('table.wp-list-table tbody').append(
                                    '<tr>' +
                                        '<td class="column-time column-primary" data-colname="Time">' + data[i].time + '</td>' +
                                        '<td class="column-status" data-colname="Status">' + data[i].status + '</td>' +
                                        '<td class="column-scanned-urls" data-colname="Scanned URLs">' + data[i].scanned_urls + '</td>' +
                                        '<td class="column-score" data-colname="Score">' + data[i].score + '</td>' +
                                        '<td class="column-issues-detected" data-colname="Issues Detected">' + data[i].issues_detected + '</td>' +
                                    '</tr>'
                                );
                            }
                        }
                    },
                    error: function(error) {
                        // if error, display error message
                        console.log(error);
                    }
                });
            }

            jQuery(document).ready(function() {
                // Call the pollApi function on page load
                 pollApi();
                // Set an interval to call the pollApi function every 5 seconds
                setInterval(function() {
                    pollApi();
                }, 5000); // 5000 milliseconds = 5 seconds
            });
        </script>

    </div>
    <?php
}
