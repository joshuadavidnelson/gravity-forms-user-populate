<?php
 /**
 * Plugin Name: Gravity Forms User Populate Add On
 * Plugin URI: http://joshuadnelson.com/user-dropdown-list-custom-notification-routing-gravity-forms/
 * Description: Populate the drop-down menu with users
 * Version: 1.0.0
 * Author: Joshua David Nelson
 * Author URI: josh@joshuadnelson.com
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
	final class GF_User_Populate {
 		/** Singleton */

 		/**
 		 * @var GF_User_Populate The one true GF_User_Populate
 		 * @since 1.0.0
 		 */
 		private static $instance;

 		/**
 		 * Main GF_User_Populate Instance
 		 *
 		 * Insures that only one instance of GF_User_Populate exists in memory at any one
 		 * time. Also prevents needing to define globals all over the place.
 		 *
 		 * @since 1.0.0
 		 * @static
 		 * @staticvar array $instance
 		 * @uses GF_User_Populate::setup_constants() Setup the constants needed
 		 * @uses GF_User_Populate::includes() Include the required files
 		 * @uses GF_User_Populate::load_textdomain() load the language files
 		 * @see DCG()
 		 * @return The one true GF_User_Populate
 		 */
 		public static function instance() {
 			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GF_User_Populate ) ) {
 				self::$instance = new GF_User_Populate;
 				self::$instance->setup_constants();
 				self::$instance->includes();
 			}
 			return self::$instance;
 		}
		
 		/**
 		 * Build the class
 		 */
 		public function __construct() {
 			// Activation Hook
 			register_activation_hook( __FILE__, array( $this, 'activation_hook' ) );
 		}

 		/**
 		 * Throw error on object clone
 		 *
 		 * The whole idea of the singleton design pattern is that there is a single
 		 * object therefore, we don't want the object to be cloned.
 		 *
 		 * @since 1.0.0
 		 * @access protected
 		 * @return void
 		 */
 		public function __clone() {
 			// Cloning instances of the class is forbidden
 			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'eec' ), '1.0.0' );
 		}

 		/**
 		 * Disable unserializing of the class
 		 *
 		 * @since 1.0.0
 		 * @access protected
 		 * @return void
 		 */
 		public function __wakeup() {
 			// Unserializing instances of the class is forbidden
 			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'eec' ), '1.0.0' );
 		}
		
 		/**
 		 * Setup plugin constants
 		 *
 		 * @access private
 		 * @since 1.0.0
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

 			// Plugin Root File
 			if ( ! defined( 'GFUP_FILE' ) ) {
 				define( 'GFUP_FILE', __FILE__ );
 			}
			
 			// Plugin Text Domain
 			if ( ! defined( 'GFUP_DOMAIN' ) ) {
 				define( 'GFUP_DOMAIN', 'gfup' );
 			}
 		}
		
		/**
		 * Activation Hook - Confirm site is using required plugin(s)
		 */
		function activation_hook() {
			// Gravity Forms
			if( !class_exists( 'GFCommon' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				$message = $this->admin_message( 'GF User Populate requires Gravity Forms', 'error' );
				$message_function = create_function( '', 'echo ' . $message . ';');
	            add_action( 'admin_notices', $message_function );
	            add_action( 'network_admin_notices', $message_function );
				
			// WP User Avatar
			} elseif( !class_exists( 'WP_User_Avatar' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				$message = $this->admin_message( 'GF User Populate requires WP User Avatar', 'error' );
				$message_function = create_function( '', 'echo ' . $message . ';');
	            add_action( 'admin_notices', $message_function );
	            add_action( 'network_admin_notices', $message_function );
			}
		}
		
		/**
		 * Return plugin error admin message
		 */
	    public static function admin_message( $message = '', $class = '' ) {
			if( !empty( $class ) && !empty( $message ) ){
		        return '<div id="message" class="' . $class . ' gfup-message"><p>' . $message . '</p></div>';
			}
	    }

 		/**
 		 * Include required files and starts the plugin
 		 *
 		 * @access private
 		 * @since 1.0.0
 		 * @return void
 		 */
 		private function includes() {			
 			add_action( 'plugins_loaded', array( $this, 'init' ) );
 		}
		
 		// Initialize the plugin hooks
 		function init() {
 			// Gravity form custom dropdown and routing
 			add_filter( 'gform_pre_render_1', array( $this, 'populate_user_email_list' ) );
 			//add_filter( 'gform_notification_1', array( $this, 'route_gf_notification' ), 10, 3 ); 
 			//add_filter( 'gform_notification_1', array( $this, 'add_attachments_to_gf' ), 10, 3 );
			
			// Add Avatar form field
			add_filter( 'get_avatar', array( $this, 'get_avatar' ), 10, 5 );
 		}
		
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
		
 		// Gravity Forms User Populate
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

 		// Route to user address
 		function route_gf_notification( $notification, $form , $entry ) {
    
 		    foreach( $form['fields'] as &$field ) {
        
 		        if( $field['type'] != 'select' || strpos( $field['cssClass'], 'gfup-employees' ) === false )
 		            continue;
        
 		        $field_id = (string) $field['id'];
 		        $user_id = $entry[ $field_id ];
        
 		    }
    
 		    $email_to = get_the_author_meta( 'user_email', $user_id);
    
 		    if( $email_to ) {
 		        $notification['to'] = $email_to;
 		    }
 		    return $notification;
 		}

 		// Add attachments to gravity form
 		function add_attachments_to_gf( $notification, $form, $entry ) {
 		    $fileupload_fields = GFCommon::get_fields_by_type( $form, array( "fileupload" ) );
 		    if( !is_array( $fileupload_fields ) )
 		        return $notification;
 		    $attachments = array();
 		    $upload_root = RGFormsModel::get_upload_root();
 		    foreach( $fileupload_fields as $field ){
 		        $url = $entry[ $field["id"] ];
 		        $attachment = preg_replace('|^(.*?)/gravity_forms/|', $upload_root, $url);
 		        if( $attachment ){
 		            $attachments[] = $attachment;
 		        }
 		    }
 		    $notification["attachments"] = $attachments;
 		    return $notification;
 		}
 	}
 } // End if class_exists check

 /**
  * The main function responsible for returning the one true DCG_Functionality
  * Instance to functions everywhere.
  *
  * Use this function like you would a global variable, except without needing
  * to declare the global.
  *
  * Example: <?php $gfup = GFUP(); ?>
  *
  * @since 1.0.0
  * @return object The one true DCG_Functionality Instance
  */
 function GFUP() {
 	return GF_User_Populate::instance();
 }

 // Get DCG Running
 GFUP();

 /**
  * Log any errors for debugging.
  *
  * @since 1.0.0
  */	
 function jdn_log_me( $message ) {
     if ( WP_DEBUG === true ) {
         if ( is_array( $message ) || is_object( $message ) ) {
             error_log( 'GFUP Error: ' . print_r( $message, true ) );
         } else {
             error_log( 'GFUP Error: ' . $message );
         }
     }
 }
