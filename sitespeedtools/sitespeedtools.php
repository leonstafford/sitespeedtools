<?php
/**
 * Plugin Name: Site Speed Tools
 * Plugin URI: https://www.sitespeedtools.com
 * Description: Analyse and fix the most critical issues slowing down your WordPress site with Site Speed Tools.
 * Version: 1.0.0
 * Author: Leon Stafford
 * Author URI: https://ljs.dev
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package SiteSpeedTools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'sst_add_admin_menu' );
add_action( 'admin_init', 'sst_settings_init' );

function sst_add_admin_menu(  ) { 
    add_menu_page( 'Site Speed Tools', 'Site Speed Tools', 'manage_options', 'site_speed_tools', 'sst_options_page', 'dashicons-performance' );
}

function sst_settings_init(  ) { 
    register_setting( 'pluginPage', 'sst_settings' );
    add_settings_section(
        'sst_pluginPage_section', 
        __( 'Site Speed Tools', 'wordpress' ), 
        'sst_settings_section_callback', 
        'pluginPage'
    );

    $settings_fields = [
        [
            'id' => 'sst_api_key',
            'title' => __('Site Speed Tools API Key', 'wordpress'),
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
    ?>
    <form action='options.php' method='post'>
        <?php
        settings_fields('pluginPage');
        do_settings_sections('pluginPage');
        submit_button();
        ?>
    </form>
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
    echo __( 'Enter your Site Speed Tools API Key below:', 'wordpress' );
}

add_action( 'admin_post_sst_submit', 'sst_submit' );

function sst_submit() {
    $options = get_option( 'sst_settings' );
    $api_key = $options['sst_api_key'];
    $site_info = sst_get_site_info();

    // set SST_DEVELOPMENT_MODE to true to send site info to API endpoint testing server
    define( 'SST_DEVELOPMENT_MODE', true );

    if ( defined( 'SST_DEVELOPMENT_MODE' ) && SST_DEVELOPMENT_MODE ) {
        $api_endpoint = 'https://apidev.sitespeedtools.com/api/v1/wordpress';
    } else {
        $api_endpoint = 'https://api.sitespeedtools.com/api/v1/wordpress';
    }

    $response = wp_remote_post( $api_endpoint, array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array( 'api_key' => $api_key, 'site_info' => $site_info ),
        'cookies' => array()
        )
    );

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        error_log( 'Site Speed Tools API error: ' . $error_message );
    } else {
        error_log( 'Site Speed Tools API response: ' . $response['body'] );
    }

    wp_redirect( admin_url( 'admin.php?page=site_speed_tools' ) );
    exit;
}

function sst_get_site_info(
    $site_url = '',
    $site_title = '',
    $site_description = '',
    $site_language = '',
    $plugins = array(),
    $themes = array(),
    $permalinks_structure = '',
    $users = array(),
    $posts = array(),
    $pages = array(),
    $comments = array()
) {
    $site_url = get_site_url();
    $site_title = get_bloginfo( 'name' );
    $site_description = get_bloginfo( 'description' );
    $site_language = get_bloginfo( 'language' );
    $plugins = get_plugins();
    $themes = wp_get_themes();
    $permalinks_structure = get_option( 'permalink_structure' );
    $users = get_users();
    $posts = get_posts();
    $pages = get_pages();
    $comments = get_comments();
    return json_encode( array(
        'site_url' => $site_url,
        'site_title' => $site_title,
        'site_description' => $site_description,
        'site_language' => $site_language,
        'plugins' => $plugins,
        'themes' => $themes,
        'permalinks_structure' => $permalinks_structure,
        'users' => $users,
        'posts' => $posts,
        'pages' => $pages,
        'comments' => $comments
    ) );
}
