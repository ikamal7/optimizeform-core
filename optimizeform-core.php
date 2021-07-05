<?php
/**
 * Plugin Name: OptimizeForm Core
 * Plugin URI: https://optimizeform.com
 * Description: OptimizeForm modules manager.
 * Version: 1.0.2
 * Author: OptimizeForm
 * Author URI: https://optimizeform.com
 * Requires at least: 4.4
 * Tested up to: 5.4.1
 * Text Domain: optimizeform-core
 * Domain Path: /languages
 *
 * @package OptimizeForm_Core
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail if already loaded other way.
if ( defined( 'OPTIMIZEFORM_CORE_PLUGIN_FILE' ) || defined( 'OPTIMIZEFORM_CORE_VERSION' ) ) {
	return;
}

// Define base file.
define( 'OPTIMIZEFORM_CORE_PLUGIN_FILE', __FILE__ );
// Define plugin version. (test use).
define( 'OPTIMIZEFORM_CORE_VERSION', '1.0.2' );
// Plugin url
define( 'OPTIMIZEFORM_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * Intialize everything after plugins_loaded action.
 *
 * @return void
 */
function optimizeform_core_init() {
	// Load the main plug class.
	if ( ! class_exists( 'OptimizeForm_Core' ) ) {
		require dirname( __FILE__ ) . '/includes/class-optimizeform-core.php';
	}

	optimizeform_core();
}
optimizeform_core_init();

/**
 * Get an instance of plugin main class.
 *
 * @return OptimizeForm_Core Instance of main class.
 */
function optimizeform_core() {
	return OptimizeForm_Core::get_instance();
}
