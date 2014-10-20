<?php
/*
Plugin Name: CiviCRM
Description: CiviCRM - Growing and Sustaining Relationships
Version: 4.5.2
Author: CiviCRM LLC
Author URI: http://civicrm.org/
Plugin URI: http://wiki.civicrm.org/confluence/display/CRMDOC/WordPress+Installation+Guide+for+CiviCRM+4.5
License: AGPL3
Text Domain: civicrm
Domain Path: /languages
*/


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */


/*
--------------------------------------------------------------------------------
WordPress resources for developers
--------------------------------------------------------------------------------
Not that they're ever adhered to anywhere other than core, but people do their
best to comply...

WordPress core coding standards:
http://make.wordpress.org/core/handbook/coding-standards/php/

WordPress HTML standards:
http://make.wordpress.org/core/handbook/coding-standards/html/

WordPress JavaScript standards:
http://make.wordpress.org/core/handbook/coding-standards/javascript/
--------------------------------------------------------------------------------
*/


// this file must not accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// set version here: when it changes, will force JS to reload
define( 'CIVICRM_PLUGIN_VERSION', '4.5.2' );

// define commonly used items as constants
define( 'CIVICRM_PLUGIN_DIR', plugin_dir_path(__FILE__) );
if (!defined('CIVICRM_SETTINGS_PATH')) {
  define( 'CIVICRM_SETTINGS_PATH', CIVICRM_PLUGIN_DIR . 'civicrm.settings.php' );
}

// prevent CiviCRM from rendering its own header
define( 'CIVICRM_UF_HEAD', TRUE );


/**
 * Define CiviCRM_For_WordPress Class
 */
class CiviCRM_For_WordPress {


  /**
   * Declare our properties
   */

  // plugin instance
  private static $instance;

  // plugin context (broad)
  static $in_wordpress;

  // plugin context (specific)
  static $context;

  // init property to store shortcodes
  public $shortcodes = array();
    
  // init property to store shortcode markup
  public $shortcode_markup = array();
    

  // ---------------------------------------------------------------------------
  // Setup
  // ---------------------------------------------------------------------------


  /**
   * Getter method which returns the CiviCRM instance and optionally creates one 
   * if it does not already exist. Standard CiviCRM singleton pattern.
   *
   * @return object CiviCRM plugin instance
   */
  public static function singleton() {

    // if it doesn't already exist...
    if ( ! isset( self::$instance ) ) {

      // create it
      self::$instance = new CiviCRM_For_WordPress;
      self::$instance->setup_instance();

    }

    // return existing instance
    return self::$instance;

  }


  /**
   * Dummy instance constructor
   */
  function __construct() {}

  /**
   * Dummy magic method to prevent CiviCRM_For_WordPress from being cloned
   */
  public function __clone() {
    _doing_it_wrong( __FUNCTION__, __( 'Only one instance of CiviCRM_For_WordPress please', 'civicrm' ), '4.4' );
  }

  /**
   * Dummy magic method to prevent CiviCRM_For_WordPress from being unserialized
   */
  public function __wakeup() {
    _doing_it_wrong( __FUNCTION__, __( 'Please do not serialize CiviCRM_For_WordPress', 'civicrm' ), '4.4' );
  }


  /**
   * Method that runs only when CiviCRM plugin is activated
   *
   * @return void
   */
  public function activate() {

    // Assign minimum capabilities for all WordPress roles and create 'anonymous_user' role
    $this->set_wp_user_capabilities();

  }


  /**
   * Set up the CiviCRM plugin instance
   *
   * @return void
   */
  public function setup_instance() {

    // kick out if another instance is being inited
    if ( isset( $this->in_wordpress ) ) {
      wp_die( __( 'Only one instance of CiviCRM_For_WordPress please', 'civicrm' ) );
    }

    // Store context
    $this->civicrm_in_wordpress_set();
    
    // there is no session handling in WP hence we start it for CiviCRM pages
    if (!session_id()) {
      session_start();
    }

    if ( $this->civicrm_in_wordpress() ) {
      // this is required for AJAX calls in WordPress admin
      $_GET['noheader'] = TRUE;
    } else {
      $_GET['civicrm_install_type'] = 'wordpress';
    }

    $this->register_hooks();

    // notify plugins
    do_action( 'civicrm_instance_loaded' );

  }


  /**
   * Setter for determining if CiviCRM is currently being displayed in WordPress.
   * This becomes true whe CiviCRM is called in the following contexts:
   *
   * (a) in the WordPress back-end
   * (b) when CiviCRM content is being displayed on the front-end via wpBasePage
   * (c) when an AJAX request is made to CiviCRM
   *
   * It is NOT true when CiviCRM is called via a shortcode
   *
   * @return void
   */
  public function civicrm_in_wordpress_set() {

    // store
    self::$in_wordpress = ( isset( $_GET['page'] ) && $_GET['page'] == 'CiviCRM' ) ? TRUE : FALSE;

  }


  /**
   * Getter for testing if CiviCRM is currently being displayed in WordPress.
   *
   * @see $this->civicrm_in_wordpress_set()
   *
   * @return bool $in_wordpress True if Civi is displayed in WordPress, false otherwise
   */
  public function civicrm_in_wordpress() {

    // already stored
    return apply_filters( 'civicrm_in_wordpress', self::$in_wordpress );

  }


  /**
   * Setter for determining how CiviCRM is currently being displayed in WordPress.
   * This can be one of the following contexts:
   *
   * (a) in the WordPress back-end
   * (b) when CiviCRM content is being displayed on the front-end via wpBasePage
   * (c) when a "non-page" request is made to CiviCRM
   * (d) when CiviCRM is called via a shortcode
   *
   * The following codes correspond to the different contexts
   *
   * (a) 'admin'
   * (b) 'basepage'
   * (c) 'nonpage'
   * (d) 'shortcode'
   *
   * @param $context One of the four context codes above
   * @return void
   */
  public function civicrm_context_set( $context ) {

    // store
    self::$context = $context;

  }


  /**
   * Getter for determining how CiviCRM is currently being displayed in WordPress.
   *
   * @see $this->civicrm_context_set()
   *
   * @return str $context The context in which Civi is displayed in WordPress
   */
  public function civicrm_context_get() {

    // already stored
    return apply_filters( 'civicrm_context', self::$context );

  }


  // ---------------------------------------------------------------------------
  // Hooks
  // ---------------------------------------------------------------------------


  /**
   * Register hooks
   *
   * @return void
   */
  public function register_hooks() {

    // always add the common hooks
    $this->register_common_hooks();
    
    // when in WordPress admin...
    if ( is_admin() ) {

      // set context
      $this->civicrm_context_set( 'admin' );
      
      // handle WP admin context
      $this->register_admin_hooks();
      return;
      
    }

    // when embedded via wpBasePage or AJAX call...
    if ( $this->civicrm_in_wordpress() ) {
      
      // add core resources for front end
      $this->add_core_resources( TRUE );
      
      /**
       * Directly output CiviCRM html only in a few cases and skip WP templating:
       *
       * (a) when a snippet is set
       * (b) when there is an AJAX call
       * (c) for an iCal feed (unless 'html' is specified)
       * (d) for file download URLs
       */
      if ( ! $this->is_page_request() ) {
        
        // set context
        $this->civicrm_context_set( 'nonpage' );
      
        // echo all output and exit
        $this->invoke();
        die();
        
      }
      
      // set context
      $this->civicrm_context_set( 'basepage' );
      
      // if we get here, we must be in a wpBasePage context
      $this->register_basepage_hooks();
      return;
    
    }
      
    // set context
    $this->civicrm_context_set( 'shortcode' );
      
    // that leaves us with handling shortcodes, should they exist
    $this->register_shortcode_hooks();

  }


