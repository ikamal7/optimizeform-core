<?php
/**
 * Admin Settings Page Class.
 *
 * @package OptimizeForm_Core
 * @class OptimizeForm_Core_Admin_Modules_Page
 */

class OptimizeForm_Core_Admin_License_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9999 );
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
			__( 'OptimizeForm Core License', 'optimizeform-core' ),
			__( 'Activate', 'optimizeform-core' ),
			$access_cap,
			'optimizeform-core-license',
			array( $this, 'render_page' )
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );
	}

	public function handle_actions() {
		if ( isset( $_POST['action'] ) ) {
			if ( 'optimizeform_core_set_license' === $_POST['action'] ) {
				$activate = optimizeform_core_activate_license( $_POST['license_key'] );

				if ( is_wp_error( $activate ) ) {
					wp_redirect( add_query_arg( array( 'error' => urlencode( $activate->get_error_message() ) ) ) );
					exit;
				}

				wp_redirect( add_query_arg( array( 'message' => urlencode( __( 'License activated.' ) ) ) ) );
				exit;

			} elseif ( 'optimizeform_core_remove_license' === $_POST['action'] ) {
				$deactivate = optimizeform_core_deactivate_license();

				if ( is_wp_error( $deactivate ) ) {
					wp_redirect( add_query_arg( array( 'error' => urlencode( $deactivate->get_error_message() ) ) ) );
					exit;
				}

				wp_redirect( add_query_arg( array( 'message' => urlencode( __( 'License deactivated.' ) ) ) ) );
				exit;
			}
		}

		// Maybe send current plugin data.
		optimizeform_core_maybe_send_plugins_data();
	}

	public function render_page() {
		$license_key = optimizeform_core_get_license_key();

		?>
		<div class="wrap optimizeform-core-wrap">
			<?php do_action( 'optimizeform_core_admin_page_top' ); ?>

			<h1><?php _e( 'Manage License', 'optimizeform-core' ) ?></h1>

			<?php do_action( 'optimizeform_core_admin_page_notices' ); ?>

			<p>
				<a href="https://optimizeform.com/support/" class="button help-btn" target="_blank">
					<span class="btn-icon dashicons dashicons-editor-help"></span>
					<span class="btn-text"><?php _e( 'Need Help?', 'optimizeform-core' ); ?></span>
				</a>
			</p>

			<?php
			$license_messages = array();

			// DEBUG license data.
			if ( ! empty( $license_key ) ) {
				$data = optimizeform_core_api_license_data( $license_key );

				if ( is_wp_error( $data ) ) {
					$license_heading = __( 'Api Error' );
					if ( 'rest_no_route' === $data->get_error_code() ) {
						$license_messages[] = __( 'OptimizeForm API server is unreachable right now. Should be back soon.' );
					} else {
						$license_messages[] = sprintf(
							__( 'Error: %s.' ),
							$data->get_error_message()
						);
					}
				} else {

					$license_heading = __( 'License Information' );

					# $data->status = 'suspended';

					if ( $data->status === 'active' ) {
						$license_heading = __( 'License status: Active' );


						$license_messages[] = sprintf(
							__( 'This license will expired on %s.' ),
							mysql2date( 'dS M Y', $data->date_active_through )
						);

						$license_messages[] = sprintf(
							__( 'You have used it on %d sites out of allocated %d sites.' ),
							$data->installs_active,
							$data->installs_allowed
						);

					} elseif ( $data->status === 'expired' ) {
						$license_heading = 'License Expired';

						$license_messages[] = __( 'Automatic updates has been disabled for all of our OptimizeForm plugins.' );
						$license_messages[] = sprintf(
							__( '<a href="%s">Visit here</a> to renew your license.' ),
							'https://optimizeform.com/renew'
						);

					} elseif ( $data->status === 'onhold' ) {
						$license_heading = __( 'License On-hold' );

						$license_messages[] = __( 'Our team you reviewing your license. Till then, you will not receive automatic updates..' );
						$license_messages[] = sprintf(
							__( '<a href="%s">Contact us</a> if the issue is taking longer than expected.' ),
							'https://optimizeform.com/support'
						);

					} else {
						$license_heading = __( 'License Suspended' );

						$license_messages[] = sprintf(
							__( 'Possible reason: %s' ),
							$data->status_note ? $data->status_note : __( 'Nothing' )
						);
					}
				}

				if ( ! empty( $license_messages ) ) {
					echo '<div class="optimizeform-core-info-box">';
						echo '<h2>' . $license_heading . '</h2>';
						echo '<ul><li>';
						echo join( $license_messages, '</li><li>' );
						echo '</li></ul>';
					echo '</div>';
				}
				# OptimizeForm_Core_Utils::p( $data );
			}
			?>

			<div class="optimizeform-core-box">
            	<?php
					include 'views/license-form.php';
				?>
			</div>

			<?php do_action( 'optimizeform_core_admin_page_bottom'  ); ?>
		</div>
		<?php
	}

	public function print_scripts() {
		wp_localize_script( 'optimizeform-core-admin', 'optimizeformCore', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'pageUrl' => admin_url( 'admin.php?page=optimizeform-core-license' )
		] );

		do_action( 'optimizeform_core_admin_page_scripts' );
	}
}
