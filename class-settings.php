<?php

namespace Advanced_Custom_Taxonomies;

class Settings {

	private $defaults;

	function __construct() {

		$this->defaults = array(
			'show_admin'   => true,
			'capability'   => 'manage_options',
			'save_json'  => null
		);
	}

	public function get( $name ) {
		return apply_filters( "actax/settings/{$name}", $this->defaults[ $name ] );
	}

	public function set( $name, $value ) {
		return ( $this->defaults[ $name ] = $value );
	}
}