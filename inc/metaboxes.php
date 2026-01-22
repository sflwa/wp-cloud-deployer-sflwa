<?php
/**
 * Metaboxes for Package configuration and asset selection.
 *
 * @package WPCloudDeployer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Metabox.
 */
function wpcd_add_package_metaboxes() {
	add_meta_box(
		'wpcd_package_assets',
		esc_html__( 'Package Configuration', 'wp-cloud-deployer' ),
		'wpcd_render_package_assets_metabox',
		'wpcd_package',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'wpcd_add_package_metaboxes' );

/**
 * Enqueue Select2 for the Metabox UI.
 */
function wpcd_enqueue_metabox_scripts( $hook ) {
	global $post;
	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'wpcd_package' === $post->post_type ) {
		// Enqueue Select2 from CDN for ease; can be moved to local later.
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
	}
}
add_action( 'admin_enqueue_scripts', 'wpcd_enqueue_metabox_scripts' );

/**
 * Render the Metabox HTML.
 */
function wpcd_render_package_assets_metabox( $post ) {
	wp_nonce_field( 'wpcd_save_package_meta', 'wpcd_package_nonce' );

	$selected_pages    = get_post_meta( $post->ID, '_wpcd_pages', true ) ?: array();
	$selected_forms    = get_post_meta( $post->ID, '_wpcd_forms', true ) ?: array();
	$selected_snippets = get_post_meta( $post->ID, '_wpcd_snippets', true ) ?: array();
	$selected_plugins  = get_post_meta( $post->ID, '_wpcd_plugins', true ) ?: array();

	?>
	<style>
		.wpcd-field-group { margin-bottom: 20px; }
		.wpcd-label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
		.wpcd-select-wrapper { width: 100%; }
	</style>

	<div class="wpcd-metabox-content">
		
		<div class="wpcd-field-group">
			<label class="wpcd-label" for="wpcd_pages"><?php esc_html_e( 'Select Pages (Elementor):', 'wp-cloud-deployer' ); ?></label>
			<select name="wpcd_pages[]" id="wpcd_pages" class="wpcd-select2" multiple="multiple" style="width: 100%;">
				<?php
				$pages = get_posts( array( 'post_type' => 'page', 'numberposts' => -1, 'post_status' => 'publish' ) );
				foreach ( $pages as $page ) {
					$selected = in_array( $page->ID, $selected_pages ) ? 'selected' : '';
					echo '<option value="' . esc_attr( $page->ID ) . '" ' . $selected . '>' . esc_html( $page->post_title ) . '</option>';
				}
				?>
			</select>
		</div>

		<div class="wpcd-field-group">
			<label class="wpcd-label" for="wpcd_forms"><?php esc_html_e( 'Select Gravity Forms:', 'wp-cloud-deployer' ); ?></label>
			<select name="wpcd_forms[]" id="wpcd_forms" class="wpcd-select2" multiple="multiple" style="width: 100%;">
				<?php
				if ( class_exists( 'GFAPI' ) ) {
					$forms = GFAPI::get_forms();
					foreach ( $forms as $form ) {
						$selected = in_array( $form['id'], $selected_forms ) ? 'selected' : '';
						echo '<option value="' . esc_attr( $form['id'] ) . '" ' . $selected . '>' . esc_html( $form['title'] ) . '</option>';
					}
				} else {
					echo '<option disabled>' . esc_html__( 'Gravity Forms not detected.', 'wp-cloud-deployer' ) . '</option>';
				}
				?>
			</select>
		</div>

		<div class="wpcd-field-group">
			<label class="wpcd-label" for="wpcd_snippets"><?php esc_html_e( 'Select Code Snippets:', 'wp-cloud-deployer' ); ?></label>
			<select name="wpcd_snippets[]" id="wpcd_snippets" class="wpcd-select2" multiple="multiple" style="width: 100%;">
				<?php
				global $wpdb;
				$table_name = $wpdb->prefix . 'snippets';
				if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
					$snippets = $wpdb->get_results( "SELECT id, title FROM $table_name WHERE active = 1" );
					foreach ( $snippets as $snippet ) {
						$selected = in_array( $snippet->id, $selected_snippets ) ? 'selected' : '';
						echo '<option value="' . esc_attr( $snippet->id ) . '" ' . $selected . '>' . esc_html( $snippet->title ) . '</option>';
					}
				} else {
					echo '<option disabled>' . esc_html__( 'Code Snippets plugin table not found.', 'wp-cloud-deployer' ) . '</option>';
				}
				?>
			</select>
		</div>

		<div class="wpcd-field-group">
			<label class="wpcd-label"><?php esc_html_e( 'Bundle Additional Plugins:', 'wp-cloud-deployer' ); ?></label>
			<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
				<?php
				$all_plugins = get_plugins();
				foreach ( $all_plugins as $slug => $data ) {
					$checked = in_array( $slug, $selected_plugins ) ? 'checked' : '';
					echo '<label style="display:block; margin-bottom:3px;">';
					echo '<input type="checkbox" name="wpcd_plugins[]" value="' . esc_attr( $slug ) . '" ' . $checked . '> ' . esc_html( $data['Name'] );
					echo '</label>';
				}
				?>
			</div>
		</div>

	</div>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.wpcd-select2').select2({
				placeholder: "<?php esc_html_e( 'Click to search...', 'wp-cloud-deployer' ); ?>",
				allowClear: true
			});
		});
	</script>
	<?php
}

/**
 * Save Metabox Data.
 */
function wpcd_save_package_meta( $post_id ) {
	if ( ! isset( $_POST['wpcd_package_nonce'] ) || ! wp_verify_nonce( $_POST['wpcd_package_nonce'], 'wpcd_save_package_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$meta_keys = array(
		'wpcd_pages'    => '_wpcd_pages',
		'wpcd_forms'    => '_wpcd_forms',
		'wpcd_snippets' => '_wpcd_snippets',
		'wpcd_plugins'  => '_wpcd_plugins',
	);

	foreach ( $meta_keys as $post_key => $meta_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			$data = array_map( 'sanitize_text_field', (array) $_POST[ $post_key ] );
			update_post_meta( $post_id, $meta_key, $data );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}
	}
}
add_action( 'save_post_wpcd_package', 'wpcd_save_package_meta' );
