<?php
/**
 * Plugin Name: Gravity Forms User Populate Add On
 * Plugin URI: https://github.com/joshuadavidnelson/gravity-forms-user-populate
 * Description: Populate the drop-down menu with users
 * Version: 1.0.0
 * Author: Joshua David Nelson
 * Author URI: josh@joshuadnelson.com
 * GitHub Plugin URI: https://github.com/joshuadavidnelson/gravity-forms-user-populate
 * GitHub Branch: master
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main GF_User_Populate Class
 *
 * @since 1.0.0
 */
if( ! class_exists( 'GF_User_Populate' ) ) {
	class GF_User_Populate {
		
		// Main instance variable
		var $instance;
		
		// The ACF Gallery field id
		private $acf_field_id = 'field_546d0ad42e7f0';
		
		// The Gravity Forms gallery field id
		private $images_gf_field_id = 27;
		
		// The Gravity Forms author id
		private $author_gf_field_id = 23;
		
		// The Gravity Forms conditional author field id ("yes" if existing author)
		private $author_conditional_gf_field_id = 19;

 		/**
 		 * Start the engine
 		 *
 		 * @since 1.0.0
 		 * @return void
 		 */
 		function __construct() {
			$this->instance =& $this;
		
			$this->setup_constants();
			$this->includes();
 		}
		
 		/**
 		 * Setup plugin constants
 		 *
 		 * @since 1.0.0
 		 * @access private
 		 * @return void
 		 */
 		private function setup_constants() {

 			// Plugin version
 			if ( ! defined( 'GFUP_VERSION' ) ) {
 				define( 'GFUP_VERSION', '1.0.0' );
 			}

 			// Plugin Folder Path
 			if ( ! defined( 'GFUP_DIR' ) ) {
 				define( 'GFUP_DIR', plugin_dir_path( __FILE__ ) );
 			}

 			// Plugin Folder URL
 			if ( ! defined( 'GFUP_URL' ) ) {
 				define( 'GFUP_URL', plugin_dir_url( __FILE__ ) );
 			}
			
 		}

 		/**
 		 * Include required files and starts the plugin
 		 *
 		 * @since 1.0.0
 		 * @access private
 		 * @return void
 		 */
 		private function includes() {
 			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
 			add_action( 'plugins_loaded', array( $this, 'init' ) );
 		}
		
 		/**
 		 * Verify that the plugin's dependencies are active
 		 *
 		 * @since 1.0.0
 		 * @access public
 		 * @return void
 		 */
		public function plugins_loaded() {
			if( ! $this->is_gfup_supported() ) {
				$message = __( 'GF User Populate Requires Gravity Forms and WP User Avatar', 'gfup' );
				add_action( 'admin_notices', array( $this, 'deactivate_admin_notice' ) );
				add_action( 'admin_init', array( $this, 'plugin_deactivate' ) );
				return;
			}
		}
		
 		/**
 		 * Verify the plugin is supported
 		 *
 		 * @since 1.0.0
 		 * @static
 		 * @access public
 		 * @return boolean 
 		 */
		private static function is_gfup_supported() {
			if( class_exists( 'WP_User_Avatar' ) && class_exists( 'GFCommon' ) ) {
				return true;
			}
			return false;
		}
		
 		/**
 		 * Deactivate plugin
 		 *
 		 * @since 1.0.0
 		 * @return void
 		 */
		function plugin_deactivate() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
		
		/**
		 *  Output admin notices
		 *
		 * @since 1.0.0
 		 * @access public
		 * @return void
		 */
		public function deactivate_admin_notice( $message = '', $class = 'error' ) {
			if( empty( $message ) ) {
				$message = __( 'GF User Populate has been deactived. It requires Gravity Forms and WP User Avatar plugins', 'gfup' );
			}
			echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
			if ( isset( $_GET['activate'] ) )
				unset( $_GET['activate'] );
		}
		
 		/**
 		 * Initialize the plugin hooks
 		 *
 		 * @since 1.0.0
 		 * @return void
 		 */
 		function init() {
 			// Gravity form custom dropdown and routing
 			add_filter( 'gform_pre_render_1', array( $this, 'populate_user_email_list' ) );
			
			// Add Avatar form field
			add_filter( 'get_avatar', array( $this, 'get_avatar' ), 10, 5 );
			
			// Set post author and/or gallery images
			add_filter( 'gform_after_submission_1', array( $this, 'set_post_fields' ), 10, 2 );
 		}
		
 		/**
 		 * Filter the avatar.
		 *
		 * Does the same checks as get_avatar (see wp-includes/pluggable.php), but replaces
		 * said avatar with the user meta field, if present
 		 *
 		 * @since 1.0.0
 		 * @access public
 		 * @return void
 		 */
		public function get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
				
			$user = false;
			// get the user
			if ( is_numeric( $id_or_email ) ) {
				$id = (int) $id_or_email;
				$user = get_user_by( 'id' , $id );
			} elseif ( is_object( $id_or_email ) ) {
				if ( ! empty( $id_or_email->user_id ) ) {
					$id = (int) $id_or_email->user_id;
					$user = get_user_by( 'id' , $id );
				}
			} else {
				$user = get_user_by( 'email', $id_or_email );	
			}
			
			// ------- Taken from wp-includes/pluggable.php:get_avatar() ------ //
			if ( empty($default) ) {
				$avatar_default = get_option('avatar_default');
				if ( empty($avatar_default) )
					$default = 'mystery';
				else
					$default = $avatar_default;
			}
			
			if ( false === $alt)
				$safe_alt = '';
			else
				$safe_alt = esc_attr( $alt );

			if ( !empty($email) )
				$email_hash = md5( strtolower( trim( $email ) ) );

			if ( is_ssl() ) {
				$host = 'https://secure.gravatar.com';
			} else {
				if ( !empty($email) )
					$host = sprintf( "http://%d.gravatar.com", ( hexdec( $email_hash[0] ) % 2 ) );
				else
					$host = 'http://0.gravatar.com';
			}

			if ( 'mystery' == $default )
				$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
			elseif ( 'blank' == $default )
				$default = $email ? 'blank' : includes_url( 'images/blank.gif' );
			elseif ( !empty($email) && 'gravatar_default' == $default )
				$default = '';
			elseif ( 'gravatar_default' == $default )
				$default = "$host/avatar/?s={$size}";
			elseif ( empty($email) )
				$default = "$host/avatar/?d=$default&amp;s={$size}";
			elseif ( strpos($default, 'http://') === 0 )
				$default = add_query_arg( 's', $size, $default );

			// if we have a user, set the avatar based on WP_User_Avatar
			if( $user && is_object( $user ) ) {
				global $blog_id, $wpdb;
				$avatar = esc_url( get_user_meta( $user->data->ID, $wpdb->get_blog_prefix( $blog_id ) . 'user_avatar', true ) );
			}
			
			// make sure there is an avatar user meta first
			if ( $avatar ) {
				$avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
			} elseif ( !empty($email) ) {
				$out = "$host/avatar/";
				$out .= $email_hash;
				$out .= '?s='.$size;
				$out .= '&amp;d=' . urlencode( $default );

				$rating = get_option('avatar_rating');
				if ( !empty( $rating ) )
					$out .= "&amp;r={$rating}";

				$out = str_replace( '&#038;', '&amp;', esc_url( $out ) );
				$avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
			} else {
				$out = esc_url( $default );
				$avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
			}
			
			return $avatar;
		}
		
 		/**
 		 * Gravity Forms User Populate.
		 *
		 * Populates the field with a list of users.
 		 *
 		 * @since 1.0.0
 		 * @return $form
 		 */
		function populate_user_email_list( $form ) {

			// Add filter to fields, populate the list
			foreach( $form['fields'] as &$field ) {

				// If the field is not a dropdown and not the specific class, move onto the next one
				// This acts as a quick means to filter arguments until we find the one we want
				if( $field['type'] !== 'select' || strpos($field['cssClass'], 'user-emails') === false )
					continue;

				// The first, "select" option
				$choices = array( array( 'text' => 'Select a User', 'value' => ' ' ) );

				// Collect user information
				// prepare arguments
				$args  = array(
					// order results by user_nicename
					'orderby' => 'user_nicename',
					// Return the fields we desire
					'fields'  => array( 'id', 'display_name', 'user_email' ),
				);
				// Create the WP_User_Query object
				$wp_user_query = new WP_User_Query( $args );
				// Get the results
				$users = $wp_user_query->get_results();
				//print_r( $users );

				// Check for results
				if ( !empty( $users ) ) {
					foreach ( $users as $user ){
						// Make sure the user has an email address, safeguard against users can be imported without email addresses
						// Also, make sure the user is at least able to edit posts (i.e., not a subscriber). Look at: http://codex.wordpress.org/Roles_and_Capabilities for more ideas
						if( !empty( $user->user_email ) && user_can( $user->id, 'edit_posts') ) {
							// add users to select options
							$choices[] = array(
								'text'  => $user->display_name,
								'value' => $user->id,
							);
						}
					}
				}
				$field['choices'] = $choices;
			}

			return $form;
		}

 		/**
 		 * Attach images to post gallery meta & author.
 		 *
 		 * @since 1.0.0
 		 * @return void
 		 */
		function set_post_fields( $entry, $form ) {
			
			// get post
			if( isset( $entry['post_id'] ) ) {
			    $post = get_post( $entry['post_id'] );
			} else {
				gfup_log_me( 'Post id not set' );
				return;
			}
			
			// Bail if the post don't work
			if( is_null( $post ) ) {
				gfup_log_me( 'Invalid post' );
				return;
			}
			
			// Set Post Author, if existing author is chosen
			if( isset( $entry[ $this->author_conditional_gf_field_id ] ) && $entry[$this->author_conditional_gf_field_id] == "Yes" && isset( $entry[ $this->author_gf_field_id ] ) && !empty( $entry[ $this->author_gf_field_id ] ) ) {
				// set post author to author field
				// verify that the id is a valid author
				if( get_user_by( 'id', $entry[ $this->author_gf_field_id ] ) )
					$post->post_author = $entry[ $this->author_gf_field_id ];
			}
			
			// Clean up images upload and create array for gallery field
			if( isset( $entry[$this->images_gf_field_id] ) ) {
				$images = stripslashes( $entry[$this->images_gf_field_id] );
				$images = json_decode( $images, true );
				$gallery = array();
				foreach( $images as $key => $value ) {
					$gallery[] = $this->get_image_id( $value );
				}
			} else {
				gfup_log_me( 'The gallery field is empty' );
			}
			
			// Update gallery field with array
			if( ! empty( $gallery ) ) {
				update_field( $this->acf_field_id, $gallery, $post->ID );
			} else {
				gfup_log_me( 'Something went wrong with the gallery upload' );
			}
			
		    // Updating post
		    wp_update_post( $post );
		}
		
		/**
		 * Create the image and return the new media upload id.
		 *
		 * @since 1.0.0
		 * @see http://codex.wordpress.org/Function_Reference/wp_insert_attachment#Example
		 */
		function get_image_id( $image_url, $parent_post_id = 0 ) {

			// Check the type of file. We'll use this as the 'post_mime_type'.
			$filetype = wp_check_filetype( basename( $image_url ), null );
			
			// get the file path
			$path = parse_url( $image_url, PHP_URL_PATH );
			
			// Get the path to the upload directory.
			$wp_upload_dir = wp_upload_dir();

			// Prepare an array of post data for the attachment.
			$attachment = array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $image_url ), 
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $image_url ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			// Insert the attachment.
			$attach_id = wp_insert_attachment( $attachment, $image_url );

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			
			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attach_id, $path );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			
			return $attach_id; 
		}
	} // End of class
	
	// Generate class
	global $_gf_user_populate;
	$_gf_user_populate = new GF_User_Populate;
} // End if class_exists check

/**
 * Log any errors for debugging.
 *
 * @since 1.0.0
 */
if( !function_exists( 'gfup_log_me' ) ) {
	function gfup_log_me( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( 'GFUP Error: ' . print_r( $message, true ) );
			} else {
				error_log( 'GFUP Error: ' . $message );
			}
		}
	}
}