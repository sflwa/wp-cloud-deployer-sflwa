<?php
/**
 * Global Settings and Agency Branding.
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'wpcd_add_settings_page' );
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

function wpcd_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle Manual Sync Trigger
	if ( isset( $_POST['wpcd_manual_sync'] ) && check_admin_referer( 'wpcd_sync_action', 'wpcd_sync_nonce' ) ) {
		wpcd_run_full_zip_cycle();
		echo '<div class="updated"><p>' . esc_html__( 'Library Sync Triggered! Check the /uploads/wpcd-exports/ folder.', 'wp-cloud-deployer' ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<div class="notice notice-info">
			<p><strong><?php esc_html_e( 'Manual Refresh:', 'wp-cloud-deployer' ); ?></strong> 
			If ZIPs are missing, use this button to force a rebuild of the library.</p>
			<form method="post" style="padding-bottom:10px;">
				<?php wp_nonce_field( 'wpcd_sync_action', 'wpcd_sync_nonce' ); ?>
				<input type="submit" name="wpcd_manual_sync" class="button button-secondary" value="Force Refresh Plugin ZIPs">
			</form>
		</div>

		<form action="options.php" method="post">
			<?php
			settings_fields( 'wpcd_settings_group' );
			do_settings_sections( 'wpcd-settings' );
			submit_button( esc_html__( 'Save Settings', 'wp-cloud-deployer' ) );
			?>
		</form>
	</div>
	<?php
}

add_action( 'admin_init', 'wpcd_register_settings' );
function wpcd_register_settings() {
	register_setting( 'wpcd_settings_group', 'wpcd_brand_name', 'sanitize_text_field' );
	register_setting( 'wpcd_settings_group', 'wpcd_core_plugins', 'wpcd_sanitize_array' );
	register_setting( 'wpcd_settings_group', 'wpcd_license_keys' ); // License Warehouse

	add_settings_section( 'wpcd_branding_section', 'Agency Branding', null, 'wpcd-settings' );
	add_settings_field( 'wpcd_brand_name', 'Agency Brand Name', 'wpcd_brand_name_callback', 'wpcd-settings', 'wpcd_branding_section' );

	add_settings_section( 'wpcd_license_section', 'License Warehouse', null, 'wpcd-settings' );
	add_settings_field( 'wpcd_license_keys', 'Stored License Keys', 'wpcd_render_license_field', 'wpcd-settings', 'wpcd_license_section' );

	add_settings_section( 'wpcd_core_section', 'Global Core Plugins', null, 'wpcd-settings' );
	add_settings_field( 'wpcd_core_plugins', 'Select "Must-Have" Plugins', 'wpcd_render_core_plugin_checklist', 'wpcd-settings', 'wpcd_core_section' );
}

function wpcd_brand_name_callback() {
	$val = get_option( 'wpcd_brand_name', 'WP Cloud Deployer' );
	echo '<input type="text" name="wpcd_brand_name" value="' . esc_attr( $val ) . '" class="regular-text">';
}

function wpcd_render_license_field() {
	$val = get_option( 'wpcd_license_keys' );
	echo '<textarea name="wpcd_license_keys" rows="5" class="large-text" placeholder="Format: Plugin Name | Key">'.esc_textarea($val).'</textarea>';
	echo '<p class="description">One per line. These will be sent to the Client site during deployment.</p>';
}

function wpcd_render_core_plugin_checklist() {
	$all_plugins = get_plugins();
	$selected    = get_option( 'wpcd_core_plugins', array() );
	echo '<div style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:10px; background:#fff;">';
	foreach ( $all_plugins as $slug => $data ) {
		$checked = in_array( $slug, (array) $selected ) ? 'checked' : '';
		echo '<label style="display:block;"><input type="checkbox" name="wpcd_core_plugins[]" value="' . esc_attr( $slug ) . '" ' . $checked . '> ' . esc_html( $data['Name'] ) . '</label>';
	}
	echo '</div>';
}

function wpcd_sanitize_array( $input ) {
	return is_array( $input ) ? array_map( 'sanitize_text_field', $input ) : array();
}