  /**
   * Register hooks that must always be present
   *
   * @return void
   */
  public function register_common_hooks() {
  
    // use translation files
    add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

    // add CiviCRM access capabilities to WordPress roles
    add_action( 'init', array( $this, 'set_access_capabilities' ) );

    // synchronise users on insert and update
    add_action( 'user_register', array( $this, 'update_user' ) );
    add_action( 'profile_update', array( $this, 'update_user' ) );

    // delete ufMatch record when a WordPress user is deleted
    add_action( 'deleted_user', array( $this, 'delete_user_ufmatch' ), 10, 1 );
    
    // register the CiviCRM shortcode
    add_shortcode( 'civicrm', array( $this, 'do_shortcode' ) );
    
  }
  
   
  /**
   * Register hooks to handle CiviCRM in a WordPress admin context
   *
   * @return void
   */
  public function register_admin_hooks() {
    
    // modify the admin menu
    add_action( 'admin_menu', array( $this, 'add_menu_items' ) );
    
    // the following three hooks CiviCRM button to post and page screens
    
    // adds the CiviCRM button to post and page edit screens
    // use priority 100 to position button to the farright
    add_action( 'media_buttons', array( $this, 'add_form_button' ), 100 );
    
    // adds the HTML triggered by the button above
    add_action( 'admin_footer', array( $this, 'add_form_button_html' ) );
    
    // add the javascript to make it all happen
    add_action( 'admin_enqueue_scripts', array( $this, 'add_form_button_js' ) );
    
    // check if settings file exist, do not show configuration link on
    // install / settings page
    if ( isset( $_GET['page'] ) && $_GET['page'] != 'civicrm-install' ) {
      if ( ! file_exists( CIVICRM_SETTINGS_PATH ) ) {
        add_action( 'admin_notices', array( $this, 'show_setup_warning' ) );
      }
    }
    
  }


  /**
   * Register hooks to handle CiviCRM in a WordPress wpBasePage context
   *
   * @return void
   */
  public function register_basepage_hooks() {
    
    // kick out if not CiviCRM
    if (!$this->initialize()) {
      return;
    }
    
    // regardless of URL, load page template
    add_filter( 'template_include', array( $this, 'basepage_template' ), 999 );
        
    // merge CiviCRM's HTML header with the WordPress theme's header
    add_action( 'wp_head', array( $this, 'wp_head' ) );
      
    // check permission
    $argdata = $this->get_request_args();
    if ( ! $this->check_permission( $argdata['args'] ) ) {
      add_filter( 'the_content', array( $this, 'get_permission_denied' ) );
      return;
    }

    // cache CiviCRM base page markup
    add_action( 'wp', array( $this, 'handle_basepage' ), 10, 1 );
    
  }


  /**
   * Register hooks to handle the presence of shortcodes in content
   *
   * @return void
   */
  public function register_shortcode_hooks() {

    // add CiviCRM core resources when a shortcode is detected in the post content
    add_action( 'wp', array( $this, 'handle_shortcodes' ), 10, 1 );
        
    // merge CiviCRM's HTML header with the WordPress theme's header
    add_action( 'wp_head', array( $this, 'wp_head' ) );
      
  }


  // ---------------------------------------------------------------------------
  // CiviCRM Initialisation
  // ---------------------------------------------------------------------------


  /**
   * Initialize CiviCRM
   *
   * @return bool $success
   */
  public function initialize() {

    static $initialized = FALSE;
    static $failure = FALSE;

    if ( $failure ) {
      return FALSE;
    }

    if ( ! $initialized ) {

      // Check for php version and ensure its greater than minPhpVersion
      $minPhpVersion = '5.3.3';
      if ( version_compare( PHP_VERSION, $minPhpVersion ) < 0 ) {
        echo '<p>' .
           sprintf(
            __( 'CiviCRM requires PHP Version %s or greater. You are running PHP Version %s', 'civicrm' ),
            $minPhpVersion,
            PHP_VERSION
           ) .
           '<p>';
        exit();
      }

      // check for settings
      if ( ! file_exists( CIVICRM_SETTINGS_PATH ) ) {
        $error = FALSE;
      } else {
        $error = include_once ( CIVICRM_SETTINGS_PATH );
      }

      // autoload
      require_once 'CRM/Core/ClassLoader.php';
      CRM_Core_ClassLoader::singleton()->register();

      // get ready for problems
      $installLink    = admin_url() . "options-general.php?page=civicrm-install";
      $docLinkInstall = "http://wiki.civicrm.org/confluence/display/CRMDOC/WordPress+Installation+Guide";
      $docLinkTrouble = "http://wiki.civicrm.org/confluence/display/CRMDOC/Installation+and+Configuration+Trouble-shooting";
      $forumLink      = "http://forum.civicrm.org/index.php/board,6.0.html";


      // construct message
      $errorMsgAdd = sprintf(
        __( 'Please review the <a href="%s">WordPress Installation Guide</a> and the <a href="%s">Trouble-shooting page</a> for assistance. If you still need help installing, you can often find solutions to your issue by searching for the error message in the <a href="%s">installation support section of the community forum</a>.', 'civicrm' ),
        $docLinkInstall,
        $docLinkTrouble,
        $forumLink
      );

      // does install message get used?
      $installMessage = sprintf(
        __( 'Click <a href="%s">here</a> for fresh install.', 'civicrm' ),
        $installLink
      );

      if ($error == FALSE) {
        header( 'Location: ' . admin_url() . 'options-general.php?page=civicrm-install' );
        return FALSE;
      }
      
      // access global defined in civicrm.settings.php
      global $civicrm_root;
      
      // this does pretty much all of the civicrm initialization
      if ( ! file_exists( $civicrm_root . 'CRM/Core/Config.php' ) ) {
        $error = FALSE;
      } else {
        $error = include_once ( 'CRM/Core/Config.php' );
      }

      // have we got it?
      if ( $error == FALSE ) {

        // set static flag
        $failure = TRUE;

        // FIX ME - why?
        wp_die(
          "<strong><p class='error'>" .
          sprintf(
            __( 'Oops! - The path for including CiviCRM code files is not set properly. Most likely there is an error in the <em>civicrm_root</em> setting in your CiviCRM settings file (%s).', 'civicrm' ),
            CIVICRM_SETTINGS_PATH
          ) .
          "</p><p class='error'> &raquo; " .
          sprintf(
            __( 'civicrm_root is currently set to: <em>%s</em>.', 'civicrm' ),
            $civicrm_root
          ) .
          "</p><p class='error'>" . $errorMsgAdd . "</p></strong>"
        );

        // won't reach here!
        return FALSE;

      }

      // set static flag
      $initialized = TRUE;

      // initialize the system by creating a config object
      $config = CRM_Core_Config::singleton();

      // sync the logged in user with WP
      global $current_user;
      if ( $current_user ) {

        // sync procedure sets session values for logged in users
        require_once 'CRM/Core/BAO/UFMatch.php';
        CRM_Core_BAO_UFMatch::synchronize(
          $current_user, // user object
          FALSE, // do not update
          'WordPress', // CMS
          $this->get_civicrm_contact_type('Individual')
        );

      }

    }

    // notify plugins
    do_action( 'civicrm_initialized' );

    // success!
    return TRUE;

  }


