<?php
/**
 * WooCommerce order delivery and administration.
 *
 * @package SM
 */

namespace SM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Provides the single-sheet integration and extension hooks. */
class SM_To_Google_Sheets {
	/** Register integration hooks. */
	public function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( SM_FILE ), array( $this, 'plugin_action_links' ) );
		add_action( 'admin_menu', array( $this, 'sm_add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'sm_settings_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'sm_admin_scripts' ) );
		add_action( 'wp_ajax_sm_generate_google_script', array( $this, 'sm_generate_google_script_ajax' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'sm_send_order_to_sheets' ), 10, 4 );
		add_action( 'sm_deliver_order', array( $this, 'deliver_order' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
		do_action( 'sm_integration_ready', $this );
	}

	/**
	 * Link to settings from this plugin's action row.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		if ( current_user_can( 'manage_options' ) ) {
			$links = array_merge( array( 'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=sheetmora' ) ) . '">' . esc_html__( 'Settings', 'sheetmora' ) . '</a>' ), $links );
		}
		return $links;
	}

	/** Set defaults only when an option does not yet exist. */
	public static function activate() {
		add_option( 'sm_gs_order_statuses', array( 'processing', 'completed' ) );
		add_option( 'sm_gs_product_categories', array() );
		add_option( 'sm_gs_script_url', '' );
		self::get_secret();
	}

	/**
	 * Return a stable receiver credential, including on upgrades without reactivation.
	 *
	 * @return string
	 */
	public static function get_secret() {
		$secret = get_option( 'sm_gs_shared_secret', '' );
		if ( ! is_string( $secret ) || strlen( $secret ) < 32 ) {
			$secret = wp_generate_password( 64, false, false );
			if ( ! add_option( 'sm_gs_shared_secret', $secret, '', false ) ) {
				$stored = get_option( 'sm_gs_shared_secret', '' );
				if ( is_string( $stored ) && strlen( $stored ) >= 32 ) {
					return $stored;
				}
				update_option( 'sm_gs_shared_secret', $secret, false );
			}
		}
		return $secret;
	}

	/**
	 * Available field definitions. Stable English labels keep existing schemas independent of admin locale.
	 *
	 * @return array
	 */
	public function get_fields() {
		$labels   = array(
			'order_id'                   => 'Order ID',
			'order_amount_with_currency' => 'Order Amount',
			'order_currency'             => 'Order Currency',
			'billing_name'               => 'Billing Name',
			'billing_email'              => 'Email Address',
			'billing_phone'              => 'Phone',
			'billing_address'            => 'Billing Address',
			'payment_method_title'       => 'Payment Method',
			'order_status'               => 'Order Status',
			'order_date'                 => 'Order Date',
			'product_name'               => 'Product Name',
			'product_categories'         => 'Product Categories',
			'product_type'               => 'Product Type',
			'shipping_method'            => 'Shipping Method',
			'customer_note'              => 'Customer Note',
			'total_discount'             => 'Total Discount',
		);
		$required = array( 'order_id', 'order_amount_with_currency', 'billing_name', 'order_status', 'order_date', 'product_name' );
		$fields   = array();
		foreach ( $labels as $key => $label ) {
			$fields[ $key ] = array(
				'label'    => $label,
				'required' => in_array( $key, $required, true ),
			);
		}
		return apply_filters( 'sm_available_fields', $fields );
	}

	/**
	 * Validate fields and restore mandatory columns in a stable order.
	 *
	 * @param mixed $input Requested field keys.
	 * @return array
	 */
	public function sanitize_fields( $input ) {
		$input  = is_array( $input ) ? array_filter( $input, 'is_string' ) : array();
		$fields = array();
		foreach ( $this->get_fields() as $key => $info ) {
			if ( ! empty( $info['required'] ) || in_array( $key, $input, true ) ) {
				$fields[] = $key;
			}
		}
		return $fields;
	}

	/**
	 * Validate trigger statuses against registered WooCommerce statuses.
	 *
	 * @param mixed $input Statuses.
	 * @return array
	 */
	public function sanitize_statuses( $input ) {
		$allowed = function_exists( 'wc_get_order_statuses' ) ? array_map(
			static function ( $key ) {
				return substr( $key, 3 );
			},
			array_keys( wc_get_order_statuses() )
		) : array();
		return array_values( array_intersect( $allowed, is_array( $input ) ? array_filter( $input, 'is_string' ) : array() ) );
	}

	/**
	 * Validate category IDs without accepting nested input.
	 *
	 * @param mixed $input Category IDs.
	 * @return array
	 */
	public function sanitize_categories( $input ) {
		$ids = is_array( $input ) ? array_map( 'absint', array_filter( $input, 'is_scalar' ) ) : array();
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Validate a deployed Google Apps Script URL; never allow arbitrary hosts or credentials.
	 *
	 * @param mixed $input URL.
	 * @return string
	 */
	public static function validate_script_url( $input ) {
		if ( ! is_string( $input ) ) {
			return '';
		}
		$parts = wp_parse_url( trim( $input ) );
		if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || 'script.google.com' !== ( $parts['host'] ?? '' ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) || ! preg_match( '#^/macros/s/[a-zA-Z0-9_-]+/exec$#D', $parts['path'] ?? '' ) ) {
			return '';
		}
		return esc_url_raw( trim( $input ), array( 'https' ) );
	}

	/**
	 * Reject invalid settings with an actionable message and retain the old destination.
	 *
	 * @param mixed $input URL setting.
	 * @return string
	 */
	public function sanitize_script_url( $input ) {
		$url = self::validate_script_url( $input );
		if ( ! empty( $input ) && ! $url ) {
			add_settings_error( 'sm_gs_script_url', 'sm_invalid_url', __( 'Use an HTTPS Google Apps Script deployment URL ending in /exec.', 'sheetmora' ) );
			return self::validate_script_url( get_option( 'sm_gs_script_url', '' ) );
		}
		return $url;
	}

	/**
	 * Category terms for each order, including parent products of variations.
	 *
	 * @param \WC_Order $order Order.
	 * @return array
	 */
	public static function order_categories( $order ) {
		$categories = array();
		foreach ( $order->get_items() as $item ) {
			$terms = wp_get_post_terms( $item->get_product_id(), 'product_cat' );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$categories[ $term->term_id ] = array(
					'id'   => (int) $term->term_id,
					'name' => $term->name,
				);
			}
		}
		return array_values( $categories );
	}

	/**
	 * Resolve selected values using WooCommerce CRUD APIs for HPOS compatibility.
	 *
	 * @param string    $key Field key.
	 * @param \WC_Order $order Order.
	 * @return mixed
	 */
	public function field_value( $key, $order ) {
		switch ( $key ) {
			case 'order_id':
				return $order->get_id();
			case 'order_amount_with_currency':
				return html_entity_decode( get_woocommerce_currency_symbol( $order->get_currency() ), ENT_QUOTES, 'UTF-8' ) . $order->get_total();
			case 'order_currency':
				return $order->get_currency();
			case 'billing_name':
				return trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			case 'billing_email':
				return $order->get_billing_email();
			case 'billing_phone':
				return $order->get_billing_phone();
			case 'billing_address':
				return implode( ', ', array_filter( array( $order->get_billing_address_1(), $order->get_billing_address_2(), $order->get_billing_city(), $order->get_billing_state(), $order->get_billing_postcode(), $order->get_billing_country() ) ) );
			case 'payment_method_title':
				return $order->get_payment_method_title();
			case 'order_status':
				return $order->get_status();
			case 'order_date':
				$date = $order->get_date_created();
				return $date ? $date->date( 'Y-m-d H:i:s' ) : '';
			case 'product_name':
				return implode(
					', ',
					array_map(
						static function ( $item ) {
							return $item->get_name();
						},
						$order->get_items()
					)
				);
			case 'product_categories':
				return implode( ', ', array_column( self::order_categories( $order ), 'name' ) );
			case 'product_type':
				$types = array();
				foreach ( $order->get_items() as $item ) {
					$product = $item->get_product();
					$types[] = $product ? $product->get_type() : 'deleted';
				}
				return implode( ', ', array_unique( $types ) );
			case 'shipping_method':
				$shipping = array();
				$symbol   = html_entity_decode( get_woocommerce_currency_symbol( $order->get_currency() ), ENT_QUOTES, 'UTF-8' );
				foreach ( $order->get_shipping_methods() as $method ) {
					$name   = $method->get_name();
					$amount = (float) $method->get_total();
					if ( 0.0 !== $amount ) {
						$name .= ' - ' . $symbol . number_format( $amount, wc_get_price_decimals(), '.', '' );
					}
					$shipping[] = $name;
				}
				return implode( ', ', $shipping );
			case 'customer_note':
				return $order->get_customer_note();
			case 'total_discount':
				return html_entity_decode( get_woocommerce_currency_symbol( $order->get_currency() ), ENT_QUOTES, 'UTF-8' ) . $order->get_discount_total();
			default:
				return apply_filters( 'sm_field_value', null, $key, $order );
		}
	}

	/**
	 * Check current trigger settings, including when a queued job runs.
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	private function eligible( $order ) {
		if ( ! $order ) {
			return false;
		}
		$selected = $this->sanitize_categories( get_option( 'sm_gs_product_categories', array() ) );
		$eligible = in_array( $order->get_status(), $this->sanitize_statuses( get_option( 'sm_gs_order_statuses', array( 'processing', 'completed' ) ) ), true )
			&& ( ! $selected || (bool) array_intersect( $selected, array_column( self::order_categories( $order ), 'id' ) ) );
		return (bool) apply_filters( 'sm_order_is_eligible', $eligible, $order );
	}

	/**
	 * Queue an order ID, keeping customer data and credentials out of scheduler arguments.
	 *
	 * @param int       $order_id Order ID.
	 * @param string    $old_status Previous status.
	 * @param string    $new_status New status.
	 * @param \WC_Order $order Order.
	 */
	public function sm_send_order_to_sheets( $order_id, $old_status, $new_status, $order ) {
		if ( $this->eligible( $order ) ) {
			$targets = apply_filters( 'sm_order_job_targets', array( '' ), $order );
			foreach ( array_unique( is_array( $targets ) ? array_filter( $targets, 'is_string' ) : array() ) as $target_id ) {
				if ( '' === $target_id || preg_match( '/^[a-f0-9]{64}$/D', $target_id ) ) {
					$this->schedule( array( absint( $order_id ), 0, $target_id ) );
				}
			}
		}
	}

	/**
	 * Schedule non-blocking work through Action Scheduler or WordPress cron.
	 *
	 * @param array $args Worker arguments.
	 * @param int   $delay Delay in seconds.
	 */
	private function schedule( $args, $delay = 0 ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + $delay, 'sm_deliver_order', $args, 'sm', true );
		} elseif ( ! wp_next_scheduled( 'sm_deliver_order', $args ) ) {
			wp_schedule_single_event( time() + $delay, 'sm_deliver_order', $args );
		}
	}

	/**
	 * Deliver only current selected data; retry failed destinations without sleeping in PHP.
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $attempt Retry number.
	 * @param string $target_id Hash of one failed destination, or empty for all destinations.
	 */
	public function deliver_order( $order_id, $attempt = 0, $target_id = '' ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $this->eligible( $order ) ) {
			return;
		}
		$data = array();
		foreach ( $this->sanitize_fields( get_option( 'sm_gs_selected_fields', array_keys( $this->get_fields() ) ) ) as $key ) {
			$data[ $key ] = $this->field_value( $key, $order );
		}
		$data    = apply_filters( 'sm_order_data', $data, $order );
		$url     = self::validate_script_url( get_option( 'sm_gs_script_url', '' ) );
		$targets = $url ? array(
			array(
				'url'      => $url,
				'receiver' => true,
			),
		) : array();
		$targets = apply_filters( 'sm_delivery_targets', $targets, $order );
		$seen    = array();
		foreach ( $targets as $target ) {
			// Installed extensions can supply a transport without placing its implementation here.
			if ( isset( $target['id'], $target['callback'] ) && is_string( $target['id'] ) && is_callable( $target['callback'] ) ) {
				$id = hash( 'sha256', 'extension:' . $target['id'] );
				if ( isset( $seen[ $id ] ) || ( $target_id && $target_id !== $id ) ) {
					continue;
				}
				$seen[ $id ] = true;
				try {
					$delivered = call_user_func( $target['callback'], $data, $order );
				} catch ( \Throwable $error ) {
					$delivered = false;
				}
				if ( true !== $delivered && $attempt < 2 ) {
					$this->schedule( array( absint( $order_id ), $attempt + 1, $id ), 60 * ( $attempt + 1 ) );
				} elseif ( true !== $delivered && function_exists( 'wc_get_logger' ) ) {
					wc_get_logger()->error(
						'An extension could not deliver this order after three attempts. Check its connection settings.',
						array(
							'source'   => 'sm',
							'order_id' => absint( $order_id ),
						)
					);
				}
				continue;
			}
			$is_receiver = ! empty( $target['receiver'] );
			$url         = $is_receiver ? self::validate_script_url( $target['url'] ?? '' ) : esc_url_raw( $target['url'] ?? '', array( 'https' ) );
			if ( ! $url || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
				continue;
			}
			$id = hash( 'sha256', $url . ( $is_receiver ? ':receiver' : ':external' ) );
			if ( isset( $seen[ $id ] ) || ( $target_id && $target_id !== $id ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$body        = $is_receiver ? array(
				'token' => self::get_secret(),
				'data'  => $data,
			) : $data;
			$response    = wp_safe_remote_post(
				$url,
				array(
					'timeout'             => 20,
					'redirection'         => 5,
					'headers'             => array( 'Content-Type' => 'application/json' ),
					'body'                => wp_json_encode( $body ),
					'data_format'         => 'body',
					'limit_response_size' => 8192,
				)
			);
			$code        = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
			$ok          = $code >= 200 && $code < 300;
			if ( $ok && $is_receiver ) {
				$result = json_decode( wp_remote_retrieve_body( $response ), true );
				$ok     = is_array( $result ) && 'success' === ( $result['status'] ?? '' );
			}
			if ( ! $ok && $attempt < 2 ) {
				$this->schedule( array( absint( $order_id ), $attempt + 1, $id ), 60 * ( $attempt + 1 ) );
			} elseif ( ! $ok && function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error(
					'Order delivery failed after three attempts. Check the deployment, authentication and sheet headers.',
					array(
						'source'      => 'sm',
						'order_id'    => absint( $order_id ),
						'http_status' => $code,
					)
				);
			}
		}
	}

	/** Display a dependency notice without calling unavailable WooCommerce APIs. */
	public function woocommerce_notice() {
		if ( ! class_exists( 'WooCommerce' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Send SheetMora requires WooCommerce to be active.', 'sheetmora' ) . '</p></div>';
		}
	}

	/** Register the settings menu. */
	public function sm_add_admin_menu() {
		add_menu_page( __( 'SheetMora', 'sheetmora' ), __( 'SheetMora', 'sheetmora' ), 'manage_options', 'sheetmora', array( $this, 'sm_settings_page' ), 'dashicons-media-spreadsheet' );
	}

	/**
	 * Load readable source assets only on this settings screen.
	 *
	 * @param string $hook Screen hook.
	 */
	public function sm_admin_scripts( $hook ) {
		if ( 'toplevel_page_sheetmora' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'sm-admin', SM_URL . 'assets/css/sm-admin.css', array( 'dashicons' ), SM_VERSION . '.' . filemtime( SM_PATH . 'assets/css/sm-admin.css' ) );
		wp_enqueue_script( 'sm-admin', SM_URL . 'assets/js/sm-admin.js', array( 'jquery' ), SM_VERSION . '.' . filemtime( SM_PATH . 'assets/js/sm-admin.js' ), true );
		wp_localize_script(
			'sm-admin',
			'sm_gs',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'sm_generate_script_nonce' ),
				'generating' => __( 'Generating…', 'sheetmora' ),
				'error'      => __( 'Could not generate the script. Save settings and try again.', 'sheetmora' ),
			)
		);
	}

	/** Register only implemented settings through the Settings API. */
	public function sm_settings_init() {
		register_setting(
			'sm_gs_settings',
			'sm_gs_order_statuses',
			array(
				'sanitize_callback' => array( $this, 'sanitize_statuses' ),
				'default'           => array( 'processing', 'completed' ),
			)
		);
		register_setting(
			'sm_gs_settings',
			'sm_gs_product_categories',
			array(
				'sanitize_callback' => array( $this, 'sanitize_categories' ),
				'default'           => array(),
			)
		);
		register_setting(
			'sm_gs_settings',
			'sm_gs_selected_fields',
			array(
				'sanitize_callback' => array( $this, 'sanitize_fields' ),
				'default'           => array_keys( $this->get_fields() ),
			)
		);
		register_setting(
			'sm_gs_settings',
			'sm_gs_script_url',
			array(
				'sanitize_callback' => array( $this, 'sanitize_script_url' ),
				'default'           => '',
			)
		);
		add_settings_section( 'sm_gs_section', '', '__return_false', 'sm_gs_settings' );
		$fields = array(
			'order_statuses'     => __( 'Trigger Order Statuses', 'sheetmora' ),
			'product_categories' => __( 'Product Categories Filter', 'sheetmora' ),
			'sheet_mode'         => __( 'Sheet Mode', 'sheetmora' ),
			'selected_fields'    => __( 'Checkout Fields', 'sheetmora' ),
			'script_url'         => __( 'Google Apps Script URL', 'sheetmora' ),
		);
		foreach ( $fields as $key => $label ) {
			add_settings_field( 'sm_gs_' . $key, $label, array( $this, 'sm_' . $key . '_render' ), 'sm_gs_settings', 'sm_gs_section' );
		}
		do_action( 'sm_register_settings', $this );
	}

	/** Render status checkboxes. */
	public function sm_order_statuses_render() {
		$selected = $this->sanitize_statuses( get_option( 'sm_gs_order_statuses', array( 'processing', 'completed' ) ) );
		echo '<p class="description sm-field-intro">' . esc_html__( 'Send an order when it moves into one of these statuses.', 'sheetmora' ) . '</p><div class="sm-choices">';
		echo '<input type="hidden" name="sm_gs_order_statuses[]" value="">';
		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			echo '</div>';
			return;
		}
		foreach ( wc_get_order_statuses() as $key => $label ) {
			$key = substr( $key, 3 );
			echo '<label class="sm-choice"><input type="checkbox" name="sm_gs_order_statuses[]" value="' . esc_attr( $key ) . '" ' . checked( in_array( $key, $selected, true ), true, false ) . '> ' . esc_html( $label ) . '</label>';
		}
		echo '</div>';
	}

	/** Render category filters with safe handling for unavailable taxonomies. */
	public function sm_product_categories_render() {
		$selected = $this->sanitize_categories( get_option( 'sm_gs_product_categories', array() ) );
		$terms    = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		echo '<input type="hidden" name="sm_gs_product_categories[]" value="">';
		if ( ! is_wp_error( $terms ) ) {
			echo '<div class="sm-category-list">';
			foreach ( $terms as $term ) {
				echo '<label class="sm-choice"><input type="checkbox" name="sm_gs_product_categories[]" value="' . esc_attr( $term->term_id ) . '" ' . checked( in_array( (int) $term->term_id, $selected, true ), true, false ) . '> ' . esc_html( $term->name ) . '</label>';
			}
			if ( ! $terms ) {
				echo '<p class="description">' . esc_html__( 'No product categories yet. All orders are included until you select a category.', 'sheetmora' ) . '</p>';
			}
			echo '</div>';
		}
		echo '<p class="description">' . esc_html__( 'Send orders containing at least one selected category. Leave empty to include all categories.', 'sheetmora' ) . '</p>';
	}

	/** Render the working single-sheet mode or delegate to an installed extension. */
	public function sm_sheet_mode_render() {
		if ( has_action( 'sm_render_sheet_mode' ) ) {
			do_action( 'sm_render_sheet_mode' );
			return;
		}
		echo '<div class="sm-single-mode"><span class="sm-mode-icon dashicons dashicons-media-spreadsheet" aria-hidden="true"></span><div><div class="sm-mode-title"><strong>' . esc_html__( 'Single Sheet', 'sheetmora' ) . '</strong><span class="sm-badge sm-badge-ready">' . esc_html__( 'Included in Free', 'sheetmora' ) . '</span></div><p class="description">' . esc_html__( 'All matching orders go to one WooCommerce Orders tab. Existing Order IDs are updated automatically.', 'sheetmora' ) . '</p></div></div>';
	}

	/** Render selectable implemented fields without disabled premium controls. */
	public function sm_selected_fields_render() {
		$selected = $this->sanitize_fields( get_option( 'sm_gs_selected_fields', array_keys( $this->get_fields() ) ) );
		$icons    = array(
			'order_id'                   => 'cart',
			'order_amount_with_currency' => 'money-alt',
			'order_currency'             => 'money',
			'billing_name'               => 'admin-users',
			'billing_email'              => 'email',
			'billing_phone'              => 'phone',
			'billing_address'            => 'location',
			'payment_method_title'       => 'bank',
			'order_status'               => 'yes-alt',
			'order_date'                 => 'calendar-alt',
			'product_name'               => 'products',
			'product_categories'         => 'category',
			'product_type'               => 'tag',
			'shipping_method'            => 'car',
			'customer_note'              => 'edit',
			'total_discount'             => 'tickets-alt',
		);
		echo '<div class="sm-field-toolbar"><span class="dashicons dashicons-search" aria-hidden="true"></span><input type="search" id="sm-field-search" aria-label="' . esc_attr__( 'Search fields', 'sheetmora' ) . '" placeholder="' . esc_attr__( 'Search checkout fields…', 'sheetmora' ) . '"></div><div class="sm-fields-grid">';
		foreach ( $this->get_fields() as $key => $info ) {
			$required = ! empty( $info['required'] );
			$icon     = $icons[ $key ] ?? 'editor-table';
			echo '<div class="sm-field-item"><label><input type="checkbox" name="sm_gs_selected_fields[]" value="' . esc_attr( $key ) . '" ' . checked( in_array( $key, $selected, true ), true, false ) . ' ' . disabled( $required, true, false ) . '><span class="dashicons dashicons-' . esc_attr( $icon ) . '" aria-hidden="true"></span><span class="sm-field-name">' . esc_html( $info['label'] ) . '</span>';
			if ( $required ) {
				echo ' <span class="sm-required-badge">' . esc_html__( 'Required', 'sheetmora' ) . '</span>';
				echo '<input type="hidden" name="sm_gs_selected_fields[]" value="' . esc_attr( $key ) . '">';
			}
			echo '</label></div>';
		}
		echo '</div><p id="sm-field-empty" class="sm-empty-state" role="status" hidden>' . esc_html__( 'No matching fields. Try another search.', 'sheetmora' ) . '</p><p class="description">' . esc_html__( 'Required fields are always included. Save settings before generating your script.', 'sheetmora' ) . '</p>';
	}

	/** Render the single destination or delegate to an extension. */
	public function sm_script_url_render() {
		if ( has_action( 'sm_render_script_urls' ) ) {
			do_action( 'sm_render_script_urls' );
			return;
		}
		echo '<input type="url" class="large-text" name="sm_gs_script_url" value="' . esc_attr( self::validate_script_url( get_option( 'sm_gs_script_url', '' ) ) ) . '" placeholder="https://script.google.com/macros/s/DEPLOYMENT_ID/exec">';
		echo '<p class="description">' . esc_html__( 'Paste the /exec URL from your Google Apps Script web app deployment.', 'sheetmora' ) . '</p>';
	}

	/** Generate only the saved schema; WordPress verifies both nonce and administrator capability. */
	public function sm_generate_google_script_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Administrator permission is required.', 'sheetmora' ) ), 403 );
		}
		check_ajax_referer( 'sm_generate_script_nonce', 'nonce' );
		$fields    = $this->sanitize_fields( get_option( 'sm_gs_selected_fields', array_keys( $this->get_fields() ) ) );
		$generator = new SM_Script_Generator( $this->get_fields() );
		nocache_headers();
		wp_send_json_success( array( 'script' => $generator->generate_script( $fields ) ) );
	}

	/** Render settings and an informational add-on card separate from working controls. */
	public function sm_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$selected_fields = $this->sanitize_fields( get_option( 'sm_gs_selected_fields', array_keys( $this->get_fields() ) ) );
		$statuses        = $this->sanitize_statuses( get_option( 'sm_gs_order_statuses', array( 'processing', 'completed' ) ) );
		$extended        = has_action( 'sm_render_configuration' );
		$overview        = apply_filters(
			'sm_admin_overview',
			array(
				array(
					'value'  => count( $selected_fields ),
					'label'  => __( 'Selected fields', 'sheetmora' ),
					'icon'   => 'editor-table',
					'metric' => 'selected',
				),
				array(
					'value'  => count( $statuses ),
					'label'  => __( 'Trigger statuses', 'sheetmora' ),
					'icon'   => 'update',
					'metric' => 'trigger',
				),
				array(
					'value' => $extended ? __( 'Extended', 'sheetmora' ) : __( 'Single Sheet', 'sheetmora' ),
					'label' => __( 'Sheet options', 'sheetmora' ),
					'icon'  => 'media-spreadsheet',
				),
			)
		);
		require SM_PATH . 'includes/views/sm-settings-page.php';
	}
}
