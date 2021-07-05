<?php
/**
 * Admin Settings Page Class.
 *
 * @package OptimizeForm_Core
 * @class OptimizeForm_Core_Admin_Modules_Page
 */

class OptimizeForm_Core_Admin_Modules_Page {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 6 );
	}

	/**
	 * Sanitize settings option
	 */
	public function admin_menu() {
		// Access capability.
		$access_cap = apply_filters( 'optimizeform_core_admin_page_access_cap', 'manage_options' );

		// Register menu.
		$admin_page = add_submenu_page(
			'optimizeform-core',
			__( 'OptimizeForm Core Modules', 'optimizeform-core' ),
			__( 'Modules', 'optimizeform-core' ),
			$access_cap,
			'optimizeform-core-modules',
			array( $this, 'render_page' )
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );
	}

	public function handle_actions() {
		// Maybe send current plugin data.
		optimizeform_core_maybe_send_plugins_data();
	}

	public function render_page() {
		?>
		<div class="wrap optimizeform-core-wrap">
			<?php do_action( 'optimizeform_core_admin_page_top'  ); ?>

			<h1><?php _e( 'Modules', 'optimizeform-core' ); ?></h1>

			<?php do_action( 'optimizeform_core_admin_page_notices' ); ?>

			<p>
				<a href="https://optimizeform.com/support/" class="button help-btn" target="_blank">
					<span class="btn-icon dashicons dashicons-editor-help"></span>
					<span class="btn-text"><?php _e( 'Need Help?', 'optimizeform-core' ); ?></span>
				</a>
			</p>

			<div class="optimizeform_core-admin-content">
            	<?php
					$modules = optimizeform_core_get_modules();

					// Exclude core plugin to be displayed here.
					if ( ! is_wp_error( $modules ) ) {
						$modules = wp_list_filter( $modules, array( 'slug' => 'optimizeform-core' ), 'NOT' );
					}

					include 'views/modules-grid.php';
				?>
			</div>

			<?php do_action( 'optimizeform_core_admin_page_bottom'  ); ?>
		</div>
		<?php
	}

	public function print_scripts() {
		wp_localize_script( 'optimizeform-core-admin', 'optimizeformCore', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'pageUrl' => admin_url( 'admin.php?page=optimizeform-core' )
		] );

		do_action( 'optimizeform_core_admin_page_scripts' );
	}
}