  // ---------------------------------------------------------------------------
  // Plugin setup
  // ---------------------------------------------------------------------------


  /**
   * Load translation files
   * A good reference on how to implement translation in WordPress:
   * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
   *
   * @return void
   */
  public function enable_translation() {

    // not used, as there are no translations as yet
    load_plugin_textdomain(

      // unique name
      'civicrm',

      // deprecated argument
      FALSE,

      // relative path to directory containing translation files
      dirname( plugin_basename( __FILE__ ) ) . '/languages/'

    );

  }


  /**
   * Adds menu items to WordPress admin menu
   * Callback method for 'admin_menu' hook as set in register_hooks()
   *
   * @return void
   */
  public function add_menu_items() {

    // check for settings file
    if ( file_exists( CIVICRM_SETTINGS_PATH ) ) {

      // use plugins_url( 'path/to/file.png', __FILE__ )
      // see http://codex.wordpress.org/Function_Reference/plugins_url
      // NB: given that URLs always use /, I see no need for DIR_SEP
      $civilogo = plugins_url(
        'civicrm/i/logo16px.png',
        __FILE__
      );

      // add top level menu item
      $menu_page = add_menu_page(
        __( 'CiviCRM', 'civicrm' ),
        __( 'CiviCRM', 'civicrm' ),
        'access_civicrm',
        'CiviCRM',
        array( $this, 'invoke' ),
        $civilogo,
        apply_filters( 'civicrm_menu_item_position', '3.904981' ) // 3.9 + random digits to reduce risk of conflict
      );
      
      // add core resources prior to page load
      add_action( 'load-' . $menu_page, array( $this, 'admin_page_load' ) );
      
      // add CiviCRM scripts and styles to admin head
      add_action( 'admin_head-' . $menu_page, array( $this, 'wp_head' ), 50 );
      
    } else {

      // add menu item to options menu
      $options_page = add_options_page(
        __( 'CiviCRM Installer', 'civicrm' ),
        __( 'CiviCRM Installer', 'civicrm' ),
        'manage_options',
        'civicrm-install',
        array( $this, 'run_installer' )
      );

      /*
      // add scripts and styles like this
      add_action( 'admin_print_scripts-' . $options_page, array( $this, 'admin_installer_js' ) );
      add_action( 'admin_print_styles-' . $options_page, array( $this, 'admin_installer_css' ) );
      add_action( 'admin_head-' . $options_page, array( $this, 'admin_installer_head' ), 50 );
      */
      
    }

  }


  /**
   * Perform necessary stuff prior to CiviCRM's admin page being loaded
   *
   * @return void
   */
  public function admin_page_load() {

    // add resources for back end
    $this->add_core_resources( FALSE );

  }


  // ---------------------------------------------------------------------------
  // Installation
  // ---------------------------------------------------------------------------


  /**
   * Callback method for add_options_page() that runs the CiviCRM installer
   *
   * @return void
   */
  public function run_installer() {

    // uses CIVICRM_PLUGIN_DIR instead of WP_PLUGIN_DIR
    $installFile =
      CIVICRM_PLUGIN_DIR .
      'civicrm' . DIRECTORY_SEPARATOR .
      'install' . DIRECTORY_SEPARATOR .
      'index.php';

    // Notice: Undefined variable: siteDir in:
    // wp-content/plugins/civicrm/civicrm/install/index.php on line 456
    include ( $installFile );

  }


  /**
   * Callback method for missing settings file in register_hooks()
   *
   * @return void
   */
  public function show_setup_warning() {

    $installLink = admin_url() . "options-general.php?page=civicrm-install";
    echo '<div id="civicrm-warning" class="updated fade">' .
       '<p><strong>' .
       __( 'CiviCRM is almost ready.', 'civicrm' ) .
       '</strong> ' .
       sprintf(
        __( 'You must <a href="%s">configure CiviCRM</a> for it to work.', 'civicrm' ),
        $installLink
       ) .
       '</p></div>';

  }


  // ---------------------------------------------------------------------------
  // HTML head
  // ---------------------------------------------------------------------------


  /**
   * Add CiviCRM core resources
   *
   * @param bool $front_end True if on WP front end, false otherwise
   * @return void
   */
  private function add_core_resources( $front_end = TRUE ) {
  
    if (!$this->initialize()) {
      return;
    }
        
    $config = CRM_Core_Config::singleton();
    $config->userFrameworkFrontend = $front_end;
    
    // add CiviCRM core resources
    CRM_Core_Resources::singleton()->addCoreResources();
    
  }


  /**
   * Merge CiviCRM's HTML header with the WordPress theme's header
   * Callback from WordPress 'admin_head' and 'wp_head' hooks
   *
   * @return void
   */
  public function wp_head() {

    // CRM-11823 - If Civi bootstrapped, then merge its HTML header with the CMS's header
    global $civicrm_root;
    if ( empty( $civicrm_root ) ) {
      return;
    }

    $region = CRM_Core_Region::instance('html-header', FALSE);
    if ( $region ) {
      echo '<!-- CiviCRM html header -->';
      echo $region->render( '' );
    }

  }


  // ---------------------------------------------------------------------------
  // CiviCRM Invocation (this outputs Civi's markup)
  // ---------------------------------------------------------------------------


  /**
   * Invoke CiviCRM in a WordPress context
   * Callback function from add_menu_page()
   * Callback from WordPress 'init' and 'the_content' hooks
   * Also called by do_shortcode() and _civicrm_update_user()
   *
   * @return void
   */
  public function invoke() {

    static $alreadyInvoked = FALSE;
    if ( $alreadyInvoked ) {
      echo $this->invoke_multiple();
      return;
    }

    // bail if this is called via a content-preprocessing plugin
    if ( $this->is_page_request() && !in_the_loop() && !is_admin() ) {
      return;
    }
    
    if (!$this->initialize()) {
      return '';
    }

    // CRM-12523
    // WordPress has it's own timezone calculations
    // Civi relies on the php default timezone which WP
    // overrides with UTC in wp-settings.php
    $wpBaseTimezone = date_default_timezone_get();
    $wpUserTimezone = get_option('timezone_string');
    if ($wpUserTimezone) {
      date_default_timezone_set($wpUserTimezone);
      CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();
    }

    // Add our standard css & js
    //CRM_Core_Resources::singleton()->addCoreResources();

    // CRM-95XX
    // At this point we are calling a CiviCRM function
    // WP always quotes the request, CiviCRM needs to reverse what it just did
    $this->remove_wp_magic_quotes();
    
    global $current_user;
    get_currentuserinfo();
    
    /*
     * bypass synchronize if running upgrade
     * to avoid any serious non-recoverable error
     * which might hinder the upgrade process.
     */
    if ( CRM_Utils_Array::value('q', $_GET) != 'civicrm/upgrade' ) {
      require_once 'CRM/Core/BAO/UFMatch.php';
      CRM_Core_BAO_UFMatch::synchronize( $current_user, FALSE, 'WordPress', 'Individual', TRUE );
    }
    
    // set flag
    $alreadyInvoked = TRUE;

    // get args
    $argdata = $this->get_request_args();
    
    // set dashboard as default if args are empty
   if ( !isset( $_GET['q'] ) ) {
      $_GET['q']      = 'civicrm/dashboard';
      $_GET['reset']  = 1;
      $argdata['args'] = array('civicrm', 'dashboard');
    }
    
    // do the business
    CRM_Core_Invoke::invoke($argdata['args']);

    // restore WP's timezone
    if ($wpBaseTimezone) {
      date_default_timezone_set($wpBaseTimezone);
    }

    // restore WP's arrays
    $this->restore_wp_magic_quotes();
    
    // notify plugins
    do_action( 'civicrm_invoked' );

  }


