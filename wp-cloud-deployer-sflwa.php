<?php
/**
 * Plugin Name:       WP Cloud Deployer by SFLWA
 * Description:       Centralized library for deploying curated Elementor pages, Gravity Forms, and Code Snippets.
 * Version:           1.0.0
 * Author:            SFLWA
 * Author URI:        https://sf-lwa.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-cloud-deployer
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * Tested up to:      6.9.2
 *
 * @package WPCloudDeployer
 */

/**
 * Standard Security Guard: Prevent direct access to the file.
 * PHPCS: Ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define Plugin Constants for easier path management.
 */
define( 'WPCD_VERSION', '1.0.0' );
define( 'WPCD_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCD_URL', plugin_dir_url( __FILE__ ) );

/**
 * Core Logic Includes.
 * Each file is scoped to a specific responsibility.
 */
require_once WPCD_PATH . 'inc/post-types.php';      // Package CPT registration.
require_once WPCD_PATH . 'inc/metaboxes.php';      // UI and Save logic.
require_once WPCD_PATH . 'inc/settings-branding.php'; // Agency Global settings.
require_once WPCD_PATH . 'inc/api-routes.php';     // API Endpoints for the Client bridge.
require_once WPCD_PATH . 'inc/zipper-service.php'; // Automated plugin archiving.

/**
 * Activation Hook: Initialize the Weekly ZIP Refresh.
 */
register_activation_hook( __FILE__, 'wpcd_activate_master' );
function wpcd_activate_master() {
	// Check for ZipArchive support before allowing activation.
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die( esc_html__( 'Error: The ZipArchive PHP extension is required for this plugin to function.', 'wp-cloud-deployer' ) );
	}

	if ( ! wp_next_scheduled( 'wpcd_weekly_plugin_refresh' ) ) {
		wp_schedule_event( time(), 'weekly', 'wpcd_weekly_plugin_refresh' );
	}
}

/**
 * Deactivation Hook: Clean up the cron job.
 */
register_deactivation_hook( __FILE__, 'wpcd_deactivate_master' );
function wpcd_deactivate_master() {
	wp_clear_scheduled_hook( 'wpcd_weekly_plugin_refresh' );
}

/**
 * Global Admin Branding: Injects the Agency Brand Name into the Menu.
 */
add_action( 'admin_menu', 'wpcd_apply_custom_branding', 999 );
function wpcd_apply_custom_branding() {
	global $menu;
	$brand_name = get_option( 'wpcd_brand_name', 'WP Cloud Deployer' );

	foreach ( $menu as $key => $item ) {
		if ( 'edit.php?post_type=wpcd_package' === $item[2] ) {
			// Update the menu label to your Agency brand name.
			$menu[ $key ][0] = esc_html( $brand_name );
		}
	}
}
