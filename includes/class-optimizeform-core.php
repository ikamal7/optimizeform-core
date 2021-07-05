<?php
/**
 * Main Plugin File.
 *
 * @package OptimizeForm_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class.
 *
 * @class OptimizeForm_Core
 */
final class OptimizeForm_Core {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	public $name = 'OptimizeForm Core';

	/**
	 * Singleton The reference the *Singleton* instance of this class.
	 *
	 * @var OptimizeForm_Core
	 */
	protected static $instance = null;

	/**
	 * Private clone method to prevent cloning of the instance of the
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Private unserialize method to prevent unserializing.
	 *
	 * @return void
	 */
	private function __wakeup() {}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	private function __construct() {
		$this->define_constants();
		$this->include_files();
		$this->register_hooks();
		$this->initialize();
	}

	/**
	 * Define constants
	 */
	private function define_constants() {
		define( 'OPTIMIZEFORM_CORE_DIR', plugin_dir_path( OPTIMIZEFORM_CORE_PLUGIN_FILE ) );
		define( 'OPTIMIZEFORM_CORE_URL', plugin_dir_url( OPTIMIZEFORM_CORE_PLUGIN_FILE ) );
		define( 'OPTIMIZEFORM_CORE_BASENAME', plugin_basename( OPTIMIZEFORM_CORE_PLUGIN_FILE ) );
		define( 'OPTIMIZEFORM_CORE_NAME', $this->name );
	}

	/**
	 * Include plugin dependency files
	 */
	private function include_files() {
		require OPTIMIZEFORM_CORE_DIR . '/includes/functions.php';
		require OPTIMIZEFORM_CORE_DIR . '/includes/class-optimizeform-core-plugins-api.php';
		require OPTIMIZEFORM_CORE_DIR . '/includes/class-optimizeform-core-utils.php';
		require OPTIMIZEFORM_CORE_DIR . '/includes/class-optimizeform-core-settings-api.php';

		if ( is_admin() ) {
			require OPTIMIZEFORM_CORE_DIR . '/includes/admin/class-optimizeform-core-ajax-handlers.php';
			require OPTIMIZEFORM_CORE_DIR . '/includes/admin/class-optimizeform-core-admin-main.php';
			require OPTIMIZEFORM_CORE_DIR . '/includes/admin/class-optimizeform-core-admin-dashboard-widget.php';
			require OPTIMIZEFORM_CORE_DIR . '/includes/admin/class-optimizeform-core-admin-page-template-helper.php';
			require OPTIMIZEFORM_CORE_DIR . '/includes/admin/pages/class-admin-modules-page.php';
			require OPTIMIZEFORM_CORE_DIR . '/includes/admin/pages/class-admin-license-page.php';
			require OPTIMIZEFORM_CORE_DIR . '/includes/admin/pages/class-admin-dashboard-page.php';
		}
	}

	/**
	 * Initialize the plugin
	 */
	private function initialize() {
		OptimizeForm_Core_Plugins_Api::get_instance();

		if ( is_admin() ) {
			new OptimizeForm_Core_Ajax_Handlers();
			new OptimizeForm_Core_Admin_Page_Template_Helper();
			new OptimizeForm_Core_Admin_Main();
			new OptimizeForm_Core_Admin_Dashboard_Widget();
			new OptimizeForm_Core_Admin_Dashboard_Page();
			new OptimizeForm_Core_Admin_Modules_Page();
			new OptimizeForm_Core_Admin_License_Page();
		}
	}

	/**
	 * Register hooks
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'load_plugin_translations' ) );
		add_action( 'optimizeform_core_news_cron', array( $this, 'process_news_cron' ) );
	}

	/**
	 * Register hooks
	 */
	public function process_news_cron() {
		optimizeform_core_log( 'process_news_cron' );

		$url = 'https://optimizeform.com/wp-json/wp/v2/posts?tags=36&limit=6';
		$response = wp_remote_get( $url, array( 'timeout' => 20 ) );
		$posts = array();

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			foreach ( $body as $post ) {
				$posts[] = array(
					'title' => $post['title']['rendered'],
					'link' => $post['link']
				);
			}

			set_transient( 'optimizeform_core_news', $posts, 3600 );

			optimizeform_core_log( 'New cached', $posts );

		} else {
			optimizeform_core_log( $response->get_error_message() );
		}
	}

	/**
	 * Load plugin translation file
	 */
	public function load_plugin_translations() {
		load_plugin_textdomain(
			'optimizeform-core',
			false,
			basename( dirname( OPTIMIZEFORM_CORE_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