  /**
   * Return a generic display instead of a CiviCRM invocation
   *
   * @param int $post_id The containing WordPress post ID
   * @param str $shortcode The shortcode being parsed
   * @return str $markup Generic markup for multiple instances
   */
  private function invoke_multiple( $post_id = FALSE, $shortcode = FALSE ) {
    
    // sanity check
    if ( ! $post_id ) return '';
    
    // init markup with a container
    $markup = '<div class="crm-container crm-public">';
    
    $markup .= '<h2>' . __( 'Content via CiviCRM', 'civicrm' ) . '</h2>';
    
    $markup .= '<p>' . sprintf(
      __( 'To view this content, <a href="%s">visit the entry</a>.', 'civicrm' ),
      get_permalink( $post_id )
    ) . '</p>';
    
    // let's have a footer
    $markup .= '<div class="crm-public-footer">';
    $civi = __( 'CiviCRM.org - Growing and Sustaining Relationships', 'civicrm' );
    $logo = '<div class="empowered-by-logo"><span>CiviCRM</span></div>';
    $markup .= sprintf( 
      __( 'Empowered by <a href="http://civicrm.org/" title="%s" target="_blank" class="empowered-by-link">%s</a>', 'civicrm' ),
      $civi,
      $logo
    );
    $markup .= '</div>';
    
    // close container
    $markup .= '</div>';
    
    // allow plugins to override
    return apply_filters( 'civicrm_invoke_multiple', $markup, $post_id, $shortcode );
    
  }


  /**
   * Non-destructively override WordPress magic quotes
   * Only called by invoke() to undo WordPress default behaviour
   * CMW: Should probably be a private method
   *
   * @return void
   */
  public function remove_wp_magic_quotes() {
    
    // save original arrays
    $this->wp_get     = $_GET;
    $this->wp_post    = $_POST;
    $this->wp_cookie  = $_COOKIE;
    $this->wp_request = $_REQUEST;

    // reassign globals
    $_GET     = stripslashes_deep($_GET);
    $_POST    = stripslashes_deep($_POST);
    $_COOKIE  = stripslashes_deep($_COOKIE);
    $_REQUEST = stripslashes_deep($_REQUEST);

  }


  /**
   * Restore WordPress magic quotes
   * Only called by invoke() to redo WordPress default behaviour
   * CMW: Should probably be a private method
   *
   * @return void
   */
  public function restore_wp_magic_quotes() {

    // restore original arrays
    $_GET     = $this->wp_get;
    $_POST    = $this->wp_post;
    $_COOKIE  = $this->wp_cookie;
    $_REQUEST = $this->wp_request;

  }


  /**
   * Detect Ajax, snippet, or file requests
   *
   * @return boolean True if request is for a CiviCRM page, false otherwise
   */
  public function is_page_request() {
    
    // kick out if not CiviCRM
    if (!$this->initialize()) {
      return;
    }

    // get args
    $argdata = $this->get_request_args();
    
    // FIXME: It's not sustainable to hardcode a whitelist of all of non-HTML
    // pages. Maybe the menu-XML should include some metadata to make this
    // unnecessary?
    if (CRM_Utils_Array::value('HTTP_X_REQUESTED_WITH', $_SERVER) == 'XMLHttpRequest'
        || ($argdata['args'][0] == 'civicrm' && in_array($argdata['args'][1], array('ajax', 'file')) )
        || !empty($_REQUEST['snippet'])
        || strpos($argdata['argString'], 'civicrm/event/ical') === 0 && empty($_GET['html'])
        || strpos($argdata['argString'], 'civicrm/contact/imagefile') === 0
    ) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }


  /**
   * Get arguments and request string from $_GET
   *
   * @return array $argdata Array containing request arguments and request string
   */
  private function get_request_args() {
  
    $argString = NULL;
    $args = array();
    if (isset( $_GET['q'])) {
      $argString = trim($_GET['q']);
      $args = explode('/', $argString);
    }
    $args = array_pad($args, 2, '');
    
    return array( 
      'args' => $args,
      'argString' => $argString
    );
    
  }


  // ---------------------------------------------------------------------------
  // Shortcode Handling
  // ---------------------------------------------------------------------------


