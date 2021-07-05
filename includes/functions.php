<?php
/**
 * Get plugins information api url
 *
 * @return string Url address of the API server.
 */
function optimizeform_core_get_api_url( $path = '' ) {
	return apply_filters( 'optimizeform_core_api_url', 'https://download.optimizeform.com/wp-json/optimizeform-server/v1' ) . $path;
}

function optimizeform_core_get_plugin_download_link( $slug ) {
	return optimizeform_core_get_api_url( "/download/" ) . optimizeform_core_get_license_key() . "/{$slug}.zip?wp_url=" . site_url();
}

/**
 * Get plugin basepath using folder name
 *
 * @param  string $slug Plugin slug/folder name.
 * @return string       Plugin basepath
 */
function optimizeform_core_get_plugin_file( $slug ) {
	$installed_plugin = get_plugins( '/' . $slug );
	if ( empty( $installed_plugin ) ) {
		return false;
	}

	$key = array_keys( $installed_plugin );
	$key = reset( $key );
	return $slug . '/' . $key;
}

/**
 * Check if plugin active based on folder name.
 *
 * @param  string $slug Plugin slug/folder name.
 * @return boolean      True/False based on active status.
 */
function optimizeform_core_is_module_active( $slug ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	return is_plugin_active( optimizeform_core_get_plugin_file( $slug ) );
}

/**
 * Check if plugin folder exists.
 *
 * @param  string $slug Plugin slug/folder name.
 */
function optimizeform_core_is_module_installed( $slug ) {
	return is_dir( WP_PLUGIN_DIR . '/' . $slug );
}

/**
 * Get admin page menus displayed on the left side in blue.
 *
 * @return array Array of menu items.
 */
function optimizeform_core_get_admin_menu_items() {
	$items = array(
		array(
			'name'      => __( 'Modules', 'optimizeform-core' ),
			'url' 		=> admin_url( 'admin.php?page=optimizeform-core' ),
			'icon'      => 'ti-package',
			'class'     => isset( $_REQUEST['page'] ) && 'optimizeform-core' === $_REQUEST['page'] ? 'menu-active' : '',
			'priority'  => 10
		),
		array(
			'name'      => __( 'Activate', 'optimizeform-core' ),
			'url' 		=> admin_url( 'admin.php?page=optimizeform-core-license' ),
			'icon'      => 'ti-lock',
			'class'     => isset( $_REQUEST['page'] ) && 'optimizeform-core-license' === $_REQUEST['page'] ? 'menu-active' : '',
			'priority'  => 9999
		)
	);

	$items = apply_filters( 'optimizeform_core_admin_menu_items', $items );

	uasort( $items, 'optimizeform_core_order_by_priority' );

	return $items;
}

/**
 * Order items by priority
 *
 * @param  array $a [description]
 * @param  array $b [description]
 * @return interger [description]
 */
function optimizeform_core_order_by_priority( $a, $b ) {
	if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) ) {
		return -1;
	}
	if ( $a['priority'] == $b['priority'] ) {
		return 0;
	}
	if ( $a['priority'] < $b['priority'] ) {
		return -1;
	}
	return 1;
}

/**
 * Get a modules settings page url if present, otherwise false.
 *
 * @param  string $slug Plugin slug/folder name.
 * @return mixed        False or the url.
 */
function optimizeform_core_get_module_settings_url( $slug ) {
	return apply_filters( 'optimizeform_core_module_settings_url', false, $slug );
}

/**
 * Get a module by folder name.
 *
 * @param  string $slug Plugin slug/folder name.
 * @return object       Module/Plugin data object.
 */
function optimizeform_core_get_module( $slug ) {
	$modules = optimizeform_core_get_modules();
	foreach ( $modules as $module ) {
		if ( $module->slug === $slug ) {
			return $module;
		}
	}

	return null;
}

/**
 * Get all available modules.
 *
 * @return array Array of available modules.
 */
function optimizeform_core_get_modules() {
	$request = wp_remote_request( optimizeform_core_get_api_url( '/plugins/' ) );
	if ( is_wp_error( $request ) ) {
		return array();
	}

	$body = json_decode( wp_remote_retrieve_body( $request ) );

	if ( ! empty( $body->data ) && ! empty( $body->data->status ) && 200 !== $body->data->status ) {
		return array();
	}

	return $body;
}

