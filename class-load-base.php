<?php

namespace Advanced_Custom_Taxonomies;

abstract class Load_Base {

	function __construct() {
	}

	function add_actions( $actions ) {
		$this->add_hook( 'action', $actions );
	}

	function add_filters( $filters ) {
		$this->add_hook( 'filter', $filters );
	}

	private function add_hook( $hook_type, $hooks ) {

		$add_callback = 'add_' . $hook_type;

		foreach ( $hooks as $hook ) {

			$tag           = $hook;
			$callback      = $hook;
			$priority      = 10;
			$accepted_args = 1;

			if ( is_array( $hook ) ) {

				$tag           = $hook[0];
				$callback      = $hook[0];
				$priority      = isset( $hook[1] ) ? intval( $hook[1] ) : $priority;
				$accepted_args = isset( $hook[2] ) ? intval( $hook[2] ) : $accepted_args;
			}

			$add_callback( $tag, array( $this, $callback ), $priority, $accepted_args );
		}
	}
}