<?php
/**
 * Admin class
 *
 * @package      GF_User_Populate
 * @subpackage	 Settings
 * @author       Joshua David Nelson <josh@joshuadnelson.com>
 * @copyright    Copyright (c) 2015, Joshua David Nelson
 * @license      http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @since        1.2.0
 *
 * Built from: https://github.com/WebDevStudios/Custom-Metaboxes-and-Fields-for-WordPress/wiki/Using-CMB-to-create-an-Admin-Theme-Options-Page
 *
 **/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The GF_User_Populate Admin Settings Class.
 *
 * @since 1.2.0
 **/
class GFUP_Settings {

    /**
     * Option key, and option page slug
   	 *
     * @since 1.2.0
     * @var string
     **/
    protected static $key = 'gfup';
  	
    /**
     * Array of metaboxes/fields
  	 *
     * @since 1.2.0
     * @var array
     **/
    protected static $theme_options = array();
	
    /**
     * Options Page title
  	 *
     * @since 1.2.0
     * @var string
     **/
    protected $title = '';

    /**
     * Constructor
  	 *
     * @since 1.2.0
     **/
    public function __construct() {
        // Set our title
        $this->title = __( 'GF User Populate Options', GFUP_DOMAIN );
    }

    /**
     * Initiate our hooks
  	 *
     * @since 1.2.0
     **/
    public function hooks() {
        add_action( 'admin_init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'add_page' ) );
    }

    /**
     * Register our setting to WP
	 *
     * @since 1.2.0
     **/
    public function init() {
        register_setting( self::$key, self::$key );
    }

    /**
     * Add menu options page
	 *
     * @since 1.2.0
     **/
    public function add_page() {
		$this->options_page = add_options_page( $this->title, $this->title, 'manage_options', self::$key, array( $this, 'admin_page_display' ) );
    }

    /**
     * Admin page markup. Mostly handled by CMB
	 *
     * @since 1.2.0
     **/
    public function admin_page_display() {
        ?>
        <div id="poststuff" class="wrap gcs_options_page <?php echo self::$key; ?>">
	        <div class="wrap cmb_options_page <?php echo self::$key; ?>">
	            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	            <?php cmb_metabox_form( self::option_fields(), self::$key ); ?>
	        </div>
		</div>
        <?php
    }

    /**
     * Defines the plugin option metabox and field configuration
	 *
	 * For more information, view: 
	 *
     * @since 1.2.0
     * @return array
     **/
    public static function option_fields() {

		// Only need to initiate the array once per page-load
		if( !empty( self::$theme_options ) )
			return self::$theme_options;

		// Define the Theme Options
		self::$theme_options = array(
			'id'           => 'gfup_settings',
			'show_on'      => array( 'key' => 'options-page', 'value' => array( self::$key, ), ),
			'show_names'   => true,
			'fields'       => array(
				array(
				    'name'    => 'Gravity Form ID',
					'description' => 'The Gravity Form ID',
				    'id'      => 'gf_form_id',
				    'type'    => 'text_small',
				),
				array(
				    'name'    => 'ACF Gallery Field ID',
					'description' => 'The field id (like "field_546d0ad42e7f0") for the ACF Gallery Field',
				    'id'      => 'acf_field_id',
				    'type'    => 'text_medium'
				),
				array(
				    'name'    => 'ACF Avatar Field ID',
					'description' => 'The field id (like "field_546d0ad42e7f0") for the ACF Avatar Field',
				    'id'      => 'acf_avatar_field_id',
				    'type'    => 'text_medium'
				),
				array(
				    'name'    => 'GF Images Field ID',
					'description' => 'The Gravity Form field id (numeber) for the gallery',
				    'id'      => 'gf_images_field_id',
				    'type'    => 'text_small'
				),
				array(
				    'name'    => 'GF Author Conditional Field ID',
					'description' => 'This field should be a condition with "yes" or "no" options, where "yes" means it is an existing author',
				    'id'      => 'gf_author_conditional_field_id',
				    'type'    => 'text_small'
				),
				array(
				    'name'    => 'GF Existing Author Field ID',
					'description' => 'The Gravity Form field id for the existing author drop down',
				    'id'      => 'gf_author_field_id',
				    'type'    => 'text_small'
				),
				array(
				    'name'    => 'GF New Author Avatar Field ID',
					'description' => 'This field is for the new author avatar image upload',
				    'id'      => 'gf_author_avatar_field_id',
				    'type'    => 'text_small'
				),
			),
	    );

	    return self::$theme_options;
    }
  
    /**
     * Make public the protected $key variable.
     *
     * @since 1.2.0
     * @return string  Option key
     **/
    public static function key() {
        return self::$key;
    }
}

// Get it started
$gfup_settings = new GFUP_Settings();
$gfup_settings->hooks();

/**
 * Wrapper function around cmb_get_option  
 *
 * @since 1.2.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 **/
function gfup_get_option( $key = '' ) {
    return cmb_Meta_Box::get_option( GFUP_Settings::key(), $key );
}