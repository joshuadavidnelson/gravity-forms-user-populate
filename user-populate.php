<?php
/**
 * Plugin Name: Gravity Forms User Populate Add On
 * Plugin URI: https://github.com/joshuadavidnelson/gravity-forms-user-populate
 * Description: Populate the drop-down menu with users
 * Version: 1.5.0
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
		
		private $user_id = false;
		
		// The default options
		private $options = array(
			'gf_form_id' => 1, // the gravity form
			'acf_field_id' => 'field_546d0ad42e7f0', // The ACF Gallery field id
			'gf_images_field_id' => 27, // The Gravity Forms gallery field id
			'gf_author_field_id' => 23, // The Gravity Forms author id
			'gf_author_conditional_field_id' => 19, // The Gravity Forms conditional author field id ("yes" if existing author)
			'gf_author_avatar_field_id' => 22,
			'acf_avatar_field_id' => 'field_55b4095067ec4',
		);

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
 				define( 'GFUP_VERSION', '1.5.0' );
 			}

 			// Plugin Folder Path
 			if ( ! defined( 'GFUP_DIR' ) ) {
 				define( 'GFUP_DIR', plugin_dir_path( __FILE__ ) );
 			}

 			// Plugin Folder URL
 			if ( ! defined( 'GFUP_URL' ) ) {
 				define( 'GFUP_URL', plugin_dir_url( __FILE__ ) );
 			}

 			// Plugin Text Domain - for internationalization
 			if ( ! defined( 'GFUP_DOMAIN' ) ) {
 				define( 'GFUP_DOMAIN', 'gfup' );
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
			
			// include metabox and settings page
			if ( file_exists( GFUP_DIR . '/includes/metabox/init.php' ) ) {
				require_once( GFUP_DIR . '/includes/metabox/init.php' );
				require_once( GFUP_DIR . '/includes/settings.php');
			}
			
			// get settings
			if( function_exists( 'gfup_get_option' ) ) {
				$gfup_options = array();
				$gfup_options['gf_form_id'] = gfup_get_option( 'gf_form_id' );
				$gfup_options['acf_field_id'] = gfup_get_option( 'acf_field_id' );
				$gfup_options['gf_images_field_id'] = gfup_get_option( 'gf_images_field_id' );
				$gfup_options['gf_author_field_id'] = gfup_get_option( 'gf_author_field_id' );
				$gfup_options['gf_author_conditional_field_id'] = gfup_get_option( 'gf_author_conditional_field_id' );
				$gfup_options['gf_author_avatar_field_id'] = gfup_get_option( 'gf_author_avatar_field_id' );
				$gfup_options['acf_avatar_field_id'] = gfup_get_option( 'acf_avatar_field_id' );
				
				// run through settings and update values as necessary
				foreach( $this->options as $option => $value ) {
					foreach( $gfup_options as $new_option => $new_value ) {
						if( $option == $new_option ) {
							if( !empty( $new_value ) ) {
								if( $new_option == 'acf_field_id' || is_numeric( $new_value ) ) {
									$this->options[$option] = $new_value;
								} // end if
							} // end if
						} // end if
					} // end foreach
				} // end foreach
			} // end if
			
 		} // end function
		
 		/**
 		 * Verify that the plugin's dependencies are active
 		 *
 		 * @since 1.0.0
 		 * @access public
 		 * @return void
 		 */
		public function plugins_loaded() {
			if( ! $this->is_gfup_supported() ) {
				$message = __( 'GF User Populate Requires Gravity Forms and GF User Registration to be active', 'gfup' );
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
			if( class_exists( 'GFCommon' ) && class_exists( 'GFUser' ) ) {
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
				$message = __( 'GF User Populate has been deactived. It requires Gravity Forms and GF User Registration plugins. Verify they are all installed and active, then attempt reactivation of GF User Populate.', 'gfup' );
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
 			add_filter( "gform_pre_render_{$this->options['gf_form_id']}", array( $this, 'populate_user_email_list' ) );
			
			// Set user id for use after submission
			add_action( 'gform_user_registered', array( $this, 'add_custom_user_meta' ), 10, 4 );
			
			// Set post author and/or gallery images
			add_filter( "gform_after_submission_{$this->options['gf_form_id']}", array( $this, 'set_post_fields' ), 10, 2 );
 		}
		
 		/**
 		 * Sets the new user's user id
 		 *
 		 * @since 1.4.0
 		 * @return void
 		 */
		function add_custom_user_meta( $user_id, $config, $entry, $user_pass ) {
    		if( isset( $entry[ $this->options['gf_author_conditional_field_id'] ] ) && $entry[ $this->options['gf_author_conditional_field_id'] ] != "Yes" && isset( $entry[ $this->options['gf_author_avatar_field_id'] ] ) && !empty( $entry[ $this->options['gf_author_avatar_field_id'] ] ) ) {
				$this->user_id = $user_id;
			}
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
				if( $field['type'] !== 'select' || $field['id'] != $this->options['gf_author_field_id'] )
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
				gfup_log_me( 'GF post id not set' );
				return;
			}
			
			// Bail if the post don't work
			if( is_null( $post ) ) {
				gfup_log_me( 'Invalid post' );
				return;
			}
			
			// Set Post Author, if existing author is chosen
			if( isset( $entry[ $this->options['gf_author_conditional_field_id'] ] ) && $entry[ $this->options['gf_author_conditional_field_id'] ] == "Yes" && isset( $entry[ $this->options['gf_author_field_id'] ] ) && !empty( $entry[ $this->options['gf_author_field_id'] ] ) ) {
				
				// set post author to author field
				// verify that the id is a valid author
				if( get_user_by( 'id', $entry[ $this->options['gf_author_field_id'] ] ) )
					$post->post_author = $entry[ $this->options['gf_author_field_id'] ];
				
			// If it's an existing author, make sure the avatar image is added to the media library
			} elseif( isset( $entry[ $this->options['gf_author_conditional_field_id'] ] ) && $entry[ $this->options['gf_author_conditional_field_id'] ] != "Yes" && isset( $entry[ $this->options['gf_author_avatar_field_id'] ] ) && !empty( $entry[ $this->options['gf_author_avatar_field_id'] ] ) ) {
				
				// add new post author image to media library and set simple local avatar
				$author_image = $this->get_image_id( $entry[ $this->options['gf_author_avatar_field_id'] ], null );
				if( $author_image && $this->user_id ) {
					update_field( $this->options['acf_avatar_field_id'], $author_image, 'user_'. $this->user_id );
				} else {
					gfup_log_me( 'No avatar set' );
				}
				
			} else {
				gfup_log_me( 'Author field error' );
			}
			
			// Clean up images upload and create array for gallery field
			if( isset( $entry[ $this->options['gf_images_field_id'] ] ) ) {
				$images = stripslashes( $entry[ $this->options['gf_images_field_id'] ] );
				$images = json_decode( $images, true );
				if( !empty( $images ) && is_array( $images ) ) {
					$gallery = array();
					foreach( $images as $key => $value ) {
						$image_id = $this->get_image_id( $value, $post->ID );
						if( $image_id ) {
							$gallery[] = $image_id;
						}
					}
				}
			} else {
				gfup_log_me( 'Images field error' );
			}
			
			// Update gallery field with array
			if( ! empty( $gallery ) ) {
				update_field( $this->options['acf_field_id'], $gallery, $post->ID );
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
		function get_image_id( $image_url, $parent_post_id = null ) {
			
			if( !isset( $image_url ) )
				return false;
			
			// Cache info on the wp uploads dir
			$wp_upload_dir = wp_upload_dir();

			// get the file path
			$path = parse_url( $image_url, PHP_URL_PATH );
			
			// File base name
			$file_base_name = basename( $image_url );
			
			// Full path
			if( defined('GFUP_SUB_DIRECTORY') && GFUP_SUB_DIRECTORY === true ) {
				$home_path = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
			} else {
				$home_path = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
			}
			$home_path = untrailingslashit( $home_path );
			$uploaded_file_path = $home_path . $path;

			// Check the type of file. We'll use this as the 'post_mime_type'.
			$filetype = wp_check_filetype( $file_base_name, null );
			
			// error check
			if( !empty( $filetype ) && is_array( $filetype ) ) {
				
				// Create attachment title
				$post_title = preg_replace( '/\.[^.]+$/', '', $file_base_name );
				
				// Prepare an array of post data for the attachment.
				$attachment = array(
					'guid'           => $wp_upload_dir['url'] . '/' . basename( $uploaded_file_path ), 
					'post_mime_type' => $filetype['type'],
					'post_title'     => esc_attr( $post_title ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				
				// Set the post parent id if there is one
				if( !is_null( $parent_post_id ) )
					$attachment['post_parent'] = $parent_post_id;

				// Insert the attachment.
				$attach_id = wp_insert_attachment( $attachment, $uploaded_file_path );

				//Error check
				if( !is_wp_error( $attach_id ) ) {
					//Generate wp attachment meta data
					if( file_exists( ABSPATH . 'wp-admin/includes/image.php') && file_exists( ABSPATH . 'wp-admin/includes/media.php') ) {
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						require_once( ABSPATH . 'wp-admin/includes/media.php' );

						$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file_path );
						wp_update_attachment_metadata( $attach_id, $attach_data );
					} // end if file exists check
				} else {
					gfup_log_me( 'Attachment id error' );
				} // end if error check
		
				return $attach_id; 
			
			} else {
				gfup_log_me( 'Filetype error' );
				return false;
			} // end if $$filetype
		} // end function get_image_id
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
