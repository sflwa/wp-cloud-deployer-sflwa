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
 * Add the Settings Submenu under the Custom Post Type.
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
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<hr>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'wpcd_settings_group' );
			do_settings_sections( 'wpcd-settings' );
			submit_button( esc_html__( 'Save & Refresh Core Library', 'wp-cloud-deployer' ) );
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

	// Section 1: Agency Branding
	add_settings_section( 
		'wpcd_branding_section', 
		esc_html__( 'Agency Branding', 'wp-cloud-deployer' ), 
		null, 
		'wpcd-settings' 
	);

	add_settings_field( 
		'wpcd_brand_name', 
		esc_html__( 'Agency Brand Name', 'wp-cloud-deployer' ), 
		'wpcd_brand_name_callback', 
		'wpcd-settings', 
		'wpcd_branding_section' 
	);

	// Section 2: Deployment Defaults
	add_settings_section( 
		'wpcd_core_section', 
		esc_html__( 'Global Core Plugins', 'wp-cloud-deployer' ), 
		null, 
		'wpcd-settings' 
	);

	add_settings_field( 
		'wpcd_core_plugins', 
		esc_html__( 'Select "Must-Have" Plugins', 'wp-cloud-deployer' ), 
		'wpcd_render_core_plugin_checklist', 
		'wpcd-settings', 
		'wpcd_core_section' 
	);
}
add_action( 'admin_init', 'wpcd_register_settings' );

/**
 * Branding Name Field Callback.
 */
function wpcd_brand_name_callback() {
	$val = get_option( 'wpcd_brand_name', 'WP Cloud Deployer' );
	echo '<input type="text" name="wpcd_brand_name" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="e.g., My Agency Cloud">';
	echo '<p class="description">' . esc_html__( 'This name will appear in the sidebar and dashboard labels.', 'wp-cloud-deployer' ) . '</p>';
}

/**
 * Core Plugins Checklist Callback.
 */
function wpcd_render_core_plugin_checklist() {
	$all_plugins = get_plugins();
	$selected    = get_option( 'wpcd_core_plugins', array() );
	
	echo '<p>' . esc_html__( 'Select plugins to be bundled in the "Start Site" deployment sequence:', 'wp-cloud-deployer' ) . '</p>';
	echo '<div style="max-height:300px; overflow-y:auto; border:1px solid #ccc; padding:15px; background:#fff; border-radius:4px;">';
	
	foreach ( $all_plugins as $slug => $data ) {
		$checked = in_array( $slug, (array) $selected ) ? 'checked' : '';
		echo '<label style="display:block; margin-bottom:8px; border-bottom:1px solid #eee; padding-bottom:4px;">';
		echo '<input type="checkbox" name="wpcd_core_plugins[]" value="' . esc_attr( $slug ) . '" ' . $checked . '> ';
		echo '<strong>' . esc_html( $data['Name'] ) . '</strong> <small>(v' . esc_html( $data['Version'] ) . ')</small>';
		echo '</label>';
	}
	
	echo '</div>';
}

/**
 * Sanitize array input for settings.
 */
function wpcd_sanitize_array( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}
	return array_map( 'sanitize_text_field', $input );
}

/**
 * Trigger immediate ZIP refresh when core settings are updated.
 */
add_action( 'update_option_wpcd_core_plugins', 'wpcd_trigger_sync_on_save', 10, 2 );
function wpcd_trigger_sync_on_save( $old_value, $new_value ) {
	if ( function_exists( 'wpcd_run_full_zip_cycle' ) ) {
		wpcd_run_full_zip_cycle();
	}
}