  /**
   * Determine if a CiviCRM shortcode is present in any of the posts about to be displayed
   * Callback method for 'wp' hook, always called from WP front-end
   *
   * @param object $wp The WP object, present but not used
   * @return void
   */
  public function handle_shortcodes( $wp ) {

    /**
     * At this point, all conditional tags are available
     * @see http://codex.wordpress.org/Conditional_Tags
     */
    
    // bail if this is a 404
    if ( is_404() ) return;
    
    // a counter's useful
    $shortcodes_present = 0;
    
    // let's loop through the results
    // this also has the effect of bypassing the logic in
    // https://github.com/civicrm/civicrm-wordpress/pull/36
    if ( have_posts() ) {
      while ( have_posts() ) : the_post();
        
        global $post;
      
        // check for existence of shortcode in content
        if ( has_shortcode( $post->post_content, 'civicrm' ) ) {
          
          // if on first instance...
          if ( !isset( $this->shortcode_present ) ) {
          
            // add core resources for front end
            $this->add_core_resources( TRUE );
            
          }
          
          // get CiviCRM shortcodes in this post
          $shortcodes_array = $this->get_shortcodes( $post->post_content );
          
          // sanity check
          if ( !empty( $shortcodes_array ) ) {
            
            // add it to our property
            $this->shortcodes[$post->ID] = $shortcodes_array;
            
            // bump shortcode counter
            $shortcodes_present += count( $this->shortcodes[$post->ID] );
          
          }
          
        }
        
      endwhile;
    }
    
    // reset loop
    rewind_posts();
    
    // did we get any?
    if ( $shortcodes_present ) {
      
      // how should we handle multiple shortcodes?
      if ( $shortcodes_present > 1 ) {
        
        // let's add dummy markup
        foreach( $this->shortcodes AS $post_id => $shortcode_array ) {
          foreach( $shortcode_array AS $shortcode ) {
            
            $this->shortcode_markup[$post_id][] = $this->invoke_multiple( $post_id, $shortcode );
            
          }
        }
        
      } else {
        
        // since we have only one shortcode, run the_loop again
        // the DB query has already been done, so this has no significant impact
        if ( have_posts() ) {
          while ( have_posts() ) : the_post();
          
            global $post;
            
            // is this the post?
            if ( ! array_key_exists( $post->ID, $this->shortcodes ) ) {
              continue;
            }
            
            // the shortcode must be the first item in the shortcodes array
            $shortcode = $this->shortcodes[$post->ID][0];
            
            // check to see if a shortcode component has been repeated?
            $text = str_replace( '[civicrm ', '', $shortcode );
            $text = str_replace( ']', '', $text );
            $atts = shortcode_parse_atts( $text );
            //print_r( $atts );
            
            // test for hijacking
            if ( isset( $atts['hijack'] ) AND $atts['hijack'] == '1' ) {
              add_filter( 'civicrm_context', array( $this, 'shortcode_context' ) );
            }
            
            // store corresponding markup
            $this->shortcode_markup[$post->ID][] = do_shortcode( $shortcode );
            
            // test for hijacking
            if ( isset( $atts['hijack'] ) AND $atts['hijack'] == '1' ) {
              
              // ditch the filter
              remove_filter( 'civicrm_context', array( $this, 'shortcode_context' ) );
              
              // set title
              global $civicrm_wp_title;
              $post->post_title = $civicrm_wp_title;
              
              // override page title
              add_filter( 'wp_title', array( $this, 'override_page_title' ), 50, 3 );
    
              // overwrite content
              add_filter( 'the_content', array( $this, 'shortcode_content' ) );
              
            }
            
          endwhile;
        }
        
        // reset loop
        rewind_posts();
    
      }
      
    }
      
    // flag that we have parsed shortcodes
    $this->shortcodes_parsed = TRUE;
    
    // broadcast this as well
    do_action( 'civicrm_shortcodes_parsed' );
  
    // trace
    //print_r( 'shortcodes_present: ' . $shortcodes_present ."\n" );
    //print_r( $this->shortcodes );
    //print_r( $this->shortcode_markup ); die();
    
  }


  /**
   * In order to hijack the page, we need to override the context
   *
   * @return str Overridden context code
   */
  public function shortcode_context() {
    return 'nonpage';
  }


  /**
   * In order to hijack the page, we need to override the content
   *
   * @return str Overridden context code
   */
  public function shortcode_content( $content ) {
  	global $post;
    // is this the post?
    if ( ! array_key_exists( $post->ID, $this->shortcode_markup ) ) {
      return $content;
    }
    return $this->shortcode_markup[$post->ID][0];
  }


  /**
   * Detect and return CiviCRM shortcodes in post content
   *
   * @param $content The content to parse
   * @return array $shortcodes Array of shortcodes
   */
  private function get_shortcodes( $content ) {
  
    // init return array
    $shortcodes = array();
      
    // attempt to discover all instances of the shortcode
    $pattern = get_shortcode_regex();
    
    if ( 
      preg_match_all( '/' . $pattern . '/s', $content, $matches )
      && array_key_exists( 2, $matches )
      && in_array( 'civicrm', $matches[2] ) )
    {
    
      // get keys for our shortcode
      $keys = array_keys( $matches[2], 'civicrm' );
      
      foreach( $keys AS $key ) {
        $shortcodes[] = $matches[0][$key];
      }
      
    }
    
    return $shortcodes;
    
  }


  /**
   * Handles CiviCRM-defined shortcodes
   *
   * @param array Shortcode attributes array
   * @return string HTML for output
   */
  public function do_shortcode( $atts ) {
    
    // check if we've already parsed this shortcode
    global $post;
    if ( is_object($post) ) {
      if ( !empty( $this->shortcode_markup ) ) {
        if ( isset( $this->shortcode_markup[$post->ID] ) ) {
          
          // this shortcode must have been done
          return $this->shortcode_markup[$post->ID][0];
          
        }
      }
    }
    
    extract( shortcode_atts( array(
      'component' => 'contribution',
      'action' => NULL,
      'mode' => NULL,
      'id' => NULL,
      'cid' => NULL,
      'gid' => NULL,
      'cs' => NULL,
      'force' => NULL,
      ),
      $atts
    ) );

    $args = array(
      'reset' => 1,
      'id'    => $id,
      'force' => $force,
    );

    switch ( $component ) {

      case 'contribution':

        if ( $mode == 'preview' || $mode == 'test' ) {
          $args['action'] = 'preview';
        }
        $args['q'] = 'civicrm/contribute/transact';
        break;

      case 'event':

        switch ( $action ) {
          case 'register':
            $args['q'] = 'civicrm/event/register';
            if ( $mode == 'preview' || $mode == 'test' ) {
              $args['action'] = 'preview';
            }
            break;

          case 'info':
            $args['q'] = 'civicrm/event/info';
            $_REQUEST['page'] = $_GET['page'] = 'CiviCRM';
            break;

          default:
            echo '<p>' . __( 'Do not know how to handle this shortcode', 'civicrm' ) . '</p>';
            return;
        }
        break;

      case 'user-dashboard':

        $args['q'] = 'civicrm/user';
        unset( $args['id'] );
        break;

      case 'profile':

        if ($mode == 'edit') {
          $args['q'] = 'civicrm/profile/edit';
        }
        elseif ($mode == 'view') {
          $args['q'] = 'civicrm/profile/view';
        }
        elseif ($mode == 'search') {
          $args['q'] = 'civicrm/profile';
        }
        else {
          $args['q'] = 'civicrm/profile/create';
        }
        $args['gid'] = $gid;
        break;


      case 'petition':

        $args['q'] = 'civicrm/petition/sign';
        $args['sid'] = $args['id'];
        unset($args['id']);
        break;

      default:

        echo '<p>' . __( 'Do not know how to handle this shortcode', 'civicrm' ) . '</p>';
        return;

    }

    foreach ( $args as $key => $value ) {
      if ( $value !== NULL ) {
        $_REQUEST[$key] = $_GET[$key] = $value;
      }
    }

    // kick out if not CiviCRM
    if (!$this->initialize()) {
      return '';
    }

    // check permission
    $argdata = $this->get_request_args();
    if ( ! $this->check_permission( $argdata['args'] ) ) {
      return $this->get_permission_denied();;
    }

    // CMW: why do we need this? Nothing that follows uses it...
    require_once ABSPATH . WPINC . '/pluggable.php';
    
    ob_start(); // start buffering
    $this->invoke(); // now, instead of echoing, shortcode output ends up in buffer
    $content = ob_get_clean(); // save the output and flush the buffer
    return $content;

  }


  // ---------------------------------------------------------------------------
  // Standalone front-end pages
  // ---------------------------------------------------------------------------


