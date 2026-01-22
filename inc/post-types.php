<?php
/**
 * Registration of the Package Custom Post Type (CPT).
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the 'wpcd_package' custom post type.
 *
 * This CPT acts as the "container" for curated pages, forms, and plugins.
 * Using 'wpcd_' prefix to ensure no conflicts with other plugins.
 */
function wpcd_register_package_cpt() {
	// Dynamically pull the Brand Name for the labels.
	$brand_name = get_option( 'wpcd_brand_name', 'WP Cloud Deployer' );

	$labels = array(
		'name'               => esc_html( $brand_name ),
		'singular_name'      => esc_html__( 'Package', 'wp-cloud-deployer' ),
		'menu_name'          => esc_html( $brand_name ),
		'name_admin_bar'     => esc_html__( 'Package', 'wp-cloud-deployer' ),
		'add_new'            => esc_html__( 'Add New Package', 'wp-cloud-deployer' ),
		'add_new_item'       => esc_html__( 'Add New Deployment Package', 'wp-cloud-deployer' ),
		'new_item'           => esc_html__( 'New Package', 'wp-cloud-deployer' ),
		'edit_item'          => esc_html__( 'Edit Package', 'wp-cloud-deployer' ),
		'view_item'          => esc_html__( 'View Package', 'wp-cloud-deployer' ),
		'all_items'          => esc_html__( 'All Packages', 'wp-cloud-deployer' ),
		'search_items'       => esc_html__( 'Search Packages', 'wp-cloud-deployer' ),
		'not_found'          => esc_html__( 'No packages found.', 'wp-cloud-deployer' ),
		'not_found_in_trash' => esc_html__( 'No packages found in Trash.', 'wp-cloud-deployer' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => false, // Keep it private; it's a warehouse, not a front-facing page.
		'show_ui'            => true,  // Show in admin dashboard.
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'wpcd-package' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 25, // Placed below 'Pages' and 'Comments'.
		'menu_icon'          => 'dashicons-cloud-upload',
		'supports'           => array( 'title' ), // We only need the title for naming the package.
		'show_in_rest'       => false, // Internal use only, handled by our custom API routes.
	);

	register_post_type( 'wpcd_package', $args );
}
add_action( 'init', 'wpcd_register_package_cpt' );

/**
 * Update CPT messages to reflect "Package" branding instead of "Post".
 *
 * @param array $messages Existing post updated messages.
 * @return array Modified messages.
 */
function wpcd_package_updated_messages( $messages ) {
	$post             = get_post();
	$post_id          = isset( $post->ID ) ? $post->ID : 0;
	$messages['wpcd_package'] = array(
		0  => '', // Unused.
		1  => esc_html__( 'Package updated.', 'wp-cloud-deployer' ),
		4  => esc_html__( 'Package updated.', 'wp-cloud-deployer' ),
		6  => esc_html__( 'Package published.', 'wp-cloud-deployer' ),
		7  => esc_html__( 'Package saved.', 'wp-cloud-deployer' ),
		8  => esc_html__( 'Package submitted.', 'wp-cloud-deployer' ),
		10 => esc_html__( 'Package draft updated.', 'wp-cloud-deployer' ),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'wpcd_package_updated_messages' );
