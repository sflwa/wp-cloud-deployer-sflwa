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
 *
 * @param string $folder_name The directory name of the plugin within wp-content/plugins.
 * @return string|bool URL to the zipped file on success, false on failure.
 */
function wpcd_zip_installed_plugin( $folder_name ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return false;
	}

	$plugin_base = WP_PLUGIN_DIR . '/' . $folder_name;
	
	// Ensure the folder exists.
	if ( ! is_dir( $plugin_base ) ) {
		return false;
	}

	$upload_dir = wp_upload_dir();
	$export_dir = $upload_dir['basedir'] . '/wpcd-exports';

	// Create export directory if missing and protect it.
	if ( ! file_exists( $export_dir ) ) {
		wp_mkdir_p( $export_dir );
		// Create .htaccess to prevent directory listing.
		file_put_contents( $export_dir . '/.htaccess', 'Options -Indexes' );
		// Create index.php for extra security.
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
			// Skip directories (they are added automatically).
			if ( ! $file->isDir() ) {
				$file_path     = $file->getRealPath();
				// Create relative path inside the zip (maintaining the plugin folder structure).
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
 * Linked to WP-Cron and the Settings "Save" trigger.
 */
function wpcd_run_full_zip_cycle() {
	// 1. Get Core Plugins from Global Settings.
	$core_plugins = get_option( 'wpcd_core_plugins', array() );
	
	// 2. Get all plugins associated with any Package CPT.
	$package_plugins = array();
	$packages        = get_posts(
		array(
			'post_type'      => 'wpcd_package',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $packages as $pkg_id ) {
		$pkg_assets = get_post_meta( $pkg_id, '_wpcd_plugins', true );
		if ( is_array( $pkg_assets ) ) {
			$package_plugins = array_merge( $package_plugins, $pkg_assets );
		}
	}

	// Combine and remove duplicates.
	$all_to_zip = array_unique( array_merge( $core_plugins, $package_plugins ) );

	if ( empty( $all_to_zip ) ) {
		return;
	}

	foreach ( $all_to_zip as $plugin_slug ) {
		// Extract folder name (e.g., 'elementor-pro/elementor-pro.php' -> 'elementor-pro').
		$folder = explode( '/', $plugin_slug )[0];
		wpcd_zip_installed_plugin( $folder );
	}
}
add_action( 'wpcd_weekly_plugin_refresh', 'wpcd_run_full_zip_cycle' );

/**
 * Admin Notice to confirm zipping is complete after a manual trigger.
 */
add_action( 'admin_notices', 'wpcd_zipping_complete_notice' );
function wpcd_zipping_complete_notice() {
	if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
		echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Plugin library has been refreshed and zipped.', 'wp-cloud-deployer' ) . '</p></div>';
	}
}
