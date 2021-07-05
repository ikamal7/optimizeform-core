<?php
/**
 * Utility Class File.
 *
 * @package OptimizeForm_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility Class.
 *
 * @class OptimizeForm_Core_Utils
 */
class OptimizeForm_Core_Utils {

	/**
	 * Pretty print variable.
	 *
	 * @param  mixed $data Variable.
	 */
	public static function p( $data ) {
		echo '<pre>';
		print_r( $data );
		echo '</pre>';
	}

	/**
	 * Pretty print & exit execution.
	 *
	 * @param  mixed $data Variable.
	 */
	public static function d( $data ) {
		self::p( $data );
		exit;
	}
}
