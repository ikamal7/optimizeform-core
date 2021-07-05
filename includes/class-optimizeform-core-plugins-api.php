<?php
/**
 * Plugins api override.
 *
 * @package OptimizeForm_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Override WordPress plugins_api call to allow it access our plugin information.
 *
 * @class OptimizeForm_Core_Plugins_Api
 */

class OptimizeForm_Core_Plugins_Api {
	/**
	 * Singleton The reference the *Singleton* instance of this class.
	 *
	 * @var OptimizeForm_Core_Plugins_Api
	 */
	protected static $instance = null;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return OptimizeForm_Core_Plugins_Api The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	private function __construct() {
		add_filter( 'http_request_host_is_external', '__return_true' );

		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );

		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 20, 2 );

		// After active_plugins option is updated.
		add_action( 'update_option_active_plugins', array( $this, 'active_plugins_updated' ), 20, 2 );


		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_update_plugins' ), 50 );

		# OptimizeForm_Core_Utils::d( $this->our_plugin_slugs() );
	}

	public function pre_update_plugins( $transient ) {
		/*
		optimizeform_core_log( 'pre_update_plugins', array(
			'transient' => $transient
		) );
		*/

		if ( ! optimizeform_core_get_license_key() ) {
			return $transient;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$installed_plugins = get_plugins( '/' );
		if ( empty( $installed_plugins ) ) {
			return $transient;
		}

		$our_plugins = wp_list_filter(
			$installed_plugins,
			array(
				'Author'    => 'OptimizeForm',
				'AuthorURI' => 'https://optimizeform.com'
			)
		);
		if ( empty( $our_plugins ) ) {
			return $transient;
		}

		$checked = array();
		foreach ( $our_plugins as $basename => $plugin ) {
			$checked[ $basename ] = $plugin['Version'];
		}

		$optimizeform_plugins = get_transient( 'optimizeform_core_plugins' );

		if ( is_admin() && isset( $_REQUEST['force-check'] ) ) {
			$optimizeform_plugins = false;
		}


		if ( false === $optimizeform_plugins ) {
			$optimizeform_plugins = optimizeform_core_get_modules();
			if ( is_wp_error( $optimizeform_plugins ) ) {
				return $transient;
			}

			set_transient( 'optimizeform_core_plugins', $optimizeform_plugins, HOUR_IN_SECONDS );
		}

		# OptimizeForm_Core_Utils::d( $optimizeform_plugins );

		if ( empty( $optimizeform_plugins ) ) {
			return $transient;
		}

		$plugins = array();
		foreach ( $optimizeform_plugins as $plugin ) {
			$plugins[ $plugin->slug ] = (object) array(
				'id'            => str_replace( 'https://', '', $plugin->homepage ),
                'slug'          => $plugin->slug,
                'plugin'        => '',
                'new_version'   => $plugin->version,
                'url'           => $plugin->homepage,
                'package'       => optimizeform_core_get_plugin_download_link( $plugin->slug ),
                'icons'         => is_object( $plugin->icons ) ? get_object_vars( $plugin->icons ) : array(),
                'banners'       => array(),
                'banners_rtl'   => array(),
                'tested'        => $plugin->tested,
                'requires_php'  => isset( $plugin->requires_php ) ? $plugin->requires_php : '5.3.6',
                'compatibility' => new stdClass,
			);
		}

		optimizeform_core_log( 'plugins pre_update_plugins', array(
			'plugins' => $plugins,
			'checked' => $checked
		) );

		foreach ( $checked as $basename => $version ) {
			$plugin_slug = dirname( $basename );

			if ( ! empty( $plugin_slug ) && isset( $plugins[ $plugin_slug ] ) ) {

				$plugin = $plugins[ $plugin_slug ];
				$plugin->plugin = $basename;

				unset(
					$transient->checked[ $basename ],
					$transient->response[ $basename ],
					$transient->no_update[ $basename ]
				);

				if ( version_compare( $plugin->new_version, $version, '>' ) ) {
					if ( ! isset( $transient->response ) ) {
						$transient->response = array();
					}

					$transient->response[ $basename ] = $plugin;

				} else {
					if ( ! isset( $transient->no_update ) ) {
						$transient->no_update = array();
					}

					$transient->no_update[ $basename ] = $plugin;
				}
			}
		}

		/*
		optimizeform_core_log( 'end pre_update_plugins', array(
			'transient' => $transient
		) );
		*/

		return $transient;
	}

	/**
	 * Update plugin install data after any of optimizeform plugin gets installed/updated.
	 * @param  [type] $upgrader   [description]
	 * @param  [type] $hook_extra [description]
	 * @return [type]             [description]
	 */
	public function upgrader_process_complete( $upgrader, $hook_extra ) {
		optimizeform_core_log( 'upgrader_process_complete', array(
			'hook_extra' => $hook_extra,
			'upgrader' => $upgrader
		) );

		$plugin_slug = '';
		if ( isset( $hook_extra ) && isset( $hook_extra['type'] ) && isset( $hook_extra['plugin'] ) ) {
			$plugin_slug = $hook_extra['plugin'];
		} elseif ( isset( $upgrader->result ) && ! empty( $upgrader->result['destination_name'] ) ) {
			$plugin_slug = $upgrader->result['destination_name'];
		}

		if ( ! empty( $plugin_slug ) && in_array( $plugin_slug, $this->our_plugin_slugs() ) ) {
			optimizeform_core_maybe_send_plugins_data();

			// include_once ABSPATH . 'wp-admin/includes/plugin.php';
			// $plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . $product_file );
			// $this->clear_updates_transient();
			// $this->product_version = $plugin['Version'];
			// $this->api_request( 'ping' );
		}
	}

	public function active_plugins_updated( $old_value, $value ) {
		optimizeform_core_maybe_send_plugins_data();
	}

	/**
	 * Override plugins_api call.
	 *
	 * @param  mixed  $res     Response.
	 * @param  string $action  Request action.
	 * @param  array  $args    Arguments.
	 * @return mixed           Instance of WP_Error or array of response.
	 */
	public function plugins_api( $res, $action, $args ) {
		if ( 'plugin_information' === $action && in_array( $args->slug, $this->our_plugin_slugs() ) ) {
			if ( ! optimizeform_core_get_license_key() ) {
				return new WP_Error( 'license_key_required', __( 'Please activate your OptimizeForm Core plugin license to install module.' ) );
			}

			$url = optimizeform_core_get_api_url( "/plugins/{$args->slug}/?wp_url=" . esc_url( site_url() ) );
			$request = wp_remote_get( $url );

			if ( is_wp_error( $request ) ) {
				$res = new WP_Error(
					'plugins_api_failed',
					sprintf( __( 'Error: %s.' ), $request->get_error_message() )
				);
			} else {
				$res = json_decode( wp_remote_retrieve_body( $request ), true );
				if ( ! empty( $res['data'] ) && ! empty( $res['data']['status'] ) && 200 !== $res['data']['status'] ) {
					return new WP_Error( $res['code'], $res['data']['message'] );

				} elseif ( isset( $res['slug'] ) ) {
					// Object casting is required in order to match the info/1.0 format.
					unset( $res['id'] );
					$res['download_link'] = optimizeform_core_get_plugin_download_link( $args->slug );
					$res = (object) $res;

				} elseif ( empty( $res ) ) {
					$res = new WP_Error(
						'plugins_api_failed',
						'Ops'
					);
				} else {
					$res = new WP_Error(
						'plugins_api_failed',
						sprintf(
							/* translators: %s: Support forums URL. */
							__( 'An unexpected error occurred. Something may be wrong with Optimizeform Server. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
							__( 'https://optimizeform.com/support/' )
						)
					);
				}
			}
		}

		# OptimizeForm_Core_Utils::d( $res );

		return $res;
	}

	/**
	 * All of our plugins slug / folder name.
	 *
	 * @return array Our plugins.
	 */
	public function our_plugin_slugs() {
		$plugin_slugs = array();
		$optimizeform_plugins = get_transient( 'optimizeform_core_plugins' );

		if ( false === $optimizeform_plugins ) {
			$optimizeform_plugins = optimizeform_core_get_modules();
			if ( ! is_wp_error( $optimizeform_plugins ) ) {
				set_transient( 'optimizeform_core_plugins', $optimizeform_plugins, HOUR_IN_SECONDS );
			}
		}

		if ( ! empty( $optimizeform_plugins ) ) {
			$plugin_slugs = wp_list_pluck( $optimizeform_plugins, 'slug' );
		}

		if ( empty( $plugin_slugs ) ) {
			$plugin_slugs = apply_filters( 'optimizeform_core_our_plugin_slugs', array(
				'optimizeform-core',
				'optimizeform-product-table',
				'optimizeform-pricing-discount-rules',
				'optimizeform-private-woocommerce-store',
				'optimizeform-user-registration-for-woocommerce',
			));
		}

		return $plugin_slugs;
	}
}
