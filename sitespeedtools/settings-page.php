<?php

function sst_settings_init(  ) { 
    register_setting( 'pluginPage', 'sst_settings' );
    add_settings_section(
        'sst_pluginPage_section', 
        __( 'Settings', 'wordpress' ), 
        'sst_settings_section_callback', 
        'pluginPage'
    );

    $site_url = get_site_url();

    $settings_fields = [
        [
            'id' => 'sst_privacy_policy_accepted',
            'title' => __('Agreed with Privacy policy', 'wordpress'),
            'type' => 'checkbox',
            'description' => __('By checking this box, you\'ve agreed to the Privacy Policy and Terms of Service.', 'wordpress')
        ],
        [
            'id' => 'sst_use_override_url',
            'title' => __('Override Site URL?', 'wordpress'),
            'type' => 'checkbox',
            'description' => __('Use this if the public URL for your WordPress site differs from your WP Site URL (' . $site_url . ').', 'wordpress')
        ],
        [
            'id' => 'sst_override_url',
            'title' => __('Site URL', 'wordpress'),
            'type' => 'text',
            'description' => __('The public URL used to access this website.', 'wordpress')
        ],
        [
            'id' => 'sst_use_basic_auth',
            'title' => __('Use Basic Auth', 'wordpress'),
            'type' => 'checkbox',
            'description' => __('Use this if you want to test a site protected by HTTP Basic Auth.', 'wordpress')
        ],
        [
            'id' => 'sst_basic_auth_user',
            'title' => __('Basic Auth User', 'wordpress'),
            'type' => 'text',
            'description' => __('Enter the username for the Basic Auth account.', 'wordpress')
        ],
        [
            'id' => 'sst_basic_auth_password',
            'title' => __('Basic Auth Password', 'wordpress'),
            'type' => 'text',
            'description' => __('Enter the password for the Basic Auth account.', 'wordpress')
        ],
        [
            'id' => 'sst_api_key',
            'title' => __('API Key', 'wordpress'),
            'type' => 'text',
            'description' => __('Premium accounts can receive an API Key from <a href="https://sitespeedtools.com" target="_blank">SiteSpeedTools.com</>, enabling more features for you.', 'wordpress')
        ],
        [
            'id' => 'sst_unique_token',
            'title' => __('Unique Site Token (auto-generated)', 'wordpress'),
            'type' => 'text',
            'description' => __('This is a unique token generated for your site. It is used to identify your site when making API calls to SiteSpeedTools.com.', 'wordpress'),
            'readonly' => true
        ]
    ];

    array_map(function ($field) {
        add_settings_field(
            $field['id'],
            $field['title'],
            'sst_render_field',
            'pluginPage',
            'sst_pluginPage_section',
            ['id' => $field['id'], 'type' => $field['type'], 'description' => $field['description'], 'readonly' => $field['readonly'] ?? false]
        );
    }, $settings_fields);
}

function sst_render_field($args) {
    $options = get_option('sst_settings', []);
    $id = $args['id'];
    $type = $args['type'];
    $description = $args['description'];
    if ($type === 'checkbox') {
        echo "<input type='checkbox' id='$id' name='sst_settings[$id]' value='1' " . checked(1, isset($options[$id]) ? $options[$id] : 0, false) . ">";
    } else {
        // add readonly attribute if readonly is set to true
        $readonly = isset($args['readonly']) && $args['readonly'] ? 'readonly' : '';
        echo "<input type='$type' id='$id' name='sst_settings[$id]' " . $readonly . " value='" . esc_attr(isset($options[$id]) ? $options[$id] : '') . "'>";
    }
    echo "<p class='description'>" . $description . "</p>";
}

function sst_reset_settings() {
    // delete all options
    delete_option('sst_settings');
    // add a transient to show a WP notice on the next page load
    set_transient( 'sst_reset_settings', true, 5 );
    // redirect back to settings page
    wp_redirect(admin_url('admin.php?page=site_speed_tools_settings'));
    exit;
}

function sst_options_page() {
    // if the transient is set, show a WP notice
    if ( get_transient( 'sst_reset_settings' ) ) {
        echo "<div class='notice notice-success is-dismissible'><p>Settings have been reset.</p></div>";
        delete_transient( 'sst_reset_settings' );
    }
    ?>
    <div class="wrap">
        <h1>Site Speed Tools</h1>

        <?php
            $options = get_option('sst_settings');
            $privacy_policy_accepted = isset($options['sst_privacy_policy_accepted']) ? $options['sst_privacy_policy_accepted'] : false;

            if (! $privacy_policy_accepted) {
                $privacy_page_url = admin_url('admin.php?page=site_speed_tools_privacy');
                echo "<div class='notice notice-warning is-dismissible'><p>Please review and agree to our <a href=" .
                    $privacy_page_url . ">Privacy Policy</a> to start using Site Speed Tools</p></div>";
            }


        ?>

        <form action='options.php' method='post'>
            <?php
            settings_fields('pluginPage');
            do_settings_sections('pluginPage');
            submit_button('Save Settings');
            ?>
        </form>

        <?php // a button to "Reset Settings" which submits to sst_reset_settings() 
            echo "<form action='" . admin_url('admin-post.php') . "' method='post'>";
            echo "<input type='hidden' name='action' value='sst_reset_settings'>";
            submit_button('Reset Settings', 'delete', 'submit', false);
            echo "</form>";
        ?>

    </div>
    <script>
        jQuery(document).ready(function($) {
            // call the sst_generate_unique_token admin ajax function to generate a unique token.
            function generateUniqueToken() {
                // add spinner to right of the #sst_unique_token field
                $('#sst_unique_token').after('<span class="spinner is-active" style="float: none; margin: 0 2px 0 0; visibility: visible;"></span>');
                // remove the error message if it exists
                $('.error-message').remove();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sst_generate_unique_token'
                    },
                    success: function(response) {
                        $('#sst_unique_token').val(response);
                    },
                    error: function(response) {
                    // on error, show error message to right of the #sst_unique_token field, including a Regenate button
                        $('#sst_unique_token').after('<span class="error-message" style="color: red; margin-left: 10px;">Error generating unique token. <button id="sst_generate_unique_token">Regenerate</button></span>');
                        // on click of the Regenerate button, call the generateUniqueToken() function again
                        $('#sst_generate_unique_token').on('click', function() {
                            generateUniqueToken();
                        });
                    },
                    complete: function() {
                        // remove spinner
                        $('.spinner').remove();
                    }
                });
            }

            // if the #sst_unique_token field is empty, generate a unique token
            if ($('#sst_unique_token').val() === '') {
                generateUniqueToken();
            }        

            function updateFieldVisibility() {
                const overrideUrlCheckbox = $('#sst_use_override_url');
                const basicAuthCheckbox = $('#sst_use_basic_auth');
                
                if (overrideUrlCheckbox.is(':checked')) {
                    $('#sst_override_url').closest('tr').show();
                } else {
                    $('#sst_override_url').closest('tr').hide();
                }
                
                if (basicAuthCheckbox.is(':checked')) {
                    $('#sst_basic_auth_user, #sst_basic_auth_password').closest('tr').show();
                } else {
                    $('#sst_basic_auth_user, #sst_basic_auth_password').closest('tr').hide();
                }
            }
            
            $('#sst_use_override_url, #sst_use_basic_auth').on('change', updateFieldVisibility);
            updateFieldVisibility();
        });
    </script>
    <style>
        .sst-hidden-field {
            display: none;
        }
    </style>
    <?php
}

function sst_settings_section_callback(  ) { 
    // echo __( 'Check/adjust your Site Speed Tools settings below:', 'wordpress' );
}