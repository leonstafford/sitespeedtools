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

require_once plugin_dir_path( __FILE__ ) . 'settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'speed-test-page.php';
require_once plugin_dir_path( __FILE__ ) . 'privacy-page.php';

add_action( 'admin_menu', 'sst_add_admin_menu' );
add_action( 'admin_init', 'sst_settings_init' );
add_action( 'admin_post_sst_run_speed_test', 'sst_run_speed_test' );
add_action( 'admin_post_sst_accept_privacy_policy', 'sst_accept_privacy_policy' );
add_action( 'admin_post_sst_reset_settings', 'sst_reset_settings' );
add_action( 'admin_sst_get_api_key', 'sst_get_api_key' );
add_action( 'wp_ajax_sst_generate_unique_token', 'sst_generate_unique_token' );

// Hook into the init action
add_action('init', 'sst_init');
function sst_init() {
    // Create a new rewrite rule for /?site-speed-tools-verification=some-token and a handler function
    add_rewrite_rule('^site-speed-tools-verification/([^/]*)/?', 'index.php?site-speed-tools-verification=$matches[1]', 'top');
}

// Add a query var for the rewrite rule
add_filter('query_vars', 'sst_add_query_vars');
function sst_add_query_vars($vars) {
    $vars[] = 'site-speed-tools-verification';
    return $vars;
}

// Add a handler function for the query var
add_action('parse_request', 'sst_parse_request');
function sst_parse_request(&$wp) {
    if (array_key_exists('site-speed-tools-verification', $wp->query_vars)) {
        error_log('parsing request');

        // get or create the temporary token
        $options = get_option('sst_settings');
        $temp_token = isset($options['sst_temp_token']) ? $options['sst_temp_token'] : '';

        // if the token is not set, exit with a 404
        if (!$temp_token) {
            error_log('no temp token, exiting');
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        error_log('temp token: ' . $temp_token);

        // get the token from the query var
        $queried_token = $wp->query_vars['site-speed-tools-verification'];
        error_log('queried token: ' . $queried_token);

        // if the token matches the temporary token, print the verification file
        if ($queried_token === $temp_token) {
            error_log('tokens match, printing verification code');
            header('Content-Type: text/plain');
            echo $queried_token;
            exit;
        } else {
            error_log('tokens do not match');
            header('HTTP/1.0 404 Not Found');
            exit;
        }
    }
}

function sst_generate_unique_token() {
    $options = get_option('sst_settings');
    $site_uri = ( isset($options['sst_override_url']) && $options['sst_override_url'] !== '' ) ? $options['sst_override_url'] : get_site_url();

    // $site_url without the protocol and url encoded
    $site_uri = urlencode(str_replace(array('http://', 'https://'), '', $site_uri)); 

    $url = 'http://apitest.sitespeedtools.com/v1/get-unique-token/' . $site_uri;
    $response = wp_remote_get($url);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    if (isset($data->success) && $data->success) {
        $options['sst_unique_token'] = $data->token;
        update_option('sst_settings', $options);
        echo json_encode(array(
            'success' => true,
            'token' => $data->token,
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'message' => isset($data->message) ? $data->message : 'Failed to generate unique token.',
        ));
    }
    wp_die();
}

function sst_accept_privacy_policy() {
    error_log('accepting privacy policy');
    $options = get_option('sst_settings');
    error_log('options before: ' . print_r($options, true));
    $options['sst_privacy_policy_accepted'] = true;
    update_option('sst_settings', $options);
    error_log('options after: ' . print_r($options, true));
    wp_redirect(admin_url('admin.php?page=site_speed_tools_settings'));
}

function sst_get_api_key() {
    $options = get_option('sst_settings');

    // make API call to /v1/get-api-key/:hostname using either the WP site URL or the override url


    // if the API call fails, show notice to user on page redirect

    return $options['sst_api_key'];
}

function sst_add_admin_menu() {
    add_menu_page(
        'Site Speed Tools',
        'Site Speed Tools',
        'manage_options',
        'site_speed_tools_speed_test',
        'sst_speed_test_page',
        'dashicons-performance'
    );
    add_submenu_page(
        'site_speed_tools_speed_test',
        'Speed Test',
        'Speed Test',
        'manage_options',
        'site_speed_tools_speed_test',
        'sst_speed_test_page'
    );
    add_submenu_page(
        'site_speed_tools_speed_test',
        'Settings',
        'Settings',
        'manage_options',
        'site_speed_tools_settings',
        'sst_options_page'
    );
    add_submenu_page(
        'site_speed_tools_speed_test',
        'Privacy',
        'Privacy',
        'manage_options',
        'site_speed_tools_privacy',
        'sst_privacy_page'
    );
}

function sst_run_speed_test() {
    $options = get_option('sst_settings');

    if (empty($options['sst_privacy_policy_accepted'])) {
        $privacy_page_url = admin_url('admin.php?page=site_speed_tools_privacy');
        set_transient('sst_api_error_message', "<div class='notice notice-warning is-dismissible'><p>Please review and agree to our <a href='" .
            $privacy_page_url . "'>Privacy Policy</a> to start using Site Speed Tools.</p></div>", 60);
        wp_redirect(admin_url('admin.php?page=site_speed_tools_speed_test'));
        exit;
    }

    if (empty($options['sst_unique_token'])) {
        set_transient('sst_api_error_message', "<div class='notice notice-warning is-dismissible'><p>Required unique token missing, please check the <a href='" .
            admin_url('admin.php?page=site_speed_tools_settings') . "'>Settings page</a>.</p></div>", 60);
        wp_redirect(admin_url('admin.php?page=site_speed_tools_speed_test'));
        exit;
    }

    $api_key = $options['sst_api_key'];
    $site_info = sst_get_site_info();

    $api_endpoint = 'http://apitest.sitespeedtools.com/v1/speed-test/create';

    $response = wp_remote_request( $api_endpoint, array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'api_key' => $api_key,
            'site_info' => $site_info
        ]),
        'cookies' => array()
        )
    );

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        error_log($error_message);
        set_transient( 'sst_api_error_message', $error_message, 60 );
        wp_redirect( admin_url( 'admin.php?page=site_speed_tools_speed_test' ) );
        exit;
    } else {
        error_log( 'Site Speed Tools API response: ' . $response['body'] );
        wp_redirect( admin_url( 'admin.php?page=site_speed_tools_speed_test' ) );
        exit;
    }
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