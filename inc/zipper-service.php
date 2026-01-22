<?php
/**
 * Service for archiving installed plugins into ZIP files for deployment.
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zips a specific plugin folder.
 */
function wpcd_zip_installed_plugin( $plugin_slug ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return false;
	}

	$folder_name = explode( '/', $plugin_slug )[0];
	$plugin_base = WP_PLUGIN_DIR . '/' . $folder_name;
	
	if ( ! is_dir( $plugin_base ) ) {
		return false;
	}

	$upload_dir = wp_upload_dir();
	$export_dir = $upload_dir['basedir'] . '/wpcd-exports';

	if ( ! file_exists( $export_dir ) ) {
		wp_mkdir_p( $export_dir );
		file_put_contents( $export_dir . '/.htaccess', 'Options -Indexes' );
		file_put_contents( $export_dir . '/index.php', '<?php // Silence is golden' );
	}

	$zip_file_path = $export_dir . '/' . $folder_name . '.zip';
	$zip           = new ZipArchive();

	if ( true === $zip->open( $zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $plugin_base ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $name => $file ) {
			if ( ! $file->isDir() ) {
				$file_path     = $file->getRealPath();
				$relative_path = $folder_name . '/' . substr( $file_path, strlen( $plugin_base ) + 1 );
				$zip->addFile( $file_path, $relative_path );
			}
		}

		$zip->close();
		return $upload_dir['baseurl'] . '/wpcd-exports/' . $folder_name . '.zip';
	}

	return false;
}

/**
 * Runs a full cycle of zipping for all Core and Package plugins.
 */
function wpcd_run_full_zip_cycle() {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); 
	}

	$core_plugins = get_option( 'wpcd_core_plugins', array() );
	
	$package_plugins = array();
	$packages        = get_posts( array(
		'post_type'      => 'wpcd_package',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $packages as $pkg_id ) {
		$pkg_assets = get_post_meta( $pkg_id, '_wpcd_plugins', true );
		if ( is_array( $pkg_assets ) ) {
			$package_plugins = array_merge( $package_plugins, $pkg_assets );
		}
	}

	$all_to_zip = array_unique( array_merge( (array) $core_plugins, $package_plugins ) );

	if ( ! empty( $all_to_zip ) ) {
		foreach ( $all_to_zip as $slug ) {
			wpcd_zip_installed_plugin( $slug );
		}
	}
}
add_action( 'wpcd_weekly_plugin_refresh', 'wpcd_run_full_zip_cycle' );

/**
 * Clean DB fix for Code Snippets table changes.
 */
function wpcd_get_snippets_safely() {
	global $wpdb;
	$table = $wpdb->prefix . 'snippets';
	
	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
		return array();
	}

	$columns = $wpdb->get_col( "DESC $table", 0 );
	$column_to_use = in_array( 'name', $columns ) ? 'name' : 'title';

	return $wpdb->get_results( "SELECT id, $column_to_use as display_name FROM $table WHERE active = 1" );
}
