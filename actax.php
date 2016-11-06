<?php
/*
Plugin Name: Advanced Custom Taxonomies
Description: Customise WordPress with custom taxonomies
Version: 0.5.5
Author: iambriansreed
Author URI: http://iambrian.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if actax already loaded
if ( ! defined( 'ACTAX_POST_TYPE' ) ) {

	define( 'ACTAX_POST_TYPE', 'actax_content_type' );

	function actax_spl_autoload_register( $namespaces_class_name ) {

		$class_name_parts = explode( '\\', $namespaces_class_name );

		if ( $class_name_parts[0] !== 'Advanced_Custom_Taxonomies' ) {
			return false;
		}

		array_shift( $class_name_parts ); // remove namespace Advanced_Custom_Taxonomies

		$class_name = array_pop( $class_name_parts );

		$class_name_parts[] = "class-$class_name.php";

		$filename = dirname( __FILE__ ) . '/' . str_replace( '_', '-', strtolower( implode( '/', $class_name_parts ) ) );

		if ( file_exists( $filename ) ) {

			include( $filename );

			return class_exists( $class_name );
		}

		return false;
	}

	spl_autoload_register( 'actax_spl_autoload_register' );

	new \Advanced_Custom_Taxonomies\Load_Main();

}