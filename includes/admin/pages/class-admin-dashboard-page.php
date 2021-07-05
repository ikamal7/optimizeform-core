<?php 
/**
 * Admin Settings Page Class.
 *
 * @package OptimizeForm_Core
 * @class OptimizeForm_Core_Admin_Dashboard_Page
 */

class OptimizeForm_Core_Admin_Dashboard_Page {

	protected static $cached_results = array();

	protected static $transients_to_update = array();
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 5 );

        /**
         * Put header before all pages related to optimizeform dashboard
         */
        add_action( 'all_admin_notices', array( $this, 'optimizeform_dashboard_before_content' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'optimizeform_scripts'));
	}
	function get_order_report_data( $args = array() ) {
		global $wpdb;

		$default_args = array(
			'data'                => array(),
			'where'               => array(),
			'where_meta'          => array(),
			'query_type'          => 'get_row',
			'group_by'            => '',
			'order_by'            => '',
			'limit'               => '',
			'filter_range'        => false,
			'nocache'             => false,
			'debug'               => false,
			'order_types'         => wc_get_order_types( 'reports' ),
			'order_status'        => array( 'completed', 'processing', 'on-hold' ),
			'parent_order_status' => false,
		);
		$args         = apply_filters( 'woocommerce_reports_get_order_report_data_args', $args );
		$args         = wp_parse_args( $args, $default_args );

		extract( $args );

		if ( empty( $data ) ) {
			return '';
		}

		$order_status = apply_filters( 'woocommerce_reports_order_statuses', $order_status );

		$query  = array();
		$select = array();

		foreach ( $data as $raw_key => $value ) {
			$key      = sanitize_key( $raw_key );
			$distinct = '';

			if ( isset( $value['distinct'] ) ) {
				$distinct = 'DISTINCT';
			}

			switch ( $value['type'] ) {
				case 'meta':
					$get_key = "meta_{$key}.meta_value";
					break;
				case 'parent_meta':
					$get_key = "parent_meta_{$key}.meta_value";
					break;
				case 'post_data':
					$get_key = "posts.{$key}";
					break;
				case 'order_item_meta':
					$get_key = "order_item_meta_{$key}.meta_value";
					break;
				case 'order_item':
					$get_key = "order_items.{$key}";
					break;
			}

			if ( empty( $get_key ) ) {
				// Skip to the next foreach iteration else the query will be invalid.
				continue;
			}

			if ( $value['function'] ) {
				$get = "{$value['function']}({$distinct} {$get_key})";
			} else {
				$get = "{$distinct} {$get_key}";
			}

			$select[] = "{$get} as {$value['name']}";
		}

		$query['select'] = 'SELECT ' . implode( ',', $select );
		$query['from']   = "FROM {$wpdb->posts} AS posts";

		// Joins
		$joins = array();

		foreach ( ( $data + $where ) as $raw_key => $value ) {
			$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
			$type      = isset( $value['type'] ) ? $value['type'] : false;
			$key       = sanitize_key( $raw_key );

			switch ( $type ) {
				case 'meta':
					$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS meta_{$key} ON ( posts.ID = meta_{$key}.post_id AND meta_{$key}.meta_key = '{$raw_key}' )";
					break;
				case 'parent_meta':
					$joins[ "parent_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS parent_meta_{$key} ON (posts.post_parent = parent_meta_{$key}.post_id) AND (parent_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'order_item_meta':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON (posts.ID = order_items.order_id)";

					if ( ! empty( $value['order_item_type'] ) ) {
						$joins['order_items'] .= " AND (order_items.order_item_type = '{$value['order_item_type']}')";
					}

					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON " .
														"(order_items.order_item_id = order_item_meta_{$key}.order_item_id) " .
														" AND (order_item_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'order_item':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
					break;
			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
				$type      = isset( $value['type'] ) ? $value['type'] : false;
				$key       = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( 'order_item_meta' === $type ) {

					$joins['order_items']              = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items.order_item_id = order_item_meta_{$key}.order_item_id";

				} else {
					// If we have a where clause for meta, join the postmeta table
					$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS meta_{$key} ON posts.ID = meta_{$key}.post_id";
				}
			}
		}

		if ( ! empty( $parent_order_status ) ) {
			$joins['parent'] = "LEFT JOIN {$wpdb->posts} AS parent ON posts.post_parent = parent.ID";
		}

		$query['join'] = implode( ' ', $joins );

		$query['where'] = "
			WHERE 	posts.post_type 	IN ( '" . implode( "','", $order_types ) . "' )
			";

		if ( ! empty( $order_status ) ) {
			$query['where'] .= "
				AND 	posts.post_status 	IN ( 'wc-" . implode( "','wc-", $order_status ) . "')
			";
		}

		if ( ! empty( $parent_order_status ) ) {
			if ( ! empty( $order_status ) ) {
				$query['where'] .= " AND ( parent.post_status IN ( 'wc-" . implode( "','wc-", $parent_order_status ) . "') OR parent.ID IS NULL ) ";
			} else {
				$query['where'] .= " AND parent.post_status IN ( 'wc-" . implode( "','wc-", $parent_order_status ) . "') ";
			}
		}

		if ( $filter_range ) {
			$query['where'] .= "
				AND 	posts.post_date >= '" . date( 'Y-m-d H:i:s', $this->start_date ) . "'
				AND 	posts.post_date < '" . date( 'Y-m-d H:i:s', strtotime( '+1 DAY', $this->end_date ) ) . "'
			";
		}

		if ( ! empty( $where_meta ) ) {

			$relation = isset( $where_meta['relation'] ) ? $where_meta['relation'] : 'AND';

			$query['where'] .= ' AND (';

			foreach ( $where_meta as $index => $value ) {

				if ( ! is_array( $value ) ) {
					continue;
				}

				$key = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( strtolower( $value['operator'] ) == 'in' || strtolower( $value['operator'] ) == 'not in' ) {

					if ( is_array( $value['meta_value'] ) ) {
						$value['meta_value'] = implode( "','", $value['meta_value'] );
					}

					if ( ! empty( $value['meta_value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['meta_value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['meta_value']}'";
				}

				if ( ! empty( $where_value ) ) {
					if ( $index > 0 ) {
						$query['where'] .= ' ' . $relation;
					}

					if ( isset( $value['type'] ) && 'order_item_meta' === $value['type'] ) {

						if ( is_array( $value['meta_key'] ) ) {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
						} else {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   = '{$value['meta_key']}'";
						}

						$query['where'] .= " AND order_item_meta_{$key}.meta_value {$where_value} )";
					} else {

						if ( is_array( $value['meta_key'] ) ) {
							$query['where'] .= " ( meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
						} else {
							$query['where'] .= " ( meta_{$key}.meta_key   = '{$value['meta_key']}'";
						}

						$query['where'] .= " AND meta_{$key}.meta_value {$where_value} )";
					}
				}
			}

			$query['where'] .= ')';
		}

		if ( ! empty( $where ) ) {

			foreach ( $where as $value ) {

				if ( strtolower( $value['operator'] ) == 'in' || strtolower( $value['operator'] ) == 'not in' ) {

					if ( is_array( $value['value'] ) ) {
						$value['value'] = implode( "','", $value['value'] );
					}

					if ( ! empty( $value['value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['value']}'";
				}

				if ( ! empty( $where_value ) ) {
					$query['where'] .= " AND {$value['key']} {$where_value}";
				}
			}
		}

		if ( $group_by ) {
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$query['order_by'] = "ORDER BY {$order_by}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		$query = apply_filters( 'woocommerce_reports_get_order_report_query', $query );
		$query = implode( ' ', $query );

		if ( $debug ) {
			echo '<pre>';
			wc_print_r( $query );
			echo '</pre>';
		}

		if ( $debug || $nocache ) {
			self::enable_big_selects();

			$result = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
		} else {
			$query_hash = md5( $query_type . $query );
			$result     = $this->get_cached_query( $query_hash );
			if ( $result === null ) {
				self::enable_big_selects();

				$result = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
			}
			$this->set_cached_query( $query_hash, $result );
		}

		return $result;
	}

	protected function get_cached_query( $query_hash ) {
		$class = strtolower( get_class( $this ) );

		if ( ! isset( self::$cached_results[ $class ] ) ) {
			self::$cached_results[ $class ] = get_transient( strtolower( get_class( $this ) ) );
		}

		if ( isset( self::$cached_results[ $class ][ $query_hash ] ) ) {
			return self::$cached_results[ $class ][ $query_hash ];
		}

		return null;
	}
	/**
	 * Set the cached query result.
	 *
	 * @param string $query_hash The query hash.
	 * @param mixed  $data The data to cache.
	 */
	protected function set_cached_query( $query_hash, $data ) {
		$class = strtolower( get_class( $this ) );

		if ( ! isset( self::$cached_results[ $class ] ) ) {
			self::$cached_results[ $class ] = get_transient( strtolower( get_class( $this ) ) );
		}

		self::add_update_transients_hook();

		self::$transients_to_update[ $class ]          = $class;
		self::$cached_results[ $class ][ $query_hash ] = $data;
	}
	/**
	 * Enables big mysql selects for reports, just once for this session.
	 */
	protected static function enable_big_selects() {
		static $big_selects = false;

		global $wpdb;

		if ( ! $big_selects ) {
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
			$big_selects = true;
		}
	}
	/**
	 * Init the static hooks of the class.
	 */
	protected static function add_update_transients_hook() {
		if ( ! has_action( 'shutdown', array( 'WC_Admin_Report', 'maybe_update_transients' ) ) ) {
			add_action( 'shutdown', array( 'WC_Admin_Report', 'maybe_update_transients' ) );
		}
	}
	public function query_old()
	{
		$select_time = time();
		$sql = "SELECT order_item_meta__product_id.meta_value AS product_id,
		Sum(order_item_meta__qty.meta_value)   AS order_item_count 
 FROM   wp_posts AS posts 
		INNER JOIN wp_woocommerce_order_items AS order_items 
				ON posts.id = order_items.order_id 
		INNER JOIN wp_woocommerce_order_itemmeta AS order_item_meta__qty 
				ON ( order_items.order_item_id = 
					 order_item_meta__qty.order_item_id ) 
				   AND ( order_item_meta__qty.meta_key = '_qty' ) 
		 INNER JOIN wp_woocommerce_order_itemmeta AS order_item_meta__product_id 
				ON ( order_items.order_item_id = order_item_meta__product_id.order_item_id ) 
				   AND ( order_item_meta__product_id.meta_key = '_product_id' ) 
		INNER JOIN wp_woocommerce_order_itemmeta AS 
				   order_item_meta__product_id_array 
				ON order_items.order_item_id = order_item_meta__product_id_array.order_item_id 
 WHERE  posts.post_type IN ( 'shop_order', 'shop_order_refund' ) 
		AND posts.post_status IN ( 'wc-completed', 'wc-processing', 'wc-on-hold' ) 
		AND posts.post_date >= date() 
		AND posts.post_date < date($select_time) 
		AND (( order_item_meta__product_id_array.meta_key IN ( '_product_id', '_variation_id' ) 
		AND order_item_meta__product_id_array.meta_value IN ( '15' ) ))
 GROUP  BY product_id ";
	}

	/**
	 * Enqueue scripts
	 */
	public function optimizeform_scripts()
	{
		// wp_enqueue_script('chart-util-cdn', 'https://raw.githubusercontent.com/chartjs/Chart.js/master/docs/scripts/utils.js', [], '3.4.0', true);
		wp_enqueue_script('chart-js-cdn', 'https://cdn.jsdelivr.net/npm/chart.js@3.4.0/dist/chart.min.js', [], '3.4.0', true);
	}

	/**
	 * Put header before all pages related to optimizeform dashboard
	 *
	 * @return void
	 */
    public function optimizeform_dashboard_before_content() {
        if ( isset( $_GET['page'] ) ) {
            if ( $_GET['page'] === 'optimizeform-core' || $_GET['page'] === 'optimizeform-core-modules' || $_GET['page'] == 'optimizeform-core-license' ) {

                require_once __DIR__ . '/views/dashboard-header.php';
            }
        }
    }

	/**
	 * Sanitize settings option
	 */
	public function admin_menu() {
		// Access capability.
		$access_cap = 'manage_options';

		// Register menu.
		$admin_page = add_submenu_page(
			'optimizeform-core',
			__( 'OptimizeForm Core Dashboard', 'optimizeform-core' ),
			__( 'Dashboard', 'optimizeform-core' ),
			$access_cap,
			'optimizeform-core',
			array( $this, 'render_page' )
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
	}

	public function render_page() {
		// Dashboard header is being printed from admin-init.php in community_dashboard_before_content function
		?>
			<!-- features list -->
			<div class="thd-wrap thd-theme-dashboard">
				<div class="wrap optimizeform-core-wrap">
					<h1></h1>
					<?php do_action( 'optimizeform_core_admin_page_notices' ); ?>
					<div class="thd-main">
						<!-- left content -->
						<div class="thd-main-content">
							<div class="thd-panel thd-panel-general">
								<!-- tab panel -->
								<div class="thd-panel-head thd-panel-tabs">
									<div class="thd-panel-tab taby thd-panel-tab-active">
										<h3 class="thd-panel-title">
											<a href="#"> <?php echo esc_html__( 'Theme Features', 'optimizeform-core' ); ?></a>
										</h3>
									</div>
								</div>
								<!-- tab panel -->


								<div class="thd-panel-content thd-panel-content-tabs">
									<div class="thd-panel-tab db-tab-content thd-panel-tab-active">
										<div class="thd-theme-features">
											<div class="thd-theme-feature thd-theme-feature-active">
												<div class="thd-theme-feature-row">
													<div class="thd-theme-feature-name"> <?php echo esc_html__( 'Change Site Title or Logo', 'optimizeform-core' ); ?></div>
						
													<div class="thd-theme-feature-badge thd-badge thd-badge-success"> <?php echo esc_html__( 'free', 'optimizeform-core' ); ?></div>
												</div>
												<div class="thd-theme-feature-row">
												
													<a href="" class="thd-theme-feature-customize" target="_self"> <?php echo esc_html__( 'Customize', 'optimizeform-core' ); ?></a>
												</div>
											</div>
											<div class="thd-theme-feature thd-theme-feature-active">
												<div class="thd-theme-feature-row">
													<div class="thd-theme-feature-name"><?php echo esc_html__( 'Header Options', 'optimizeform-core' ); ?></div>
													
													<div class="thd-theme-feature-badge thd-badge thd-badge-success"> <?php echo esc_html__( 'free', 'optimizeform-core' ); ?></div>
												</div>
												<div class="thd-theme-feature-row">
												
													<a href="" class="thd-theme-feature-customize" target="_self" class="thd-theme-feature-customize" target="_self"> <?php echo esc_html__( 'Customize', 'optimizeform-core' ); ?></a>
												</div>
											</div>
											<div class="thd-theme-feature thd-theme-feature-active">
												<div class="thd-theme-feature-row">
													<div class="thd-theme-feature-name"> <?php echo esc_html__( 'Color Options', 'optimizeform-core' ); ?></div>
						
													<div class="thd-theme-feature-badge thd-badge thd-badge-success"> <?php echo esc_html__( 'free', 'optimizeform-core' ); ?></div>
												</div>
												<div class="thd-theme-feature-row">
												
													<a href="" class="thd-theme-feature-customize" target="_self"> <?php echo esc_html__( 'Customize', 'optimizeform-core' ); ?></a>
												</div>
											</div>
											<div class="thd-theme-feature thd-theme-feature-active">
												<div class="thd-theme-feature-row">
													<div class="thd-theme-feature-name"> <?php echo esc_html__( 'Blog Options', 'optimizeform-core' ); ?></div>
						
													<div class="thd-theme-feature-badge thd-badge thd-badge-success"><?php echo esc_html__( 'free', 'optimizeform-core' ); ?></div>
												</div>
												<div class="thd-theme-feature-row">
												
													<a href="" class="thd-theme-feature-customize" target="_self"> <?php echo esc_html__( 'Customize', 'optimizeform-core' ); ?></a>
												</div>
											</div>
											<div class="thd-theme-feature thd-theme-feature-active">
												<div class="thd-theme-feature-row">
													<div class="thd-theme-feature-name"><?php echo esc_html__( 'Sidebar Layout', 'optimizeform-core' ); ?></div>
						
													<div class="thd-theme-feature-badge thd-badge thd-badge-success"><?php echo esc_html__( 'free', 'optimizeform-core' ); ?></div>
												</div>
												<div class="thd-theme-feature-row">
												
													<a href="" class="thd-theme-feature-customize" target="_self"> <?php echo esc_html__( 'Customize', 'optimizeform-core' ); ?></a>
												</div>
											</div>
										</div>
									</div>
								</div>
								<!-- theme feature content -->
							</div>
							<!-- support pannel -->
							<div class="thd-panel thd-panel-support">
								<div class="thd-panel-head">
									<h3 class="thd-panel-title"><?php echo esc_html__( 'Support', 'optimizeform-core' ); ?></h3>
								</div>
								<div class="thd-panel-content">
									<div class="thd-conttent-primary">
										<div class="thd-title">
											<?php echo esc_html__( 'Need help? Were here for you!', 'optimizeform-core' ); ?>									</div>

										<div class="thd-description"><?php echo esc_html__( 'Have a question? Hit a bug? Get the help you need, when you need it from our friendly support staff.', 'optimizeform-core' ); ?></div>

										<div class="thd-button-wrap">
											<a href="https://communitytheme.com/support/?utm_source=dashboard&utm_medium=free-support&utm_campaign=lite" class="thd-button button" target="_blank">
												<?php echo esc_html__( 'Get Support', 'optimizeform-core' ); ?>										</a>
										</div>
									</div>

									<div class="thd-conttent-secondary pro-widget">
										<div class="thd-title">
											<?php echo esc_html__( 'Priority Support', 'optimizeform-core' ); ?>
											<div class="thd-badge"><?php echo esc_html__( 'pro', 'optimizeform-core' ); ?></div>
										</div>

										<div class="thd-description"><?php echo esc_html__( 'Want your questions answered faster? Go Pro to be first in the queue!', 'optimizeform-core' ); ?></div>

										<div class="thd-button-wrap">
											<a href="https://communitytheme.com/support/?utm_source=dashboard&utm_medium=pro-support&utm_campaign=pro-user" class="thd-button button" target="_blank"><?php echo esc_html__( 'Go PRO', 'optimizeform-core' ); ?></a>
										</div>
									</div>
								</div>
							</div>
							<!-- support pannel -->

							<!-- community panel -->
							<div class="thd-panel thd-panel-community">
								<div class="thd-panel-head">
									<h3 class="thd-panel-title"><?php echo esc_html__( 'Custom development', 'optimizeform-core' ); ?></h3>
								</div>
								<div class="thd-panel-content">
									<div class="thd-title">
										<?php echo esc_html__( 'Do you need a custom plugin or edits to your site?', 'optimizeform-core' ); ?>								</div>

									<div class="thd-description">.<?php echo esc_html__( 'We have created top-class themes and products for BuddyPress and can help you customize our products or create something custom. ' ); ?></div>

									<div class="thd-button-wrap">
										<a href="https://communitytheme.com/developers/buddypress/" class="thd-button button" target="_blank">
											<?php echo esc_html__( 'Start a Project ', 'optimizeform-core' ); ?>									</a>
									</div>
								</div>
							</div>
							<!-- community panel -->
						</div>
						<!-- left content -->

						<!-- sidebar -->
						<div class="thd-main-sidebar">
							<!-- chart Widgets -->
							<div class="thd-panel thd-panel-chart">
								<div class="thd-panel-head">
									<h3 class="thd-panel-title">Chart</h3>
								</div>
								<div class="thd-panel-content">
									<canvas id="myChart" width="200" height="200"></canvas>
								</div>
							</div>
							<!-- upgrade widget -->
							<div class="thd-panel thd-panel-promo pro-widget">
								<div class="thd-panel-inner">
									<div class="thd-heading"><?php echo esc_html__( 'Upgrade to', 'optimizeform-core' ); ?> <?php echo esc_html__( 'pro', 'optimizeform-core' ); ?></div>
									<div class="thd-description"><?php echo esc_html__( 'Take Community Theme to a whole other level by upgrading to the Pro version.', 'optimizeform-core' ); ?></div>
									<div class="thd-button-wrap">
										<a href="https://communitytheme.com/lp/upgrade/?utm_source=dashboard&utm_medium=banner&utm_campaign=upgrade" class="thd-button button" target="_blank">
											<?php echo esc_html__( 'Discover Community Theme Pro', 'optimizeform-core' ); ?>										</a>
									</div>
								</div>
							</div>
							<!-- upgrade widget -->

							<!-- review widget -->
							<div class="thd-panel thd-panel-review">
								<div class="thd-panel-head">
									<h3 class="thd-panel-title"><?php echo esc_html__( 'Review', 'optimizeform-core' ); ?></h3>
								</div>
								<div class="thd-panel-content">
									<img class="thd-stars" src="<?php echo OPTIMIZEFORM_CORE_PLUGIN_URL; ?>assets/images/stars@2x.png" width="136px" height="24px">

									<div class="thd-description"><?php echo esc_html__( 'It makes us happy to hear from our users. We would appreciate a review.', 'optimizeform-core' ); ?></div>

									<div class="thd-button-wrap">
										<a href="https://communitytheme.com/submit-review/" class="thd-button button" target="_blank">
											<?php echo esc_html__( 'Submit a Review', 'optimizeform-core' ); ?>									</a>
									</div>

									<div class="thd-line"></div>

									<div class="thd-heading"><?php echo esc_html__( 'Have an idea or feedback?', 'optimizeform-core' ); ?></div>

									<div class="thd-description"><?php echo esc_html__( 'Let us know. Wed love to hear from you.', 'optimizeform-core' ); ?></div>

									<a href="https://communitytheme.com/suggest/" class="thd-suggest-idea-link" target="_blank">
										<?php echo esc_html__( 'Suggest an idea', 'optimizeform-core' ); ?>								</a>
								</div>
							</div>
							<!-- review widget -->

							<!-- Latest blogs widget -->
							<?php 
							// $posts = community_theme_get_latest_blogs();
							$posts = array();
							if ( count( $posts ) ) {
								?> 
								<div class="thd-panel thd-panel-changelog">
									<div class="thd-panel-head">
										<h3 class="thd-panel-title"><?php echo esc_html__( 'Latest blog posts', 'optimizeform-core' ); ?></h3>
									</div>
									<div class="thd-panel-content">
										<ul>
											<?php 
											foreach ($posts as $post) {
												if ( isset( $post['title'] ) ) {
													?> 
													<li>
														<a target="_blank" href="<?php echo esc_url( $post['link'] ); ?>">
															<?php echo esc_html( $post['title']['rendered'] ); ?>
														</a>
													</li>
													<?php
												}
											}
											?>
										</ul>
									</div>
								</div>
								<?php
							}
							?>
							
							<!-- Latest blogs widget -->
						</div>
						<!-- sidebar -->
					</div>

					<!-- video tutorial -->
					<?php 
					// $videos = community_theme_get_youtube_tutorials();
                    $videos = array();
					if ( count( $videos ) ) {
						?> 
						<div class="theplus-panel-row theplus-mt-50">
							<div class="theplus-panel-col theplus-panel-col-100">
								<div class="theplus-panel-sec theplus-p-20 theplus-welcome-video">
									<div class="theplus-sec-title"><?php echo esc_html__( 'Video Tutorials', 'optimizeform-core' ); ?></div>
									<div class="theplus-sec-subtitle"><?php echo esc_html__( 'Checkout Few of our latest video tutorials', 'optimizeform-core' ); ?></div>
									<div class="theplus-sec-border"></div>
									<div class="theplus-panel-row theplus-panel-relative">
										<a href="https://www.youtube.com/playlist?list=PLFRO-irWzXaLK9H5opSt88xueTnRhqvO5 " class="theplus-more-video" target="_blank"><?php echo esc_html__( 'Our Full Playlist', 'optimizeform-core' ); ?></a>
										<?php 
										foreach ($videos as $video) {
											$video_link = isset( $video['video_link'] ) ? $video['video_link'] : '';
											$thumb_url = isset( $video['thumbnail_url'] ) ? $video['thumbnail_url'] : '';
											?> 
											<div class="theplus-panel-col theplus-panel-col-25 single-video">
												<a target="_blank" href="<?php echo esc_url( $video_link ); ?>" class="theplus-panel-video-list" target="_blank">
													<img src="<?php echo esc_url( $thumb_url ); ?>">
												</a>
											</div>
											<?php
										}
										?>
									</div>
								</div>
							</div>
						</div>
						<!-- video tutorial -->
						<?php
					}
					?>
				</div>
			</div>
			<!-- features list-->
			
		<?php
	}

    public function print_scripts() {
		do_action( 'optimizeform_core_admin_page_scripts' );
	}
}


?>