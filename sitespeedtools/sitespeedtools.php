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

// add plugin settings page with menu item using lightning icon
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
    add_settings_field( 
        'sst_text_field_0', 
        __( 'Site Speed Tools API Key', 'wordpress' ), 
        'sst_text_field_0_render', 
        'pluginPage', 
        'sst_pluginPage_section' 
    );
}

function sst_text_field_0_render(  ) { 
    $options = get_option( 'sst_settings' );
    ?>
    <input type='text' name='sst_settings[sst_text_field_0]' value='<?php echo $options['sst_text_field_0']; ?>'>
    <?php
}

// Q which url should I access the settings page at?
function sst_options_page(  ) { 
    ?>
    <form action='options.php' method='post'>
        <?php
        settings_fields( 'pluginPage' );
        do_settings_sections( 'pluginPage' );
        submit_button();
        ?>
    </form>

    <!-- button on plugin settings page to submit WP site info as JSON to Site Speed Tools API -->
    <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
        <input type="hidden" name="action" value="sst_submit">
        <input type="submit" value="Get site speed reports">
    <?php
}

function sst_settings_section_callback(  ) { 
    echo __( 'Enter your Site Speed Tools API Key below:', 'wordpress' );
}

// button on plugin settings page to submit WP site info as JSON to Site Speed Tools API
add_action( 'admin_post_sst_submit', 'sst_submit' );

// the sst_submit function
function sst_submit() {
    // get the API key from the settings page
    $options = get_option( 'sst_settings' );
    $api_key = $options['sst_text_field_0'];
    // get the site info
    $site_info = sst_get_site_info();

    // if in SST_DEVELOPMENT_MODE is set, send to API endpoint testing server
    if ( defined( 'SST_DEVELOPMENT_MODE' ) && SST_DEVELOPMENT_MODE ) {
        // send request to httpbin service
        $api_endpoint = 'https://httpbin.org/post';
    } else {
        $api_endpoint = 'https://www.sitespeedtools.com/api/v1/wordpress';
    }

    // send the site info to the API
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

    // log the response
    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        error_log( 'Site Speed Tools API error: ' . $error_message );
    } else {
        error_log( 'Site Speed Tools API response: ' . $response['body'] );
    }

    // redirect back to the settings page
    wp_redirect( admin_url( 'admin.php?page=site_speed_tools' ) );
    exit;
}

// the sst_get_site_info function which gets the WP site info and returns it as JSON
// site info includes the following:
// - site url
// - site title
// - site description
// - site language
// - all plugins, noting which are active and which are not
// - all themes, noting which are active and which are not
// - permalinks structure
// - number of users
// - number of posts, pages, comments, with totals per post type
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
    // get the site url
    $site_url = get_site_url();
    // get the site title
    $site_title = get_bloginfo( 'name' );
    // get the site description
    $site_description = get_bloginfo( 'description' );
    // get the site language
    $site_language = get_bloginfo( 'language' );
    // get the plugins
    $plugins = get_plugins();
    // get the themes
    $themes = wp_get_themes();
    // get the permalinks structure
    $permalinks_structure = get_option( 'permalink_structure' );
    // get the users
    $users = get_users();
    // get the posts
    $posts = get_posts();
    // get the pages
    $pages = get_pages();
    // get the comments
    $comments = get_comments();
    // return the site info as JSON
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
