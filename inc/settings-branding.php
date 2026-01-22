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
		
		<div class="notice notice-info" style="margin-top: 20px; border-left-color: #72aee6; background: #fff;">
			<p><strong><?php esc_html_e( 'Library Maintenance:', 'wp-cloud-deployer' ); ?></strong> 
			If you've recently updated a plugin on this Master site, click below to refresh the deployment ZIPs.</p>
			<form method="post" style="padding-bottom:10px;">
				<?php wp_nonce_field( 'wpcd_sync_action', 'wpcd_sync_nonce' ); ?>
				<input type="submit" name="wpcd_manual_sync" class="button button-secondary" value="<?php esc_attr_e( 'Force Refresh All ZIPs', 'wp-cloud-deployer' ); ?>">
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
	register_setting( 'wpcd_settings_group', 'wpcd_license_keys', 'textarea_escape_and_sanitize' );

	// Branding
	add_settings_section( 'wpcd_branding_section', esc_html__( 'Agency Branding', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_brand_name', esc_html__( 'Agency Brand Name', 'wp-cloud-deployer' ), 'wpcd_brand_name_callback', 'wpcd-settings', 'wpcd_branding_section' );

	// Licenses
	add_settings_section( 'wpcd_license_section', esc_html__( 'License Warehouse (CLI Ready)', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_license_keys', esc_html__( 'Stored License Keys', 'wp-cloud-deployer' ), 'wpcd_render_license_field', 'wpcd-settings', 'wpcd_license_section' );

	// Core Plugins
	add_settings_section( 'wpcd_core_section', esc_html__( 'Start Site Core Plugins', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_core_plugins', esc_html__( 'Select Must-Have Plugins', 'wp-cloud-deployer' ), 'wpcd_render_core_plugin_checklist', 'wpcd-settings', 'wpcd_core_section' );
}
add_action( 'admin_init', 'wpcd_register_settings' );

function wpcd_brand_name_callback() {
	$val = get_option( 'wpcd_brand_name', 'WP Cloud Deployer' );
	echo '<input type="text" name="wpcd_brand_name" value="' . esc_attr( $val ) . '" class="regular-text">';
}

function wpcd_render_license_field() {
	$val = get_option( 'wpcd_license_keys', '' ); // Pull the saved value
	// FIXED: Now actually echoing the $val inside the textarea
	echo '<textarea name="wpcd_license_keys" rows="8" class="large-text" style="font-family:monospace; background:#f9f9f9;" placeholder="elementor-pro|key_here">' . esc_textarea( $val ) . '</textarea>';
	echo '<p class="description"><strong>Format:</strong> <code>slug|key</code> (One per line).<br>';
	echo 'The Child plugin will use these for <code>wp elementor-pro</code> and <code>wp gf install</code> commands.</p>';
}

function wpcd_render_core_plugin_checklist() {
	$all_plugins = get_plugins();
	$selected    = (array) get_option( 'wpcd_core_plugins', array() );
	
	echo '<div style="max-height:250px; overflow-y:auto; border:1px solid #ccc; padding:15px; background:#fff; border-radius:4px;">';
	foreach ( $all_plugins as $slug => $data ) {
		$checked = in_array( $slug, $selected ) ? 'checked' : '';
		echo '<label style="display:block; margin-bottom:8px; border-bottom:1px solid #f0f0f1; padding-bottom:4px;">';
		echo '<input type="checkbox" name="wpcd_core_plugins[]" value="' . esc_attr( $slug ) . '" ' . $checked . '> ';
		echo '<strong>' . esc_html( $data['Name'] ) . '</strong> <small>(' . esc_html( $slug ) . ')</small>';
		echo '</label>';
	}
	echo '</div>';
}

function wpcd_sanitize_array( $input ) {
	return is_array( $input ) ? array_map( 'sanitize_text_field', $input ) : array();
}

function textarea_escape_and_sanitize( $input ) {
	return sanitize_textarea_field( $input );
}
