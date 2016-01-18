<?php
/*
Plugin Name: CiviCRM
Description: CiviCRM - Growing and Sustaining Relationships
Version: 4.7
Author: CiviCRM LLC
Author URI: http://civicrm.org/
Plugin URI: http://wiki.civicrm.org/confluence/display/CRMDOC/WordPress+Installation+Guide+for+CiviCRM+4.7
License: AGPL3
Text Domain: civicrm
Domain Path: /languages
*/


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
define( 'CIVICRM_PLUGIN_VERSION', '4.7' );

// store reference to this file
if (!defined('CIVICRM_PLUGIN_FILE')) {
  define( 'CIVICRM_PLUGIN_FILE', __FILE__ );
}

// store URL to this plugin's directory
if (!defined( 'CIVICRM_PLUGIN_URL')) {
  define( 'CIVICRM_PLUGIN_URL', plugin_dir_url(CIVICRM_PLUGIN_FILE) );
}

// store PATH to this plugin's directory
if (!defined( 'CIVICRM_PLUGIN_DIR')) {
  define( 'CIVICRM_PLUGIN_DIR', plugin_dir_path(CIVICRM_PLUGIN_FILE) );
}

// Test where the settings file exists, if in the 4.6 and prior location use that as CIVICRM_SETTINGS_PATH, otherwise set the new location as CIVICRM_SETTINGS_PATH
  $upload_dir    = wp_upload_dir();
  $wp_civi_settings = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm.settings.php' ;
  $wp_civi_settings_deprectated = CIVICRM_PLUGIN_DIR . 'civicrm.settings.php';

if (file_exists($wp_civi_settings_deprectated)) {
  define( 'CIVICRM_SETTINGS_PATH', $wp_civi_settings_deprectated );
}
else  {
  define( 'CIVICRM_SETTINGS_PATH', $wp_civi_settings );
}

