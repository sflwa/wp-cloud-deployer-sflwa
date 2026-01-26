<?php
/**
 * Zipper Service: Packages plugin folders into ZIP files for deployment.
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs the full cycle of zipping all selected core and package plugins.
 */
function wpcd_run_full_zip_cycle() {
	$export_dir = wpcd_maybe_create_export_dir();
	
	// 1. Get Global Core Plugins
	$core_plugins = get_option( 'wpcd_core_plugins', array() );
	
	// 2. Get all plugins used in Packages
	$package_plugins = array();
	$packages = get_posts( array( 'post_type' => 'wpcd_package', 'posts_per_page' => -1 ) );
	foreach ( $packages as $pkg ) {
		$plugins = get_post_meta( $pkg->ID, '_wpcd_plugins', true );
		if ( is_array( $plugins ) ) {
			$package_plugins = array_merge( $package_plugins, $plugins );
		}
	}

	// Unique list of all plugins that need zipping
	$all_to_zip = array_unique( array_merge( (array)$core_plugins, $package_plugins ) );

	foreach ( $all_to_zip as $plugin_path ) {
		wpcd_create_plugin_zip( $plugin_path, $export_dir );
	}
}

/**
 * Create the export directory and a permissive .htaccess.
 * This is the "Rescue" logic to ensure the Client can actually download the files.
 */
function wpcd_maybe_create_export_dir() {
	$upload_dir = wp_upload_dir();
	$export_dir = $upload_dir['basedir'] . '/wpcd-exports';

	if ( ! file_exists( $export_dir ) ) {
		wp_mkdir_p( $export_dir );
	}

	// Permissive .htaccess: Allow ZIP downloads but block directory indexing.
	$htaccess_content  = "Options -Indexes\n";
	$htaccess_content .= "<FilesMatch \"\.(zip)$\">\n";
	$htaccess_content .= "    <IfModule mod_authz_core.c>\n";
	$htaccess_content .= "        Require all granted\n";
	$htaccess_content .= "    </IfModule>\n";
	$htaccess_content .= "    <IfModule !mod_authz_core.c>\n";
	$htaccess_content .= "        Order allow,deny\n";
	$htaccess_content .= "        Allow from all\n";
	$htaccess_content .= "    </IfModule>\n";
	$htaccess_content .= "</FilesMatch>";

	file_put_contents( $export_dir . '/.htaccess', $htaccess_content );

	return $export_dir;
}

/**
 * Zips a plugin folder.
 *
 * @param string $plugin_path The path from plugins root (e.g., 'elementor/elementor.php').
 * @param string $export_dir  The absolute path to the export destination.
 */
function wpcd_create_plugin_zip( $plugin_path, $export_dir ) {
	$slug = explode( '/', $plugin_path )[0];
	$source = WP_PLUGIN_DIR . '/' . $slug;
	$destination = $export_dir . '/' . $slug . '.zip';

	if ( ! file_exists( $source ) ) {
		return false;
	}

	// Initialize archive object
	$zip = new ZipArchive();
	if ( $zip->open( $destination, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
		return false;
	}

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $source ),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ( $files as $name => $file ) {
		// Skip directories (they are added automatically)
		if ( ! $file->isDir() ) {
			$file_path = $file->getRealPath();
			$relative_path = $slug . '/' . substr( $file_path, strlen( $source ) + 1 );
			$zip->addFile( $file_path, $relative_path );
		}
	}

	$zip->close();
	return $destination;
}

/**
 * Schedule a weekly refresh of ZIP files.
 */
if ( ! wp_next_scheduled( 'wpcd_weekly_zip_refresh' ) ) {
	wp_schedule_event( time(), 'weekly', 'wpcd_weekly_zip_refresh' );
}
add_action( 'wpcd_weekly_zip_refresh', 'wpcd_run_full_zip_cycle' );
