<?php

namespace Advanced_Custom_Taxonomies;

use Advanced_Custom_Taxonomies\Admin\Dashicons;
use Advanced_Custom_Taxonomies\Admin\Fields;
use Advanced_Custom_Taxonomies\Admin\Taxonomy;

class Load_Main extends Load_Base {

	private $settings;

	function __construct() {

		$this->settings = new Settings();

		$this->add_actions( array( 'init' ) );
	}

	function init() {

		$this->register_taxonomies();


		if ( is_admin() ) {

			$dashicons = new Dashicons();
			$fields    = new Fields( $this->settings, $dashicons );
			$taxonomy  = new Taxonomy( $this, $this->settings, $fields, $dashicons );

			new Admin\Load( $this, $this->settings, $fields, $taxonomy );
		}
	}

	/**
	 * Returns all valid custom taxonomies
	 *
	 * @return array|null
	 */
	public function get_taxonomies() {

		if ( ! $this->taxonomies ) {

			$this->taxonomies = array();

			$taxonomies_data = array_merge(
				$this->get_posts_data_from_db(),
				$this->get_posts_data_from_json()
			);

			foreach ( $taxonomies_data as $taxonomy_data ) {

				if ( ! $this->is_valid_taxonomy_data( $taxonomy_data ) ) {
					continue;
				}

				$this->taxonomies[ $taxonomy_data['taxonomy'] ] = $taxonomy_data;
			}
		}

		return $this->taxonomies;
	}

	private function get_posts_data_from_db() {

		$taxonomies_data = array();

		$posts = get_posts( array( 'post_type' => ACTAX_POST_TYPE ) );

		foreach ( $posts as $post ) {
			$taxonomies_data[] = json_decode( $post->post_content, true );
		}

		return $taxonomies_data;
	}

	private function get_posts_data_from_json() {

		$json_path = $this->settings->get( 'save_json' );

		$taxonomies_data = array();

		if ( $json_path ) {

			$files = scandir( $json_path );

			foreach ( $files as $file ) {

				if ( substr( $file, - 5 ) === '.json' ) {

					$post_type_json = file_get_contents( "$json_path/$file" );

					$taxonomies_data[] = json_decode( $post_type_json, true );
				}
			}
		}

		return $taxonomies_data;
	}

	private $taxonomies = null;

	private function register_taxonomies() {

		$taxonomies_data = $this->get_taxonomies();

		foreach ( $taxonomies_data as $taxonomy_data ) {

			register_taxonomy( $taxonomy_data['taxonomy'], (array) $taxonomy_data['args']['post_types'], $taxonomy_data['args'] );
		}
	}

	private function is_valid_taxonomy_data( $taxonomy_data ) {

		return isset( $taxonomy_data['taxonomy'] )
		       && isset( $taxonomy_data['args'] )
		       && $this->is_valid_taxonomy_name( $taxonomy_data['taxonomy'] );
	}

	private function is_valid_taxonomy_name( $taxonomy_name ) {
		$error = $this->is_invalid_taxonomy_name( $taxonomy_name );

		return ! $error;
	}

	public function is_invalid_taxonomy_name( $taxonomy_name ) {

		if ( ! is_string( $taxonomy_name ) ) {
			return 'Taxonomy name must be a string.';
		}

		if ( strlen( $taxonomy_name ) < 1 || strlen( $taxonomy_name ) > 32 ) {
			return 'Taxonomy name must not be greater 20 characters.';
		}

		if ( in_array( $taxonomy_name, $this->get_wp_reserved_terms() ) ) {
			return 'Taxonomy name must not use a <a target="_blank" href="https://codex.wordpress.org/Reserved_Terms">reserved term</a>.';
		}

		if ( $taxonomy_name !== str_replace( '-', '_', sanitize_title( $taxonomy_name ) ) ) {
			return 'Taxonomy name must not contain capital letters or spaces.';
		}

		return false;
	}

	private function get_wp_reserved_terms() {

		$wp = new \WP();

		return array_merge(
			$wp->public_query_vars,
			$wp->private_query_vars,
			// other reserved terms found here: https://codex.wordpress.org/Reserved_Terms
			array(
				'category',
				'comments_popup',
				'custom',
				'customize_messenger_channel',
				'customized',
				'debug',
				'link_category',
				'nav_menu',
				'nonce',
				'post',
				'post_tag',
				'terms',
				'theme',
				'type',
			) );
	}
}