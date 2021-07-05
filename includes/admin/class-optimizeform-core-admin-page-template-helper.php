<?php
/**
 * Admin page template helper.
 *
 * @package OptimizeForm_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page template helper.
 *
 * @class OptimizeForm_Core_Admin_Page_Template_Helper
 */

class OptimizeForm_Core_Admin_Page_Template_Helper {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'optimizeform_core_admin_page_top', array( $this, 'admin_page_top' ) );
		add_action( 'optimizeform_core_admin_page_bottom', array( $this, 'admin_page_bottom' ) );
		add_action( 'optimizeform_core_admin_page_scripts', array( $this, 'admin_page_scripts' ) );
		add_action( 'optimizeform_core_admin_page_notices', array( $this, 'admin_page_notices' ) );
	}

	/**
	 * Display this on all admin page top section.
	 *
	 * @return void
	 */
	public function admin_page_top() {
		$items = optimizeform_core_get_admin_menu_items();
		?>
		<div class="optimizeform-core-inner">
			<div class="content-wrap">
		<?php
	}

	private function get_menu_template( $item ) {
		$class = 'menu-item';

		if ( ! empty( $item['class'] ) ) {
			$class .= ' ' . $item['class'];
		}

		if ( $item['icon'] ) {
			$title = sprintf(
				'<span class="menu-icon"><i class="%s"></i></span>
				<span>%s</span>',
				$item['icon'],
				$item['name']
			);
		} else {
			$title = sprintf(
				'<span>%s</span>',
				$item['name']
			);
		}

		$submenu = '';
		if ( ! empty( $item['submenu'] ) ) {
			$submenu .= '<ul class="submenu">';
			foreach ( $item['submenu'] as $submenu_item ) {
				$submenu .= $this->get_menu_template( $submenu_item );
			}
			$submenu .= '</ul>';
		}

		if ( $item['url'] ) {
			$title = sprintf(
				'<a href="%s">%s</a>',
				$item['url'],
				$title
			);
		}

		return sprintf(
			'<li class="%s">%s%s</li>',
			$class,
			$title,
			$submenu
		);
	}


	/**
	 * Display at the bottom of admin page.
	 *
	 * @return void
	 */
	public function admin_page_bottom() {
		?>
			</div><!--.content-wrap-->
		</div><!--.optimizeform-core-inner-->
		<?php
	}

	/**
	 * Enqueue required admin page scripts.
	 *
	 * @return void
	 */
	public function admin_page_scripts() {
		wp_enqueue_style( array( 'optimizeform-core-admin' ) );
		wp_enqueue_script( array( 'optimizeform-core-admin' ) );

		if ( strpos(add_query_arg( null, null ), 'optimizeform') !== false ) {
			?> 
			<style id="optimizeform-custom-dashboard">
				.wrap {
					padding: 0 40px;
				}
				#wpfooter {
					position: initial !important;
				}
				#redux-header {
					display: none;
				}
			</style>
			<?php
		}
	}

	/**
	 * Display notices on page.
	 *
	 * @return void
	 */
	public function admin_page_notices() {
		?>
		<div id="optimizeform_core-admin-notes">
			<?php if ( isset( $_GET['error'] ) && ! empty( $_GET['error'] ) ) { ?>
				<div class="notice notice-error settings-error"><p><?php echo stripslashes( urldecode( $_GET['error'] ) ); ?></p></div>
			<?php } ?>
			<?php if ( isset( $_GET['message'] ) && ! empty( $_GET['message'] ) ) { ?>
				<div class="notice notice-success settings-error"><p><?php echo stripslashes( urldecode( $_GET['message'] ) ); ?></p></div>
			<?php } ?>
		</div>
		<?php
	}
}
