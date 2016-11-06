<?php

namespace Advanced_Custom_Taxonomies\Admin {

	use Advanced_Custom_Taxonomies\Load_Main;
	use Advanced_Custom_Taxonomies\Load_Base;
	use Advanced_Custom_Taxonomies\Settings;

	class Load extends Load_Base {

		private $settings;
		private $loader;
		private $fields;
		private $taxonomy;

		public function __construct(
			Load_Main $loader,
			Settings $settings,
			Fields $fields,
			Taxonomy $taxonomy
		) {

			$this->loader   = $loader;
			$this->settings = $settings;
			$this->fields   = $fields;
			$this->taxonomy = $taxonomy;

			$cap = $settings->get( 'capability' );

			register_post_type( ACTAX_POST_TYPE, array(
				'labels'          => array(
					'name'               => __( 'Taxonomies ', 'actax' ),
					'singular_name'      => __( 'Taxonomy', 'actax' ),
					'add_new'            => __( 'Add New', 'actax' ),
					'add_new_item'       => __( 'Add New Taxonomy', 'actax' ),
					'edit_item'          => __( 'Edit Taxonomy', 'actax' ),
					'new_item'           => __( 'New Taxonomy', 'actax' ),
					'view_item'          => __( 'View Taxonomy', 'actax' ),
					'search_items'       => __( 'Search Taxonomies ', 'actax' ),
					'not_found'          => __( 'No Taxonomies  found', 'actax' ),
					'not_found_in_trash' => __( 'No Taxonomies  found in Trash', 'actax' ),
				),
				'public'          => true,
				'show_ui'         => true,
				'_builtin'        => false,
				'capability_type' => 'post',
				'capabilities'    => array(
					'edit_post'    => $cap,
					'delete_post'  => $cap,
					'edit_posts'   => $cap,
					'delete_posts' => $cap,
				),
				'hierarchical'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => false,
				'show_in_menu'    => false
			) );

			$this->add_actions( array(
				'admin_notices',
				'admin_head',
				'save_post',
				'add_meta_boxes',
				'admin_menu',
				'wp_ajax_advanced_custom_post_types'
			) );

			$this->add_filters( array(
				'post_updated_messages',
				array( 'post_row_actions', 10, 2 ),
				'enter_title_here'
			) );

		}


		/**
		 * action callback: display all user's notices
		 */
		public function admin_notices() {

			$notices = Notices::get_all();

			if ( count( $notices ) ) {

				foreach ( $notices as $notice ) {
					printf(
						'<div class="%1$s"><p>%2$s</p></div>',
						'notice notice-' . $notice->type . ( $notice->is_dismissible ? ' is-dismissible' : '' ),
						$notice->message
					);
				}

				Notices::remove_all();
			}
		}

		/**
		 * action callback
		 */
		public function admin_head() {

			?>
			<style>
				/* dashboard_right_now */
				<?php foreach ( $this->loader->get_taxonomies() as $post_type )
				{
					if ( $post_type['args']['public'] )
					{
				?>
				#dashboard_right_now .<?php echo $post_type['post_type']; ?>-count a:before {
					content: "\f<?php echo $post_type['args']['dashicon_unicode_number']; ?>";
				}

				<?php
					}
				}
				?>
			</style>
			<script>
				var actax;
				actax = new function actaxClass() {

					return {
						conditional_logic: new List()
					};

					function List() {

						var items = [];
						this.Add = function addItem(item) {
							items.push(item);
						};
						this.Items = function getItems() {
							return jQuery.extend({}, items);
						};
					}

				};
			</script>
			<?php
		}

