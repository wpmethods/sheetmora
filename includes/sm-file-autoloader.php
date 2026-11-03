<?php
/**
 * Load the plugin's classes.
 *
 * @package SM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class_name ) {
		$classes = array(
			'SM\\SM_To_Google_Sheets' => 'class-sm-to-google-sheets.php',
			'SM\\SM_Script_Generator' => 'class-sm-script-generator.php',
		);
		if ( isset( $classes[ $class_name ] ) ) {
			require_once SM_PATH . 'includes/' . $classes[ $class_name ];
		}
	}
);