  /**
   * Build CiviCRM base page content
   * Callback method for 'wp' hook, always called from WP front-end
   *
   * @param object $wp The WP object, present but not used
   * @return void
   */
  public function handle_basepage( $wp ) {

    /**
     * At this point, all conditional tags are available
     * @see http://codex.wordpress.org/Conditional_Tags
     */
    
    // bail if this is a 404
    if ( is_404() ) return;
    
    // kick out if not CiviCRM
    if (!$this->initialize()) {
      return '';
    }
    
    //add_filter( 'civicrm_in_wordpress', '__return_false' );

    // CMW: why do we need this? Nothing that follows uses it...
    require_once ABSPATH . WPINC . '/pluggable.php';

    // let's do the_loop
    // this has the effect of bypassing the logic in
    // https://github.com/civicrm/civicrm-wordpress/pull/36
    if ( have_posts() ) {
      while ( have_posts() ) : the_post();
        
        global $post;
        
        ob_start(); // start buffering
        $this->invoke(); // now, instead of echoing, base page output ends up in buffer
        $this->basepage_markup = ob_get_clean(); // save the output and flush the buffer
        
        // override post title
        global $civicrm_wp_title;
        $post->post_title = $civicrm_wp_title;
        
        // disallow commenting
        $post->comment_status = 'closed';
        
      endwhile;
    }
    
    // reset loop
    rewind_posts();
    
    // override page title
    add_filter( 'wp_title', array( $this, 'override_page_title' ), 50, 3 );
    
    // include this content when base page is rendered
    add_filter( 'the_content', array( $this, 'do_basepage' ) );

    // hide the edit link
    add_action( 'edit_post_link', array( $this, 'clear_edit_post_link' ) );
    
    // why why why?
    //add_action( 'wp', array( $this, 'turn_comments_off' ) );
    //add_action( 'wp', array( $this, 'set_post_blank' ) );
    
    // flag that we have parsed the base page
    $this->basepage_parsed = TRUE;
    
    // broadcast this as well
    do_action( 'civicrm_basepage_parsed' );
    
    /*
    // trace
    print_r( 'handle_basepage' . "\n" ); //die();
    print_r( 'title: ' . $civicrm_wp_title ); //die();
    print_r( $this->basepage_markup ); die();
    */
    
  }


  /**
   * Get CiviCRM base page content
   * Callback method for 'the_content' hook, always called from WP front-end
   *
   * @param object $wp The WP object, present but not used
   * @return void
   */
  public function do_basepage() {
    
    // hand back our base page markup
    return $this->basepage_markup;
  
  }


  /**
   * Get CiviCRM base page template
   * Callback method for 'template_include' hook, always called from WP front-end
   *
   * @param str $template The path to the existing template
   * @return str $template The modified path to the desired template
   */
  public function basepage_template( $template ) {
    
    // use the basic page template, but allow overrides
    $page_template = locate_template( array(
      apply_filters( 'civicrm_basepage_template', 'page.php' )
    ) );
    
    if ( '' != $page_template ) {
      return $page_template;
    }
    
    // fallback
    return $template;
  
  }


  /**
   * Override a WordPress page title with the CiviCRM entity title
   * Callback method for 'wp_title' hook, always called from WP front-end
   *
   * @return str $title The title of the CiviCRM entity
   */
  public function override_page_title( $title, $sep, $seplocation ) {
    
    // only on singular pages
    if ( ! is_singular() ) return $title;

    global $civicrm_wp_title;
    
    // Determines position of the separator and direction of the breadcrumb
    if ( 'right' == $seplocation ) { // sep on right, so reverse the order
      $title = $civicrm_wp_title . " $sep ";
    } else {
      $title = " $sep " . $civicrm_wp_title;
    }
    
    return $title;
  
  }


  /**
   * Callback from 'edit_post_link' hook to remove edit link in set_post_blank()
   *
   * @return string Always empty
   */
  public function clear_edit_post_link() {
    return '';
  }


  // ---------------------------------------------------------------------------
  // User-related methods
  // ---------------------------------------------------------------------------


  /**
   * Authentication function used by register_basepage_hooks()
   *
   * @return bool True if authenticated, false otherwise
   */
  public function check_permission( $args ) {
  
    if ( $args[0] != 'civicrm' ) {
      return FALSE;
    }

    $config = CRM_Core_Config::singleton();

    // set frontend true
    $config->userFrameworkFrontend = TRUE;

    require_once 'CRM/Utils/Array.php';
    
    // all profile and file urls, as well as user dashboard and tell-a-friend are valid
    $arg1 = CRM_Utils_Array::value(1, $args);
    $invalidPaths = array('admin');
    if ( in_array( $arg1, $invalidPaths ) ) {
      return FALSE;
    }

    return TRUE;
    
  }


  /**
   * Called when authentication fails in register_basepage_hooks()
   *
   * @return string Warning message
   */
  public function get_permission_denied() {
    return __( 'You do not have permission to access this content.', 'civicrm' );
  }


  /**
   * Keep WordPress user synced with CiviCRM Contact
   * Callback function for 'user_register' hook
   * Callback function for 'profile_update' hook
   *
   * CMW: seems to (wrongly) create new CiviCRM Contact every time a user changes their
   * first_name or last_name attributes in WordPress.
   *
   * @return void
   */
  public function update_user( $userID ) {

    $user = get_userdata( $userID );
    if ( $user ) {

      if (!$this->initialize()) {
        return;
      }

      require_once 'CRM/Core/BAO/UFMatch.php';

      // this does not return anything, so if we want to do anything further
      // to the CiviCRM Contact, we have to search for it all over again...
      CRM_Core_BAO_UFMatch::synchronize(
        $user, // user object
        TRUE, // update = true
        'WordPress', // CMS
        'Individual' // contact type
      );

      /*
      // IN progress: synchronizeUFMatch does return the contact object, however
      $civi_contact = CRM_Core_BAO_UFMatch::synchronizeUFMatch(
        $user, // user object
        $user->ID, // ID
        $user->user_mail, // unique identifier
        null // unused
        'WordPress' // CMS
        'Individual' // contact type
      );

      // now we can allow other plugins to do their thing
      do_action( 'civicrm_contact_synced', $user, $civi_contact );
      */

    }

  }


  /**
   * When a WordPress user is deleted, delete the ufMatch record
   * Callback function for 'delete_user' hook
   *
   * @param $userID The numerical ID of the WordPress user
   * @return void
   */
  public function delete_user_ufmatch( $userID ) {

    if (!$this->initialize()) {
      return;
    }

    // delete the ufMatch record
    require_once 'CRM/Core/BAO/UFMatch.php';
    CRM_Core_BAO_UFMatch::deleteUser($userID);

  }


  /**
   * Function to create 'anonymous_user' role, if 'anonymous_user' role is not in 
   * the WordPress installation and assign minimum capabilities for all WordPress roles
   *
   * The legacy global scope function civicrm_wp_set_capabilities() is called from
   * upgrade_4_3_alpha1()
   *
   * @return void
   */
  public function set_wp_user_capabilities() {

    global $wp_roles;
    if ( ! isset( $wp_roles ) ) {
      $wp_roles = new WP_Roles();
    }

    // Minimum capabilities (Civicrm permissions) arrays
    $default_min_capabilities =  array(
      'access_civimail_subscribe_unsubscribe_pages' => 1,
      'access_all_custom_data' => 1,
      'access_uploaded_files' => 1,
      'make_online_contributions' => 1,
      'profile_create' => 1,
      'profile_edit' => 1,
      'profile_view' => 1,
      'register_for_events' => 1,
      'view_event_info' => 1,
      'sign_civicrm_petition' => 1,
      'view_public_civimail_content' => 1,
    );

    // allow other plugins to filter
    $min_capabilities = apply_filters( 'civicrm_min_capabilities', $default_min_capabilities );

    // Assign the Minimum capabilities (Civicrm permissions) to all WP roles
    foreach ( $wp_roles->role_names as $role => $name ) {
      $roleObj = $wp_roles->get_role( $role );
      foreach ( $min_capabilities as $capability_name => $capability_value ) {
        $roleObj->add_cap( $capability_name );
      }
    }

    // Add the 'anonymous_user' role with minimum capabilities.
    if ( ! in_array( 'anonymous_user' , $wp_roles->roles ) ) {
      add_role(
        'anonymous_user',
        __( 'Anonymous User', 'civicrm' ),
        $min_capabilities
      );
    }

  }


