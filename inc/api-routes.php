<?php
/**
 * REST API Endpoints for Master-to-Client communication.
 * Version: 1.8
 * Added: Gravity Forms Entries, GravityKit Views, and Options Sync.
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Custom REST API Routes.
 */
add_action( 'rest_api_init', 'wpcd_register_rest_routes' );
function wpcd_register_rest_routes() {
    
    register_rest_route( 'wpcd/v1', '/packages', array(
        'methods'             => 'GET',
        'callback'            => 'wpcd_get_packages_list',
        'permission_callback' => 'wpcd_api_permissions_check',
    ) );

    register_rest_route( 'wpcd/v1', '/package/(?P<id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'wpcd_get_single_package_data',
        'permission_callback' => 'wpcd_api_permissions_check',
    ) );

    register_rest_route( 'wpcd/v1', '/defaults', array(
        'methods'             => 'GET',
        'callback'            => 'wpcd_get_global_defaults',
        'permission_callback' => 'wpcd_api_permissions_check',
    ) );
}

function wpcd_api_permissions_check() {
    return current_user_can( 'edit_posts' );
}

function wpcd_get_packages_list() {
    $packages = get_posts( array(
        'post_type'      => 'wpcd_package',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    $response = array();
    foreach ( $packages as $pkg ) {
        $response[] = array(
            'id'    => $pkg->ID,
            'title' => $pkg->post_title,
        );
    }

    return rest_ensure_response( $response );
}

/**
 * The Package Injector: Compiles Plugins, Elementor, Gravity Forms + Entries, Views, and Options.
 */
function wpcd_get_single_package_data( $request ) {
    global $wpdb;
    $package_id = $request['id'];
    $package    = get_post( $package_id );

    if ( ! $package || 'wpcd_package' !== $package->post_type ) {
        return new WP_Error( 'no_package', 'Package not found', array( 'status' => 404 ) );
    }

    $upload_url = wp_upload_dir()['baseurl'] . '/wpcd-exports/';

    $data = array(
        'success' => true,
        'data'    => array(
            'title'   => $package->post_title,
            'plugins' => array(),
            'content' => array(
                'pages'    => array(),
                'forms'    => array(),
                'views'    => array(),
                'snippets' => array(),
                'options'  => array(), // New: System Settings
            ),
        )
    );

    // 1. Process Package Plugins
    $plugin_paths = get_post_meta( $package_id, '_wpcd_plugins', true ) ?: array();
    foreach ( (array) $plugin_paths as $path ) {
        $slug = explode( '/', $path )[0];
        $data['data']['plugins'][] = array(
            'slug' => $slug,
            'url'  => $upload_url . $slug . '.zip',
        );
    }

    // 2. Process Elementor Pages
    $page_ids = get_post_meta( $package_id, '_wpcd_pages', true ) ?: array();
    foreach ( (array) $page_ids as $pid ) {
        $data['data']['content']['pages'][] = array(
            'title'    => get_the_title( $pid ),
            'content'  => get_post_meta( $pid, '_elementor_data', true ),
            'settings' => get_post_meta( $pid, '_elementor_page_settings', true )
        );
    }

    // 3. Process Gravity Forms + Entries
    if ( class_exists( 'GFAPI' ) ) {
        $form_ids = get_post_meta( $package_id, '_wpcd_forms', true ) ?: array();
        foreach ( (array) $form_ids as $fid ) {
            $form_object = GFAPI::get_form( $fid );
            if ( is_array( $form_object ) ) {
                // Attach the last 50 entries to the form object
                $form_object['entries'] = GFAPI::get_entries( $fid, array(), null, array( 'page_size' => 50 ) );
                $data['data']['content']['forms'][] = $form_object;
            }
        }
    }

    // 4. Process GravityKit Views
    $view_ids = get_post_meta( $package_id, '_wpcd_views', true ) ?: array();
    foreach ( (array) $view_ids as $vid ) {
        $view_post = get_post( $vid );
        if ( $view_post && 'gravityview' === $view_post->post_type ) {
            $data['data']['content']['views'][] = array(
                'title' => $view_post->post_title,
                'meta'  => get_post_custom( $vid ) // Captures all View configuration
            );
        }
    }

    // 5. Process Code Snippets (SQL Table version)
    $snippet_ids = get_post_meta( $package_id, '_wpcd_snippets', true ) ?: array();
    $table_name = $wpdb->prefix . 'snippets';
    foreach ( (array) $snippet_ids as $sid ) {
        $snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $sid ) );
        if ( $snippet ) {
            $data['data']['content']['snippets'][] = array(
                'title'  => $snippet->name,
                'code'   => $snippet->code,
                'scope'  => $snippet->scope,
                'active' => 1
            );
        }
    }

    // 6. Process ITB / System Options
    $option_names = get_post_meta( $package_id, '_wpcd_options', true ) ?: array();
    foreach ( (array) $option_names as $opt_name ) {
        $val = get_option( $opt_name );
        if ( false !== $val ) {
            $data['data']['content']['options'][] = array(
                'name'  => $opt_name,
                'value' => maybe_serialize( $val ) // Ensure serialized strings stay intact
            );
        }
    }

    if ( ob_get_length() ) ob_clean();
    wp_send_json( $data );
}

function wpcd_get_global_defaults() {
    $core_theme   = get_option( 'wpcd_core_theme', 'astra' );
    $core_plugins = get_option( 'wpcd_core_plugins', array() );
    $license_keys = get_option( 'wpcd_license_keys', '' );
    
    $upload_dir   = wp_upload_dir();
    $upload_url   = $upload_dir['baseurl'] . '/wpcd-exports/';

    $plugins_data = array();
    foreach ( (array) $core_plugins as $plugin_path ) {
        $slug = explode( '/', $plugin_path )[0];
        $plugins_data[] = array(
            'slug' => $slug,
            'url'  => $upload_url . $slug . '.zip',
        );
    }

    return rest_ensure_response( array(
        'core_theme'   => $core_theme,
        'core_plugins' => $plugins_data,
        'license_keys' => $license_keys,
    ) );
}
