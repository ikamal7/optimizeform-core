<?php
/**
 * Admin main class.
 *
 * @package OptimizeForm_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Main Class.
 *
 * @class OptimizeForm_Core_Admin_Main
 */
class OptimizeForm_Core_Admin_Dashboard_Widget {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ), 5 );
		add_action( 'admin_init', array( $this, 'schedule_news_cache' ) );
		add_action( 'admin_head', array( $this, 'dashboard_css' ) );
	}

	public function dashboard_css( $hook ) {
		global $pagenow;
		if ( 'index.php' === $pagenow && empty( $_REQUEST['page'] ) ) {
			?>
			<style type="text/css">
				#optimizeform_dashboard_widget .inside{
					padding: 0;
					margin-top: 0;
				}
				#optimizeform_dashboard_widget .inside ul li a{
					padding: 0 12px;
				}
			</style>
			<?php
		}
	}

	public function schedule_news_cache() {
		if ( ! wp_next_scheduled( 'optimizeform_core_news_cron' ) ) {
			wp_schedule_event( time() + 2, 'hourly', 'optimizeform_core_news_cron' );
		}
	}

	/**
	 * Fix menu icon issue.
	 */
	public function add_dashboard_widgets() {
		$posts = get_transient( 'optimizeform_core_news' );
		if ( ! empty( $posts ) ) {
			wp_add_dashboard_widget(
		        'optimizeform_dashboard_widget',
		        esc_html__( 'Optimizeform News', 'wporg' ),
		        array( $this, 'render' )
		    );
		}
	}

	public function render() {
		$posts = get_transient( 'optimizeform_core_news' );
		echo '<ul>';
		foreach ( $posts as $post ) {
			printf(
				'<li><a target="_blank" href="%s">%s</a></li>',
				$post['link'],
				$post['title']
			);
		}
		echo '</ul>';

		echo '<p class="community-events-footer">';
		echo '<a href="https://optimizeform.com/support/" target="_blank"><span aria-hidden="true" class="dashicons dashicons-yes-alt"></span> Support</a>
		 | <a href="https://optimizeform.com/" target="_blank"><span aria-hidden="true" class="dashicons dashicons-text-page"></span> More News</a>';
		echo '</p>';

	}
}