  /**
   * Add CiviCRM access capabilities to WordPress roles
   * this is a callback for the 'init' hook in register_hooks()
   *
   * The legacy global scope function wp_civicrm_capability() is called by
   * postProcess() in civicrm/CRM/ACL/Form/WordPress/Permissions.php
   *
   * @return void
   */
  public function set_access_capabilities() {

    // test for existing global
    global $wp_roles;
    if ( ! isset( $wp_roles ) ) {
      $wp_roles = new WP_Roles();
    }

    // give access to civicrm page menu link to particular roles
    $roles = apply_filters( 'civicrm_access_roles', array( 'super admin', 'administrator' ) );
    foreach ( $roles as $role ) {
      $roleObj = $wp_roles->get_role( $role );
      if (
        is_object( $roleObj ) &&
        is_array( $roleObj->capabilities ) &&
        ! array_key_exists( 'access_civicrm', $wp_roles->get_role( $role )->capabilities )
      ) {
        $wp_roles->add_cap( $role, 'access_civicrm' );
      }
    }

  }


  /**
   * Get CiviCRM contact type
   *
   * @param string $default contact type
   * @return string $ctype contact type
   */
  public function get_civicrm_contact_type( $default = NULL ) {

    // here we are creating a new contact
    // get the contact type from the POST variables if any
    if ( isset( $_REQUEST['ctype'] ) ) {
      $ctype = $_REQUEST['ctype'];
    } elseif (
      isset( $_REQUEST['edit'] ) &&
      isset( $_REQUEST['edit']['ctype'] )
    ) {
      $ctype = $_REQUEST['edit']['ctype'];
    } else {
      $ctype = $default;
    }

    if (
      $ctype != 'Individual' &&
      $ctype != 'Organization' &&
      $ctype != 'Household'
    ) {
      $ctype = $default;
    }

    return $ctype;

  }


  // ---------------------------------------------------------------------------
  // CiviCRM Shortcode button
  // ---------------------------------------------------------------------------


  /**
   * Callback method for 'media_buttons' hook as set in register_hooks()
   *
   * @param string $editor_id Unique editor identifier, e.g. 'content'
   * @return void
   */
  public function add_form_button( $editor_id ) {

    // add button to WP selected post types, if allowed
    if ( $this->post_type_has_button() ) {

      if (!$this->initialize()) {
        return '';
      }

      $config      = CRM_Core_Config::singleton();
      $imageBtnURL = $config->resourceBase . 'i/logo16px.png';
      $out         = '<a href="#TB_inline?width=480&inlineId=civicrm_frontend_pages" class="button thickbox" id="add_civi" style="padding-left: 4px;" title="' . __( 'Add CiviCRM Public Pages', 'civicrm' ) . '"><img src="' . $imageBtnURL . '" height="15" width="15" alt="' . __( 'Add CiviCRM Public Pages', 'civicrm' ) . '" />'. __( 'CiviCRM', 'civicrm' ) .'</a>';
      echo $out;

    }

  }


  /**
   * Callback method for 'admin_enqueue_scripts' hook as set in register_hooks()
   *
   * @return void
   */
  public function add_form_button_js( $hook ) {

    // are we on the page(s) we want?
    if ( !in_array(
      $hook,
      array( 'post.php', 'page.php', 'page-new.php', 'post-new.php' )
    ) ) {

      // bail
      return;

    }

    // enqueue script in footer
    $in_footer = TRUE;

    // construct path to file
    $src = plugins_url(
      'civicrm.js',
      __FILE__
    );

    // we now benefit from browser caching, concatenation, gzip compression, etc
    wp_enqueue_script(
      'civicrm_form_button_js', // handle
      $src, // src
      array( 'jquery' ), // deps
      CIVICRM_PLUGIN_VERSION,
      $in_footer
    );

  }


  /**
   * Does a WordPress post type have the CiviCRM button on it?
   *
   * @return bool $has_button True if the post type has the button, false otherwise
   */
  private function post_type_has_button() {
  
    // get screen object
    $screen = get_current_screen();
    
    // get post types that support the editor
    $capable_post_types = $this->get_post_types_with_editor();
    
    // default allowed to true on all capable post types
    $allowed = ( in_array( $screen->post_type, $capable_post_types ) ) ? true : false;
    
    // allow plugins to override
    $allowed = apply_filters( 'civicrm_restrict_button_appearance', $allowed, $screen );
    
    return $allowed;

  }


  /**
   * Get WordPress post types that support the editor
   *
   * @return array $supported_post_types Array of post types that have an editor
   */
  private function get_post_types_with_editor() {
  
    static $supported_post_types = array();
    if ( !empty( $supported_post_types) ) {
      return $supported_post_types;
    }
    
    // get only post types with an admin UI
    $args = array(
      'public'   => true,
      'show_ui' => true,
    );
    
    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'
    
    // get post types
    $post_types = get_post_types($args, $output, $operator);
    
    // init outputs
    $output = array();
    $options = '';
    
    // sanity check
    if ( count($post_types) > 0 ) {
      foreach($post_types AS $post_type) {
      
        // filter only those which have an editor
        if ( post_type_supports($post_type, 'editor') ) {
          $supported_post_types[] = $post_type;
        }
      }
    }
    
    return $supported_post_types;
  }


  /**
   * Get ID and title of CiviCRM contribution pages
   *
   * @access private
   * @return array $contributionPages Array of contribution pages
   */
  private function get_contribution_pages() {
    $now = date('Ymdhis');
    $sql = "
        SELECT id, title
        FROM   civicrm_contribution_page
        WHERE  is_active = 1
        AND    (
             ( start_date IS NULL AND end_date IS NULL )
        OR       ( start_date <= $now AND end_date IS NULL )
        OR       ( start_date IS NULL AND end_date >= $now )
        OR       ( start_date <= $now AND end_date >= $now )
             )
        ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $contributionPages = array();
    while ($dao->fetch()) {
      $contributionPages[$dao->id] = $dao->title;
    }
    return $contributionPages;
  }


  /**
   * Get ID and title of CiviCRM events
   *
   * @access private
   * @return array $eventPages Array of event pages
   */
  private function get_event() {
    $now = date('Ymdhis');
    $sql = "
        SELECT id, title
        FROM   civicrm_event
        WHERE  is_active = 1
        AND ( is_template = 0 OR is_template IS NULL )
        AND    (
             ( start_date IS NULL AND end_date IS NULL )
        OR       ( start_date <= $now AND end_date IS NULL )
        OR       ( start_date IS NULL AND end_date >= $now )
        OR       ( start_date <= $now AND end_date >= $now )
        OR       ( start_date >= $now )
             )
        ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $eventPages = array();
    while ($dao->fetch()) {
      $eventPages[$dao->id] = $dao->title;
    }
    return $eventPages;
  }


