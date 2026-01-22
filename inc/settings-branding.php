<?php
/**
 * Global Settings and Agency Branding.
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the Settings Submenu.
 */
function wpcd_add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=wpcd_package',
		esc_html__( 'Global Defaults', 'wp-cloud-deployer' ),
		esc_html__( 'Global Defaults', 'wp-cloud-deployer' ),
		'manage_options',
		'wpcd-settings',
		'wpcd_render_settings_page'
	);
}
add_action( 'admin_menu', 'wpcd_add_settings_page' );

/**
 * Render the Settings Page HTML.
 */
function wpcd_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle Manual Sync Trigger for ZIPs.
	if ( isset( $_POST['wpcd_manual_sync'] ) && check_admin_referer( 'wpcd_sync_action', 'wpcd_sync_nonce' ) ) {
		wpcd_run_full_zip_cycle();
		echo '<div class="updated"><p>' . esc_html__( 'Library Sync Triggered! ZIPs are being refreshed.', 'wp-cloud-deployer' ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<div class="notice notice-info" style="margin-top: 20px; border-left-color: #72aee6;">
			<p><strong><?php esc_html_e( 'Library Maintenance:', 'wp-cloud-deployer' ); ?></strong> 
			Clicking this button will force the server to re-zip all selected plugins immediately.</p>
			<form method="post" style="padding-bottom:10px;">
				<?php wp_nonce_field( 'wpcd_sync_action', 'wpcd_sync_nonce' ); ?>
				<input type="submit" name="wpcd_manual_sync" class="button button-secondary" value="<?php esc_attr_e( 'Force Refresh Plugin ZIPs', 'wp-cloud-deployer' ); ?>">
			</form>
		</div>

		<form action="options.php" method="post">
			<?php
			settings_fields( 'wpcd_settings_group' );
			do_settings_sections( 'wpcd-settings' );
			submit_button( esc_html__( 'Save All Master Settings', 'wp-cloud-deployer' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Register Settings, Sections, and Fields.
 */
function wpcd_register_settings() {
	register_setting( 'wpcd_settings_group', 'wpcd_brand_name', 'sanitize_text_field' );
	register_setting( 'wpcd_settings_group', 'wpcd_core_plugins', 'wpcd_sanitize_array' );
	register_setting( 'wpcd_settings_group', 'wpcd_license_keys' );

	// Section 1: Agency Branding
	add_settings_section( 'wpcd_branding_section', esc_html__( 'Agency Branding', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_brand_name', esc_html__( 'Agency Brand Name', 'wp-cloud-deployer' ), 'wpcd_brand_name_callback', 'wpcd-settings', 'wpcd_branding_section' );

	// Section 2: License Warehouse (CLI Ready)
	add_settings_section( 'wpcd_license_section', esc_html__( 'License Warehouse (CLI Ready)', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_license_keys', esc_html__( 'Stored License Keys', 'wp-cloud-deployer' ), 'wpcd_render_license_field', 'wpcd-settings', 'wpcd_license_section' );

	// Section 3: Core Deployment Defaults
	add_settings_section( 'wpcd_core_section', esc_html__( 'Start Site Core Plugins', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_core_plugins', esc_html__( 'Must-Have Plugins', 'wp-cloud-deployer' ), 'wpcd_render_core_plugin_checklist', 'wpcd-settings', 'wpcd_core_section' );
}
add_action( 'admin_init', 'wpcd_register_settings' );

/**
 * Branding Callback.
 */
function wpcd_brand_name_callback() {
	$val = get_option( 'wpcd_brand_name', 'WP Cloud Deployer' );
	echo '<input type="text" name="wpcd_brand_name" value="' . esc_attr( $val ) . '" class="regular-text">';
	echo '<p class="description">' . esc_html__( 'Used for menu labels and white-labeling the client plugin.', 'wp-cloud-deployer' ) . '</p>';
}

/**
 * License Warehouse Callback.
 */
function wpcd_render_license_field() {
	$val = get_option( 'wpcd_license_keys' );
	echo '<textarea name="wpcd_license_keys" rows="8" class="large-text" style="font-family:monospace;" placeholder="elementor-pro|ep-wCRug..."></textarea>';
	echo '<p class="description"><strong>' . esc_html__( 'Format:', 'wp-cloud-deployer' ) . '</strong> <code>slug|key</code> ' . esc_html__( '(One per line)', 'wp-cloud-deployer' ) . '<br>';
	echo esc_html__( 'Example:', 'wp-cloud-deployer' ) . ' <code>elementor-pro|ep-key123</code><br>';
	echo esc_html__( 'The Client plugin will use these to run CLI activation commands.', 'wp-cloud-deployer' ) . '</p>';
}

/**
 * Core Plugins Checklist Callback.
 */
function wpcd_render_core_plugin_checklist() {
	$all_plugins = get_plugins();
	$selected    = get_option( 'wpcd_core_plugins', array() );
	
	echo '<div style="max-height:250px; overflow-y:auto; border:1px solid #ccc; padding:15px; background:#fff; border-radius:4px;">';
	foreach ( $all_plugins as $slug => $data ) {
		$checked = in_array( $slug, (array) $selected ) ? 'checked' : '';
		echo '<label style="display:block; margin-bottom:8px; border-bottom:1px solid #f0f0f1; padding-bottom:4px;">';
		echo '<input type="checkbox" name="wpcd_core_plugins[]" value="' . esc_attr( $slug ) . '" ' . $checked . '> ';
		echo '<strong>' . esc_html( $data['Name'] ) . '</strong> <small>(' . esc_html( $slug ) . ')</small>';
		echo '</label>';
	}
	echo '</div>';
	echo '<p class="description">' . esc_html__( 'Plugins checked here are zipped and served for every "Start Site" request.', 'wp-cloud-deployer' ) . '</p>';
}

/**
 * Sanitize array helper.
 */
function wpcd_sanitize_array( $input ) {
	return is_array( $input ) ? array_map( 'sanitize_text_field', $input ) : array();
}
