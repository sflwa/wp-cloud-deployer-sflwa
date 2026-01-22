<?php
/**
 * REST API Endpoints for Master-to-Client communication.
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
	// Route to get a list of all available packages.
	register_rest_route( 'wpcd/v1', '/packages', array(
		'methods'             => 'GET',
		'callback'            => 'wpcd_get_packages_list',
		'permission_callback' => 'wpcd_api_permissions_check',
	) );

	// Route to get the full data for a specific package.
	register_rest_route( 'wpcd/v1', '/package/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'callback'            => 'wpcd_get_single_package_data',
		'permission_callback' => 'wpcd_api_permissions_check',
	) );

	// Route to get Global Defaults (Core Plugins, Theme, and Keys).
	register_rest_route( 'wpcd/v1', '/defaults', array(
		'methods'             => 'GET',
		'callback'            => 'wpcd_get_global_defaults',
		'permission_callback' => 'wpcd_api_permissions_check',
	) );
}

/**
 * Permission Check: Ensure the requester is authenticated.
 */
function wpcd_api_permissions_check() {
	return current_user_can( 'edit_posts' );
}

/**
 * Get a simplified list of all packages.
 */
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
 * Get full data for a single package.
 */
function wpcd_get_single_package_data( $request ) {
	$package_id = $request['id'];
	$package    = get_post( $package_id );

	if ( ! $package || 'wpcd_package' !== $package->post_type ) {
		return new WP_Error( 'no_package', 'Package not found', array( 'status' => 404 ) );
	}

	$plugin_ids  = get_post_meta( $package_id, '_wpcd_plugins', true ) ?: array();
	$page_ids    = get_post_meta( $package_id, '_wpcd_pages', true ) ?: array();
	$form_ids    = get_post_meta( $package_id, '_wpcd_forms', true ) ?: array();
	$snippet_ids = get_post_meta( $package_id, '_wpcd_snippets', true ) ?: array();

	$data = array(
		'title'    => $package->post_title,
		'plugins'  => array(),
		'pages'    => array(),
		'forms'    => array(),
		'snippets' => array(),
	);

	// Process Plugin ZIP URLs
	$upload_url = wp_upload_dir()['baseurl'] . '/wpcd-exports/';
	foreach ( $plugin_ids as $slug ) {
		$folder = explode( '/', $slug )[0];
		$data['plugins'][] = array(
			'slug' => $slug,
			'url'  => $upload_url . $folder . '.zip',
		);
	}

	// Page/Form/Snippet data logic remains here for future extraction
	// ...

	return rest_ensure_response( $data );
}

/**
 * Get Global Defaults (Core Theme, Plugins, and License Warehouse).
 */
function wpcd_get_global_defaults() {
	$core_theme   = get_option( 'wpcd_core_theme', 'astra' );
	$core_plugins = get_option( 'wpcd_core_plugins', array() );
	$license_keys = get_option( 'wpcd_license_keys', '' );
	$brand_name   = get_option( 'wpcd_brand_name', 'SFLWA Cloud' );
	
	$upload_url   = wp_upload_dir()['baseurl'] . '/wpcd-exports/';

	$plugins_data = array();
	foreach ( (array) $core_plugins as $slug ) {
		$folder = explode( '/', $slug )[0];
		$plugins_data[] = array(
			'slug' => $slug,
			'url'  => $upload_url . $folder . '.zip',
		);
	}

	return rest_ensure_response( array(
		'brand_name'   => $brand_name,
		'core_theme'   => $core_theme,
		'core_plugins' => $plugins_data,
		'license_keys' => $license_keys, // Strings for CLI activation
	) );
}