function optimizeform_core_activate_license( $license_key, $plugin_slug = 'optimizeform-core' ) {
	$data = optimizeform_core_api_license_data( $license_key, $plugin_slug );
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	if ( $data->installs_allowed <= $data->installs_active ) {
		return new WP_Error( 'limit_reached', __( 'License usage limit reached. You can not activate this license.' ) );
	}
	# OptimizeForm_Core_Utils::d( $data );

	update_option( '_optimizeform_core_license_key', $license_key );
	update_option( '_optimizeform_core_license_data', $data );

	optimizeform_core_maybe_send_plugins_data();

	return $data;
}

function optimizeform_core_deactivate_license() {
	$data = optimizeform_core_api_license_data( optimizeform_core_get_license_key() );
#	if ( is_wp_error( $data ) ) {
#		return $data;
#	}

	delete_option( '_optimizeform_core_license_key' );
	delete_option( '_optimizeform_core_license_data' );

	optimizeform_core_maybe_send_plugins_data();

	return true;
}

function optimizeform_core_get_license_data() {
	return get_option( '_optimizeform_core_license_data' );
}

function optimizeform_core_get_license_key() {
	return get_option( '_optimizeform_core_license_key' );
}

function optimizeform_core_log( $message, $context = array() ) {
	do_action(
		'w4_loggable_log',
		// string, usually a name from where you are storing this log
		'OptimizeForm Core',
		// string, log message
		$message,
		// array, a data that can be replaced with placeholder inside message.
		$context
	);
}

function optimizeform_core_get_plugins_data() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	$installed_plugins = get_plugins( '/' );
	if ( empty( $installed_plugins ) ) {
		return array();
	}

	$our_plugins = wp_list_filter(
		$installed_plugins,
		array(
			'Author'    => 'OptimizeForm',
			'AuthorURI' => 'https://optimizeform.com'
		)
	);
	if ( empty( $our_plugins ) ) {
		return array();
	}

	$plugins_data = array();
	foreach ( $our_plugins as $basename => $plugin ) {
		$status = 'installed';
		if ( is_plugin_active( $basename ) ) {
			$status = 'active';
		}

		$plugins_data[] = array(
			'slug'    => dirname( $basename ),
			'version' => $plugin['Version'],
			'status'  => $status
		);
	}

	return $plugins_data;
}

/**
 * Get license data.
 *
 * @return array Array of available modules.
 */
function optimizeform_core_maybe_send_plugins_data() {
	$plugins_data = optimizeform_core_get_plugins_data();

	$data = array(
		'plugins'    => $plugins_data,
		'wp_url'     => esc_url( site_url() ),
		'wp_locale'  => get_locale(),
		'wp_version' => get_bloginfo( 'version', 'display' ),
	);

	// Add license if present.
	if ( optimizeform_core_get_license_key() ) {
		$data['license_key'] = optimizeform_core_get_license_key();
	}

	// Check if we had already sent that data.
	if ( $data === get_transient( 'optimizeform_core_plugins_data' ) ) {
		return true;
	}

	optimizeform_core_log( 'optimizeform_core_maybe_send_plugins_data', array(
		'data' => $data
	));

	// Store lastest plugin data payload.
	set_transient( 'optimizeform_core_plugins_data', $data, DAY_IN_SECONDS );

	$request = wp_remote_post(
		optimizeform_core_get_api_url( "/install/bulk" ),
		array(
			'timeout' => 15,
			'headers' => array(
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $data )
		)
	);

	if ( is_wp_error( $request ) ) {
		return $request;
	}

	$body = json_decode( wp_remote_retrieve_body( $request ) );

	if ( ! empty( $body->data ) && ! empty( $body->data->status ) && 200 !== $body->data->status ) {
		return new WP_Error( $body->code, $body->message );
	}

	return true;
}

/**
 * Get license data.
 *
 * @return array Array of available modules.
 */
function optimizeform_core_api_license_data( $license_key ) {
	$data = array(
		'license_key' => $license_key,
		'wp_url'      => esc_url( site_url() ),
		'wp_locale'   => get_locale(),
		'wp_version'  => get_bloginfo( 'version', 'display' )
	);

	$request = wp_remote_post(
		optimizeform_core_get_api_url( '/license/' ),
		array(
			'headers' => array(
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $data )
		)
	);

	if ( is_wp_error( $request ) ) {
		return array();
	}

	$body = json_decode( wp_remote_retrieve_body( $request ) );

	if ( ! empty( $body->data ) && ! empty( $body->data->status ) && 200 !== $body->data->status ) {
		return new WP_Error( $body->code, $body->message );
	}

	return $body;
}
