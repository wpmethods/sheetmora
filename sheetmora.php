<?php
/**
 * Plugin Name: SheetMora
 * Plugin URI: https://wpmethods.com/product/sheetmora/
 * Description: Send WooCommerce orders to a single Google Sheet when selected order statuses change.
 * Version: 1.0.0
 * Author: WP Methods
 * Author URI: https://wpmethods.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sheetmora
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 *
 * @package SM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SM_VERSION', '1.0.0' );
define( 'SM_FILE', __FILE__ );
define( 'SM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SM_URL', plugin_dir_url( __FILE__ ) );

require_once SM_PATH . 'includes/sm-file-autoloader.php';

register_activation_hook( __FILE__, array( 'SM\\SM_To_Google_Sheets', 'activate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SM_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		new SM\SM_To_Google_Sheets();
	},
	20
);