// test if Civi is installed
if ( file_exists( CIVICRM_SETTINGS_PATH )  ) {
    define( 'CIVICRM_INSTALLED', TRUE );
  } else {
    define( 'CIVICRM_INSTALLED', FALSE );
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

  /**
   * @var CiviCRM_For_WordPress
   */
  private static $instance;

  // plugin context (broad)
  static $in_wordpress;

  // plugin context (specific)
  static $context;

  /**
   * @var CiviCRM_For_WordPress_Shortcodes
   */
  public $shortcodes;

  /**
   * @var CiviCRM_For_WordPress_Shortcodes_Modal
   */
  public $modal;

  /**
   * @var CiviCRM_For_WordPress_Basepage
   */
  public $basepage;

  /**
   * @var CiviCRM_For_WordPress_Users
   */
  public $users;


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
   * Method that is called only when CiviCRM plugin is activated
   * In order for other plugins to be able to interact with Civi's activation,
   * we wait until after the activation redirect to perform activation actions
   *
   * @return void
   */
  public function activate() {

    // set a one-time-only option
    add_option( 'civicrm_activation_in_progress', 'true' );

  }


  /**
   * Method that runs CiviCRM's plugin activation methods
   *
   * @return void
   */
  public function activation() {

    // if activating...
    if ( is_admin() && get_option( 'civicrm_activation_in_progress' ) == 'true' ) {

      // assign minimum capabilities for all WP roles and create 'anonymous_user' role
      $this->users->set_wp_user_capabilities();

      // set a one-time-only option to flag that we need to create a basepage -
      // it will not update the option once it has been set to another value nor
      // create a new option with the same name
      add_option( 'civicrm_activation_create_basepage', 'true' );

      // change option so this method never runs again
      update_option( 'civicrm_activation_in_progress', 'false' );

    }

    // if activating and we still haven't created the basepage...
    if (
      is_admin() &&
      get_option( 'civicrm_activation_create_basepage' ) == 'true' &&
      CIVICRM_INSTALLED
    ) {

      // create basepage
      add_action( 'wp_loaded', array( $this, 'create_wp_basepage' ) );

      // change option so this method never runs again
      update_option( 'civicrm_activation_create_basepage', 'done' );

    }

  }


  /**
   * Method that is called only when CiviCRM plugin is deactivated
   * In order for other plugins to be able to interact with Civi's activation,
   * we need to remove the option that is set in activate() above
   *
   * @return void
   */
  public function deactivate() {

    // delete options
    delete_option( 'civicrm_activation_in_progress' );
    delete_option( 'civicrm_activation_create_basepage' );

  }


  /**
   * Set up the CiviCRM plugin instance
   *
   * @return void
   */
  public function setup_instance() {

    // kick out if another instance is being inited
    if ( isset( self::$in_wordpress ) ) {
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

    // get classes and instantiate
    $this->include_files();

    // do plugin activation
    $this->activation();

    // register all hooks
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
   * @param string $context
   *   One of the four context codes above
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
   * @return string
   *   The context in which Civi is displayed in WordPress
   */
  public function civicrm_context_get() {

    // already stored
    return apply_filters( 'civicrm_context', self::$context );

  }


  // ---------------------------------------------------------------------------
  // Files
  // ---------------------------------------------------------------------------


  /**
   * Include files
   *
   * @return void
   */
  public function include_files() {

    // include users class
    include_once CIVICRM_PLUGIN_DIR . 'includes/civicrm.users.php';
    $this->users = new CiviCRM_For_WordPress_Users;

    // include shortcodes class
    include_once CIVICRM_PLUGIN_DIR . 'includes/civicrm.shortcodes.php';
    $this->shortcodes = new CiviCRM_For_WordPress_Shortcodes;

    // include shortcodes modal dialog class
    include_once CIVICRM_PLUGIN_DIR . 'includes/civicrm.shortcodes.modal.php';
    $this->modal = new CiviCRM_For_WordPress_Shortcodes_Modal;

    // include basepage class
    include_once CIVICRM_PLUGIN_DIR . 'includes/civicrm.basepage.php';
    $this->basepage = new CiviCRM_For_WordPress_Basepage;

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
    $this->register_hooks_common();

    // when in WordPress admin...
    if ( is_admin() ) {

      // set context
      $this->civicrm_context_set( 'admin' );

      // handle WP admin context
      $this->register_hooks_admin();
      return;

    }

    // go no further if Civi not installed yet
    if ( ! CIVICRM_INSTALLED ) return;

    // when embedded via wpBasePage or AJAX call...
    if ( $this->civicrm_in_wordpress() ) {

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

        // add core resources for front end
        add_action( 'wp', array( $this, 'front_end_page_load' ) );

        // echo all output when WP has been set up but nothing has been rendered
        add_action( 'wp', array( $this, 'invoke' ) );
        return;

      }

      // set context
      $this->civicrm_context_set( 'basepage' );

      // if we get here, we must be in a wpBasePage context
      $this->basepage->register_hooks();
      return;

    }

    // set context
    $this->civicrm_context_set( 'shortcode' );

    // that leaves us with handling shortcodes, should they exist
    $this->shortcodes->register_hooks();

  }


  /**
   * Register hooks that must always be present
   *
   * @return void
   */
  public function register_hooks_common() {

    // use translation files
    add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

    // register user hooks
    $this->users->register_hooks();

  }


  /**
   * Register hooks to handle CiviCRM in a WordPress admin context
   *
   * @return void
   */
  public function register_hooks_admin() {

    // modify the admin menu
    add_action( 'admin_menu', array( $this, 'add_menu_items' ) );

    // set page title
    add_filter( 'admin_title', array( $this, 'set_admin_title' ) );

    // print CiviCRM's header
    add_action('admin_head', array( $this, 'wp_head' ), 50);

    // if settings file does not exist, show notice with link to installer
    if ( ! CIVICRM_INSTALLED ) {
      if ( isset( $_GET['page'] ) && $_GET['page'] == 'civicrm-install' ) {
        // register hooks for installer page?
      } else {
        // show notice
        add_action( 'admin_notices', array( $this, 'show_setup_warning' ) );
      }
    }

    // enable shortcode modal
    $this->modal->register_hooks();

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
      $minPhpVersion = '5.3.4';
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
      if ( ! CIVICRM_INSTALLED ) {
        $error = FALSE;
      } elseif ( file_exists( CIVICRM_SETTINGS_PATH) ) {
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
      //print_r( $config ); die();

      // sync the logged in user with WP
      global $current_user;
      if ( $current_user ) {

        // sync procedure sets session values for logged in users
        require_once 'CRM/Core/BAO/UFMatch.php';
        CRM_Core_BAO_UFMatch::synchronize(
          $current_user, // user object
          FALSE, // do not update
          'WordPress', // CMS
          $this->users->get_civicrm_contact_type('Individual')
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
    if ( CIVICRM_INSTALLED ) {

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

    } else {

      $civilogo = plugins_url(
        'civicrm/i/logo16px.png',
        __FILE__
      );

      // add top level menu item
      $menu_page = add_menu_page(
        __( 'CiviCRM Installer', 'civicrm' ),
        __( 'CiviCRM Installer', 'civicrm' ),
        'manage_options',
        'civicrm-install',
        array( $this, 'run_installer' ),
        $civilogo,
        apply_filters( 'civicrm_menu_item_position', '3.904981' ) // 3.9 + random digits to reduce risk of conflict
      );

      /*
      // add scripts and styles like this
      add_action( 'admin_print_scripts-' . $options_page, array( $this, 'admin_installer_js' ) );
      add_action( 'admin_print_styles-' . $options_page, array( $this, 'admin_installer_css' ) );
      add_action( 'admin_head-' . $options_page, array( $this, 'admin_installer_head' ), 50 );
      */

    }

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
    // CIVICRM_PLUGIN_DIR/civicrm/install/index.php on line 456
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


  /**
   * Create WordPress basepage and save setting
   *
   * @return void
   */
  public function create_wp_basepage() {

    if (!$this->initialize()) {
      return;
    }

    $config = CRM_Core_Config::singleton();

    // bail if we already have a basepage setting
    if ( !empty( $config->wpBasePage ) ) {
      return;
    }

    // default page slug, but allow overrides
    $slug = apply_filters( 'civicrm_basepage_slug', 'civicrm' );

    // get existing page with that slug
    $page = get_page_by_path( $slug );

    // does it exist?
    if ( $page ) {

      // we already have a basepage
      $result = $page->ID;

    } else {

      // create the basepage
      $result = $this->create_basepage( $slug );

    }

    // were we successful?
    if ( $result !== 0 AND !is_wp_error($result) ) {

      // get the post object
      $post = get_post( $result );

      $params = array(
        'version' => 3,
        'wpBasePage' => $post->post_name,
      );

      // save the setting
      civicrm_api3('setting', 'create', $params);

    }

  }


  /**
   * Create a WordPress page to act as the CiviCRM base page.
   *
   * @param string $slug The unique slug for the page - same as wpBasePage setting
   * @return int|WP_Error The page ID on success. The value 0 or WP_Error on failure
   */
  private function create_basepage( $slug ) {

    // if multisite, switch to main site
    if ( is_multisite() && !is_main_site() ) {

      // store this site
      $original_site = get_current_blog_id();

      // switch
      global $current_site;
      switch_to_blog( $current_site->blog_id );

    }

    // define basepage
    $page = array(
      'post_status' => 'publish',
      'post_type' => 'page',
      'post_parent' => 0,
      'comment_status' => 'closed',
      'ping_status' => 'closed',
      'to_ping' => '', // quick fix for Windows
      'pinged' => '', // quick fix for Windows
      'post_content_filtered' => '', // quick fix for Windows
      'post_excerpt' => '', // quick fix for Windows
      'menu_order' => 0,
      'post_name' => $slug,
    );

    // default page title, but allow overrides
    $page['post_title'] = apply_filters( 'civicrm_basepage_title', __( 'CiviCRM', 'civicrm' ) );

    // default content
    $content = __( 'Do not delete this page. Page content is generated by CiviCRM.', 'civicrm' );

    // set, but allow overrides
    $page['post_content'] = apply_filters( 'civicrm_basepage_content', $content );

    // insert the post into the database
    $page_id = wp_insert_post( $page );

    // switch back if we've switched
    if ( isset( $original_site ) ) {
      restore_current_blog();
    }

    return $page_id;
  }


  // ---------------------------------------------------------------------------
  // HTML head
  // ---------------------------------------------------------------------------


  /**
   * Perform necessary stuff prior to CiviCRM's admin page being loaded
   * This needs to be a method because it can then be hooked into WP at the
   * right time
   *
   * @return void
   */
  public function admin_page_load() {

    // add resources for back end
    $this->add_core_resources( FALSE );

    // check setting for path to wp-load.php
    $this->add_wpload_setting();

  }


  /**
   * When CiviCRM is loaded in WP Admin, check for the existence of a setting
   * which holds the path to wp-load.php. This is the only reliable way to
   * bootstrap WordPress from CiviCRM.
   *
   * CMW: I'm not entirely happy with this approach, because the value will be
   * different for different installs (e.g. when a dev site is migrated to live)
   * A better approach would be to store this setting in civicrm.settings.php as
   * a constant, but doing that involves a complicated process of getting a new
   * setting registered in the installer.
   *
   * Also, it needs to be decided whether this value should be tied to a CiviCRM
   * 'domain', since a single CiviCRM install could potentially be used by a
   * number of WordPress installs. This is not relevant to its use in WordPress
   * Multisite, because the path to wp-load.php is common to all sites on the
   * network.
   *
   * My final concern is that the value will only be set *after* someone visits
   * CiviCRM in the back end. I have restricted it to this so as not to add
   * overhead to the front end, but there remains the possibility that the value
   * could be missing. To repeat: this would be better in civicrm.settings.php.
   *
   * To get the path to wp-load.php, use:
   * $path = CRM_Core_BAO_Setting::getItem('CiviCRM Preferences', 'wpLoadPhp');
   *
   * @return void
   */
  public function add_wpload_setting() {

    if (!$this->initialize()) {
      return;
    }

    // get path to wp-load.php
    $path = ABSPATH . 'wp-load.php';

    // get the setting, if it exists
    $setting = CRM_Core_BAO_Setting::getItem('CiviCRM Preferences', 'wpLoadPhp');

    // if we don't have one, create it
    if ( is_null( $setting ) ) {
      CRM_Core_BAO_Setting::setItem($path, 'CiviCRM Preferences', 'wpLoadPhp');
    }

    // is it different to the one we've stored?
    if ( $setting !== $path ) {
      // yes - set new path (this could be because we've changed server or location)
      CRM_Core_BAO_Setting::setItem($path, 'CiviCRM Preferences', 'wpLoadPhp');
    }

  }


  /**
   * Perform necessary stuff prior to CiviCRM being loaded on the front end
   * This needs to be a method because it can then be hooked into WP at the
   * right time
   *
   * @return void
   */
  public function front_end_page_load() {

    static $frontend_loaded = FALSE;
    if ( $frontend_loaded ) {
      return;
    }

    // add resources for front end
    $this->add_core_resources( TRUE );

    // merge CiviCRM's HTML header with the WordPress theme's header
    add_action( 'wp_head', array( $this, 'wp_head' ) );

    // set flag so this only happens once
    $frontend_loaded = TRUE;

  }


  /**
   * Load only the CiviCRM CSS. This is needed because $this->front_end_page_load()
   * is only called when there is a single Civi entity present on a page or archive
   * and, whilst we don't want all the Javascript to load, we do want stylesheets
   *
   * @return void
   */
  public function front_end_css_load() {

    static $frontend_css_loaded = FALSE;
    if ( $frontend_css_loaded ) {
      return;
    }

    if (!$this->initialize()) {
      return;
    }

    $config = CRM_Core_Config::singleton();

    // default custom CSS to standalone
    $dependent = NULL;

    // Load core CSS
    if (!CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'disable_core_css')) {

      // enqueue stylesheet
      wp_enqueue_style(
        'civicrm_css',
        $config->resourceBase . 'css/civicrm.css',
        NULL, // dependencies
        CIVICRM_PLUGIN_VERSION, // version
        'all' // media
      );

      // custom CSS is dependent
      $dependent = array( 'civicrm_css' );

    }

    // Load custom CSS
    if (!empty($config->customCSSURL)) {
      wp_enqueue_style(
        'civicrm_custom_css',
        $config->customCSSURL,
        $dependent, // dependencies
        CIVICRM_PLUGIN_VERSION, // version
        'all' // media
      );
    }

    // set flag so this only happens once
    $frontend_css_loaded = TRUE;

  }


  /**
   * Add CiviCRM core resources
   *
   * @param bool $front_end True if on WP front end, false otherwise
   * @return void
   */
  public function add_core_resources( $front_end = TRUE ) {

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
   * Also called by shortcode_render() and _civicrm_update_user()
   *
   * @return void
   */
  public function invoke() {

    static $alreadyInvoked = FALSE;
    if ( $alreadyInvoked ) {
      return;
    }

    // bail if this is called via a content-preprocessing plugin
    if ( $this->is_page_request() && !in_the_loop() && !is_admin() ) {
      return;
    }

    if (!$this->initialize()) {
      return;
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

    // CRM-95XX
    // At this point we are calling a CiviCRM function
    // WP always quotes the request, CiviCRM needs to reverse what it just did
    $this->remove_wp_magic_quotes();

    // Code inside invoke() requires the current user to be set up
    $current_user = wp_get_current_user();

    /**
     * Bypass synchronize if running upgrade to avoid any serious non-recoverable
     * error which might hinder the upgrade process.
     */
    if ( CRM_Utils_Array::value('q', $_GET) != 'civicrm/upgrade' ) {
      $this->users->sync_user( $current_user );
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
  public function get_request_args() {

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

  /**
   * Add CiviCRM's title to the header's <title>
   *
   * @param string $title
   * @return string
   */
  public function set_admin_title($title) {
    global $civicrm_wp_title;
    if (!$civicrm_wp_title) {
      return $title;
    }
    // Replace 1st occurance of "CiviCRM" in the title
    $pos = strpos($title, 'CiviCRM');
    if ($pos !== FALSE) {
      return substr_replace($title, $civicrm_wp_title, $pos, 7);
    }
    return $civicrm_wp_title;
  }


  /**
   * Override a WordPress page title with the CiviCRM entity title
   * Callback method for 'single_page_title' hook, always called from WP front-end
   *
   * @param string $post_title The title of the WordPress page or post
   * @param object $post The WordPress post object the title applies to
   * @return string $civicrm_wp_title The title of the CiviCRM entity
   */
  public function single_page_title( $post_title, $post ) {

    // sanity check and override
    global $civicrm_wp_title;
    if (!empty($civicrm_wp_title)) {
      return $civicrm_wp_title;
    }

    // fallback
    return $post_title;

  }


  /**
   * Remove edit link from page content
   * Callback from 'edit_post_link' hook
   *
   * @return string Always empty
   */
  public function clear_edit_post_link() {
    return '';
  }


  /**
   * Remove edit link in WP Admin Bar
   * Callback from 'wp_before_admin_bar_render' hook
   *
   * @return void
   */
  public function clear_edit_post_menu_item() {

    // access object
    global $wp_admin_bar;

    // bail if in admin
    if ( is_admin() ) return;

    // remove the menu item from front end
    $wp_admin_bar->remove_menu( 'edit' );

  }


  /**
   * Clone of CRM_Utils_System_WordPress::getBaseUrl() whose access is set to
   * private. Until it is public, we cannot access the URL of the basepage since
   * CRM_Utils_System_WordPress::url()
   *
   * @param bool $absolute Passing TRUE prepends the scheme and domain, FALSE doesn't
   * @param bool $frontend Passing FALSE returns the admin URL
   * @param $forceBackend Passing TRUE overrides $frontend and returns the admin URL
   * @return mixed|null|string
   */
  public function get_base_url($absolute, $frontend, $forceBackend) {
    $config    = CRM_Core_Config::singleton();

    if (!isset($config->useFrameworkRelativeBase)) {
      $base = parse_url($config->userFrameworkBaseURL);
      $config->useFrameworkRelativeBase = $base['path'];
    }

    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    if ((is_admin() && !$frontend) || $forceBackend) {
      $base .= admin_url( 'admin.php' );
      return $base;
    }
    elseif (defined('CIVICRM_UF_WP_BASEPAGE')) {
      $base .= CIVICRM_UF_WP_BASEPAGE;
      return $base;
    }
    elseif (isset($config->wpBasePage)) {
      $base .= $config->wpBasePage;
      return $base;
    }

    return $base;
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


/**
 * Tell WordPress to call plugin activation method - no longer calls legacy
 * global scope function
 */
register_activation_hook( CIVICRM_PLUGIN_FILE, array( civi_wp(), 'activate' ) );


/**
 * Tell WordPress to call plugin deactivation method - needed in order to reset
 * the option that is set on activation.
 */
register_deactivation_hook( CIVICRM_PLUGIN_FILE, array( civi_wp(), 'deactivate' ) );


// uninstall uses the 'uninstall.php' method
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



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
  civi_wp()->users->set_access_capabilities();
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
  return civi_wp()->users->check_permission( $args );
}

/**
 * Called when authentication fails in civicrm_wp_frontend()
 */
function civicrm_set_frontendmessage() {
  return civi_wp()->users->get_permission_denied();
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
  civi_wp()->users->set_wp_user_capabilities();
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
  return civi_wp()->users->get_civicrm_contact_type( $default );
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
