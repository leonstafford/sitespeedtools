<?php

function sst_settings_init(  ) { 
    register_setting( 'pluginPage', 'sst_settings' );
    add_settings_section(
        'sst_pluginPage_section', 
        __( 'Settings', 'wordpress' ), 
        'sst_settings_section_callback', 
        'pluginPage'
    );

    $settings_fields = [
        [
            'id' => 'sst_api_key',
            'title' => __('API Key', 'wordpress'),
            'type' => 'text'
        ],
        [
            'id' => 'sst_override_url_checkbox',
            'title' => __('Override site URL', 'wordpress'),
            'type' => 'checkbox'
        ],
        [
            'id' => 'sst_override_url_text',
            'title' => __('Override URL', 'wordpress'),
            'type' => 'text'
        ],
        [
            'id' => 'sst_basic_auth_checkbox',
            'title' => __('Use basic auth', 'wordpress'),
            'type' => 'checkbox'
        ],
        [
            'id' => 'sst_basic_auth_user',
            'title' => __('Basic Auth User', 'wordpress'),
            'type' => 'text'
        ],
        [
            'id' => 'sst_basic_auth_password',
            'title' => __('Basic Auth Password', 'wordpress'),
            'type' => 'text'
        ]
    ];

    array_map(function ($field) {
        add_settings_field(
            $field['id'],
            $field['title'],
            'sst_render_field',
            'pluginPage',
            'sst_pluginPage_section',
            ['id' => $field['id'], 'type' => $field['type']]
        );
    }, $settings_fields);
}

function sst_render_field($args) {
    $options = get_option('sst_settings');
    $id = $args['id'];
    $type = $args['type'];

    if ($type === 'checkbox') {
        echo "<input type='checkbox' id='$id' name='sst_settings[$id]' value='1' " . checked(1, $options[$id], false) . ">";
    } else {
        echo "<input type='$type' id='$id' name='sst_settings[$id]' value='" . esc_attr($options[$id]) . "'>";
    }
}

function sst_options_page() {
    delete_transient( 'sst_api_error' );
    ?>
    <div class="wrap">
        <h1>Site Speed Tools</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('pluginPage');
            do_settings_sections('pluginPage');
            submit_button();
            ?>
        </form>
    </div>
    <script>
        jQuery(document).ready(function($) {
            function updateFieldVisibility() {
                const overrideUrlCheckbox = $('#sst_override_url_checkbox');
                const basicAuthCheckbox = $('#sst_basic_auth_checkbox');
                
                if (overrideUrlCheckbox.is(':checked')) {
                    $('#sst_override_url_text').closest('tr').show();
                } else {
                    $('#sst_override_url_text').closest('tr').hide();
                }
                
                if (basicAuthCheckbox.is(':checked')) {
                    $('#sst_basic_auth_user, #sst_basic_auth_password').closest('tr').show();
                } else {
                    $('#sst_basic_auth_user, #sst_basic_auth_password').closest('tr').hide();
                }
            }
            
            $('#sst_override_url_checkbox, #sst_basic_auth_checkbox').on('change', updateFieldVisibility);
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
    echo __( 'Enter your Site Speed Tools API Key and optional settings below:', 'wordpress' );
}