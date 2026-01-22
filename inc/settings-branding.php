<?php
/**
 * Global Settings and Agency Branding for the Master Warehouse.
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the Settings Submenu under the Package CPT.
 */
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
			<p><strong><?php esc_html_e( 'Maintenance:', 'wp-cloud-deployer' ); ?></strong> 
			If you've updated plugins on this site, click below to update the deployment ZIPs.</p>
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
add_action( 'admin_init', 'wpcd_register_settings' );
function wpcd_register_settings() {
	register_setting( 'wpcd_settings_group', 'wpcd_brand_name', 'sanitize_text_field' );
	register_setting( 'wpcd_settings_group', 'wpcd_core_plugins', 'wpcd_sanitize_array' );
	register_setting( 'wpcd_settings_group', 'wpcd_core_theme', 'sanitize_text_field' );
	register_setting( 'wpcd_settings_group', 'wpcd_license_keys', 'sanitize_textarea_field' );

	// Section 1: Agency Branding
	add_settings_section( 'wpcd_branding_section', esc_html__( 'Agency Branding', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_brand_name', esc_html__( 'Agency Brand Name', 'wp-cloud-deployer' ), 'wpcd_brand_name_callback', 'wpcd-settings', 'wpcd_branding_section' );

	// Section 2: License Warehouse
	add_settings_section( 'wpcd_license_section', esc_html__( 'License Warehouse (CLI Ready)', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	add_settings_field( 'wpcd_license_keys', esc_html__( 'Stored License Keys', 'wp-cloud-deployer' ), 'wpcd_render_license_field', 'wpcd-settings', 'wpcd_license_section' );

	// Section 3: Deployment Defaults
	add_settings_section( 'wpcd_core_section', esc_html__( 'Start Site Core Defaults', 'wp-cloud-deployer' ), null, 'wpcd-settings' );
	
	add_settings_field( 'wpcd_core_theme', esc_html__( 'Global Core Theme', 'wp-cloud-deployer' ), 'wpcd_render_theme_selector', 'wpcd-settings', 'wpcd_core_section' );
	
	add_settings_field( 'wpcd_core_plugins', esc_html__( 'Must-Have Core Plugins', 'wp-cloud-deployer' ), 'wpcd_render_core_plugin_checklist', 'wpcd-settings', 'wpcd_core_section' );
}

/**
 * Branding Callback.
 */
function wpcd_brand_name_callback() {
	$val = get_option( 'wpcd_brand_name', 'WP Cloud Deployer' );
	echo '<input type="text" name="wpcd_brand_name" value="' . esc_attr( $val ) . '" class="regular-text">';
}

/**
 * License Warehouse Callback.
 */
function wpcd_render_license_field() {
	$val = get_option( 'wpcd_license_keys', '' );
	echo '<textarea name="wpcd_license_keys" rows="8" class="large-text" style="font-family:monospace; background:#f9f9f9;" placeholder="astra-addon|key_here">' . esc_textarea( $val ) . '</textarea>';
	echo '<p class="description"><strong>Format:</strong> <code>slug|key</code> (One per line).<br>';
	echo 'For Astra Pro use: <code>astra-addon|key</code></p>';
}

/**
 * Core Theme Selector Callback.
 */
function wpcd_render_theme_selector() {
	$themes = wp_get_themes();
	$selected = get_option( 'wpcd_core_theme', 'astra' );
	echo '<select name="wpcd_core_theme" class="regular-text">';
	foreach ( $themes as $slug => $theme ) {
		$is_selected = ( $selected === $slug ) ? 'selected' : '';
		echo '<option value="' . esc_attr( $slug ) . '" ' . $is_selected . '>' . esc_html( $theme->get( 'Name' ) ) . '</option>';
	}
	echo '</select>';
	echo '<p class="description">This theme will be force-installed and activated on the Client site.</p>';
}

/**
 * Core Plugins Checklist Callback.
 */
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

/**
 * Sanitize array helper.
 */
function wpcd_sanitize_array( $input ) {
	return is_array( $input ) ? array_map( 'sanitize_text_field', $input ) : array();
}
