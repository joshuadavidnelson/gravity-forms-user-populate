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
 			add_filter( 'gform_notification_1', array( $this, 'add_attachments_to_gf' ), 10, 3 );
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