  /**
   * Get ID and title of CiviCRM profile pages
   *
   * @access private
   * @return array $profilePages Array of profile pages
   */
  private function get_profile_page() {
    $sql = "
        SELECT g.id as id, g.title as title
        FROM   civicrm_uf_group g, civicrm_uf_join j
        WHERE  g.is_active = 1
        AND    j.is_active = 1
        AND    ( group_type LIKE '%Individual%'
           OR    group_type LIKE '%Contact%' )
        AND    g.id = j.uf_group_id
        AND    j.module = 'Profile'
        ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $profilePages = array();
    while ($dao->fetch()) {
      $profilePages[$dao->id] = $dao->title;
    }
    return $profilePages;
  }


  /**
   * Get ID and title of CiviCRM petition pages
   *
   * @access private
   * @return array $petitionPages Array of petition pages
   */
  private function get_petition() {
    $params = array(
      'version' => 3,
      'is_active' => 1,
      'activity_type_id' => 'Petition',
      'return' => array('id', 'title'),

    );
    $result = civicrm_api('Survey', 'get', $params);

    $petitionPages = array();
    foreach ($result['values'] as $value) {
      $petitionPages[$value['id']] = $value['title'];
    }
    return $petitionPages;
  }


  /**
   * Callback method for 'admin_footer' hook as set in register_hooks()
   *
   * @return void
   */
  public function add_form_button_html() {

    // add modal to WP selected post types, if allowed
    if ( $this->post_type_has_button() ) {

      if (!$this->initialize()) {
        return '';
      }
      
      // include markup
      include_once( CIVICRM_PLUGIN_DIR . 'civicrm.modal.php' );
      
    }

  }


} // class CiviCRM_For_WordPress ends


/*
--------------------------------------------------------------------------------
Procedures start here
--------------------------------------------------------------------------------
*/


/**
 * The main function responsible for returning the CiviCRM_For_WordPress instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: $civi = civi_wp();
 *
 * @return CiviCRM_For_WordPress instance
 */
function civi_wp() {
  return CiviCRM_For_WordPress::singleton();
}

/**
 * Hook CiviCRM_For_WordPress early onto the 'plugins_loaded' action.
 *
 * This gives all other plugins the chance to load before CiviCRM, to get their
 * actions, filters, and overrides setup without CiviCRM being in the way.
 */
if ( defined( 'CIVICRM_LATE_LOAD' ) ) {
  add_action( 'plugins_loaded', 'civi_wp', (int) CIVICRM_LATE_LOAD );

// initialize
} else {
  civi_wp();
}


// tell WordPress to call plugin activation method, although it's still directed
// at the legacy callback, in case there are situations where the function is
// called from elsewhere. Should perhaps be:
// register_activation_hook( 'CiviCRM_For_WordPress, 'civicrm_activate' );
register_activation_hook( __FILE__, 'civicrm_activate' );


/*
--------------------------------------------------------------------------------
The global scope functions below are to maintain backwards compatibility with
previous versions of the CiviCRM WordPress plugin.
--------------------------------------------------------------------------------
*/


/**
 * add CiviCRM access capabilities to WordPress roles
 * Called by postProcess() in civicrm/CRM/ACL/Form/WordPress/Permissions.php
 * Also a callback for the 'init' hook in civi_wp()->register_hooks()
 */
function wp_civicrm_capability() {
  civi_wp()->set_access_capabilities();
}

/**
 * Test if CiviCRM is currently being displayed in WordPress
 * Called by setTitle() in civicrm/CRM/Utils/System/WordPress.php
 * Also called at the top of this plugin file to determine AJAX status
 */
function civicrm_wp_in_civicrm() {
  return civi_wp()->civicrm_in_wordpress();
}

/**
 * This was the original name of the initialization function and is
 * retained for backward compatibility
 */
function civicrm_wp_initialize() {
  return civi_wp()->initialize();
}

/**
 * Initialize CiviCRM. Call this function from other modules too if
 * they use the CiviCRM API.
 */
function civicrm_initialize() {
  return civi_wp()->initialize();
}

/**
 * Callback from 'edit_post_link' hook to remove edit link in civicrm_set_post_blank()
 */
function civicrm_set_blank() {
  return civi_wp()->clear_edit_post_link();
}

/**
 * Authentication function used by civicrm_wp_frontend()
 */
function civicrm_check_permission( $args ) {
  return civi_wp()->check_permission( $args );
}

/**
 * Called when authentication fails in civicrm_wp_frontend()
 */
function civicrm_set_frontendmessage() {
  return civi_wp()->get_permission_denied();
}

/**
 * Invoke CiviCRM in a WordPress context
 * Callback function from add_menu_page()
 * Callback from WordPress 'init' and 'the_content' hooks
 * Also used by civicrm_wp_shortcode_includes() and _civicrm_update_user()
 */
function civicrm_wp_invoke() {
  civi_wp()->invoke();
}

/**
 * Method that runs only when civicrm plugin is activated.
 */
function civicrm_activate() {
  civi_wp()->activate();
}

/**
 * Function to create anonymous_user' role, if 'anonymous_user' role is not in the wordpress installation
 * and assign minimum capabilities for all wordpress roles
 * This function is called on plugin activation and also from upgrade_4_3_alpha1()
 */
function civicrm_wp_set_capabilities() {
  civi_wp()->set_wp_user_capabilities();
}

/**
 * Callback function for add_options_page() that runs the CiviCRM installer
 */
function civicrm_run_installer() {
  civi_wp()->run_installer();
}

/**
 * Function to get the contact type
 * @param string $default contact type
 * @return $ctype contact type
 */
function civicrm_get_ctype( $default = NULL ) {
  return civi_wp()->get_civicrm_contact_type( $default );
}

/**
 * Getter function for global $wp_set_breadCrumb
 * Called by appendBreadCrumb() in civicrm/CRM/Utils/System/WordPress.php
 */
function wp_get_breadcrumb() {
  global $wp_set_breadCrumb;
  return $wp_set_breadCrumb;
}

/**
 * Setter function for global $wp_set_breadCrumb
 * Called by appendBreadCrumb() in civicrm/CRM/Utils/System/WordPress.php
 * Called by resetBreadCrumb() in civicrm/CRM/Utils/System/WordPress.php
 */
function wp_set_breadcrumb( $breadCrumb ) {
  global $wp_set_breadCrumb;
  $wp_set_breadCrumb = $breadCrumb;
  return $wp_set_breadCrumb;
}


/**
 * Incorporate WP-CLI Integration
 * Based on drush civicrm functionality, work done by Andy Walker
 * https://github.com/andy-walker/wp-cli-civicrm
 */
if ( defined('WP_CLI') && WP_CLI ) {
  // changed from __DIR__ because of possible symlink issues
  include_once CIVICRM_PLUGIN_DIR . 'wp-cli/civicrm.php';
}