		/**
		 * action callback
		 *
		 * @param $post_id
		 */
		public function save_post( $post_id ) {

			global $post;

			$is_doing_autosave  = defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
			$is_actax_post_type = ACTAX_POST_TYPE === (string) get_post_type( $post_id );
			$is_published       = 'publish' === (string) get_post_status( $post_id );

			if ( ! $is_actax_post_type ) {
				// not an taxonomy to edit
				return;

			} else if ( $is_doing_autosave || ! $is_published ) {
				// is a taxonomy to edit but it's an autosave or not published

				// post has been saved before
				if ( is_a( $post, 'WP_Post' ) ) {
					delete_option( $post->post_name );
				}

				return;
			}

			remove_action( 'save_post', array( $this, 'save_post' ) );
			$this->taxonomy->save( $post );
			add_action( 'save_post', array( $this, 'save_post' ) );

		}

		/**
		 * action callback
		 */
		public function add_meta_boxes() {

			if ( ACTAX_POST_TYPE !== get_post_type() ) {
				return;
			}

			new Meta_Boxes( $this->fields );
		}

		/**
		 * action callback
		 */
		public function admin_menu() {

			if ( $this->settings->get( 'show_admin' ) ) {

				$slug = 'edit.php?post_type=' . ACTAX_POST_TYPE;

				$capability = $this->settings->get( 'capability' );

				add_menu_page( __( "Taxonomies ", 'actax' ), __( "Taxonomies ", 'actax' ),
					$capability, $slug, '',
					'dashicons-tag',
					'81.026' );

				add_submenu_page( $slug, __( 'Taxonomies ', 'actax' ), __( 'Taxonomies ', 'actax' ),
					$capability,
					$slug );

				add_submenu_page( $slug,
					__( 'Add New', 'actax' ),
					__( 'Add New', 'actax' ),
					$capability,
					'post-new.php?post_type=' . ACTAX_POST_TYPE
				);
			}
		}

		public function wp_ajax_advanced_custom_post_types() {

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'advanced_custom_post_types' ) ) {
				exit( "No naughty business please" );
			}

			if ( isset( $_REQUEST['export'] ) ) {
				$this->export( $_REQUEST['export'] );
			}
		}

		public function export( $post_id ) {

			header( "Content-Type: text/plain" );

			$export_post = get_post( $post_id );

			$post_data = json_decode( $export_post->post_content, true );

			$args = var_export( $post_data['args'], 1 );

			$function_name = "init_register_post_type_{$post_data['post_type']}";

			echo "/* Exported from Advanced_Custom_Taxonomies */
			
add_action( 'init', '$function_name' );

function $function_name(){

register_post_type( '{$post_data['post_type']}', {$args});

}";
			exit;

		}

		public function enter_title_here( $title ) {

			$screen = get_current_screen();

			$post_types = $this->loader->get_taxonomies();

			if ( array_key_exists( $screen->post_type, $post_types ) ) {

				$args = $post_types[ $screen->post_type ]['args'];

				$name = strtolower( $args['singular_name'] );

				$title = "Enter $name name";
			}

			return $title;
		}

		/**
		 * @param $actions
		 * @param $post
		 *
		 * @return mixed
		 */
		public function post_row_actions( $actions, $post ) {

			if ( $post->post_type === ACTAX_POST_TYPE ) {

				$nonce = wp_create_nonce( 'advanced_custom_post_types' );
				$url   = admin_url( "admin-ajax.php?action=advanced_custom_post_types&nonce=$nonce&export={$post->ID}" );

				$actions['export_php'] = "<a href=\"$url\" target=\"_blank\">Export</a>";
			}

			return $actions;
		}

		/**
		 * @param $messages
		 */
		public function post_updated_messages( $messages ) {

			global $post;

			$messages[ ACTAX_POST_TYPE ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( 'Taxonomy updated.' ),
				2  => __( 'Custom field updated.' ),
				3  => __( 'Custom field deleted.' ),
				4  => __( 'Taxonomy updated.' ),
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Taxonomy restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => __( 'Taxonomy published.' ),
				7  => __( 'Taxonomy saved.' ),
				8  => __( 'Taxonomy submitted.' ),
				9  => sprintf(
					__( 'Taxonomy scheduled for: <strong>%1$s</strong>.' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
				),
				10 => __( 'Taxonomy draft updated.' )
			);

			return $messages;
		}

	}
}
