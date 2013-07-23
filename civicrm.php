<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
Plugin Name: CiviCRM
Plugin URI: http://civicrm.org/
Description: CiviCRM WP Plugin
Author: CiviCRM LLC
Version: 4.3
Author URI: http://civicrm.org/
License: AGPL3
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
define( 'CIVICRM_PLUGIN_VERSION', '4.3' );

// define commonly used items as constants
define( 'CIVICRM_PLUGIN_DIR', plugin_dir_path(__FILE__) );
define( 'CIVICRM_SETTINGS_PATH', CIVICRM_PLUGIN_DIR . 'civicrm.settings.php' );

// prevent CiviCRM from rendering its own header
define( 'CIVICRM_UF_HEAD', TRUE );


/*
--------------------------------------------------------------------------------
CiviCRM_For_WordPress Class
--------------------------------------------------------------------------------
*/

class CiviCRM_For_WordPress {


  /**
   * declare our properties
   */

  // plugin instance
  private static $instance;

  // plugin context
  static $in_wordpress;


  /**
   * @description: getter method which returns the CiviCRM instance and optionally
   * creates one if it does not already exist. Standard CiviCRM singleton pattern.
   * @return CiviCRM plugin instance
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
   * @description: dummy instance constructor
   */
  function __construct() {}

  /**
   * @description: dummy magic method to prevent CiviCRM_For_WordPress from being cloned
   */
  public function __clone() {
    _doing_it_wrong( __FUNCTION__, __( 'Only one instance of CiviCRM_For_WordPress please', 'civicrm-wordpress' ), '4.3' );
  }

  /**
   * @description: dummy magic method to prevent CiviCRM_For_WordPress from being unserialized
   */
  public function __wakeup() {
    _doing_it_wrong( __FUNCTION__, __( 'Please do not serialize CiviCRM_For_WordPress', 'civicrm-wordpress' ), '4.3' );
  }


  /**
   * @description: method that runs only when CiviCRM plugin is activated
   */
  public function activate() {

    // Assign minimum capabilities for all WordPress roles and create 'anonymous_user' role
    $this->set_wp_user_capabilities();

  }


  /**
   * @description: set up the CiviCRM plugin instance
   */
  public function setup_instance() {

    // kick out if another instance is being inited
    if ( isset( $this->in_wordpress ) ) {
      wp_die( __( 'Only one instance of CiviCRM_For_WordPress please', 'civicrm-wordpress' ) );
    }

    // store context
    self::$in_wordpress = ( isset( $_GET['page'] ) && $_GET['page'] == 'CiviCRM' ) ? TRUE : FALSE;

    // there is no session handling in WP hence we start it for CiviCRM pages
    if (!session_id()) {
      session_start();
    }

    // this is required for ajax calls in civicrm
    if ( $this->civicrm_in_wordpress() ) {
      $_GET['noheader'] = TRUE;
    } else {
      $_GET['civicrm_install_type'] = 'wordpress';
    }

    $this->register_hooks();

    // notify plugins
    do_action( 'civicrm_instance_loaded' );

  }


  /**
   * @description: getter for testing if CiviCRM is currently being displayed in WordPress
   * @return bool $in_wordpress
   */
  public function civicrm_in_wordpress() {

    // already stored
    return self::$in_wordpress;

  }


  /**
   * @description: register hooks
   */
  public function register_hooks() {

    // always add the following hooks

    // use translation files
    add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

    // add CiviCRM access capabilities to WordPress roles
    add_action( 'init', array( $this, 'set_access_capabilities' ) );

    // synchronise users on insert and update
    add_action( 'user_register', array( $this, 'update_user' ) );
    add_action( 'profile_update', array( $this, 'update_user' ) );

    // register the CiviCRM shortcode
    add_shortcode( 'civicrm', array( $this, 'shortcode_handler' ) );


    // only when in WordPress admin...
    if ( is_admin() ) {

      // modify the admin menu
      add_action( 'admin_menu', array( $this, 'add_menu_items' ) );

      // the following three hooks CiviCRM button to post and page screens

      // adds the CiviCRM button to post and page screens
      add_action( 'media_buttons_context', array( $this, 'add_form_button' ) );

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

      // merge CiviCRM's HTML header with the WordPress theme's header
      add_action( 'admin_head', array( $this, 'wp_head' ) );


    // not in admin
    } else {

      // merge CiviCRM's HTML header with the WordPress theme's header
      add_action( 'wp_head', array( $this, 'wp_head' ) );

      // invoke CiviCRM when a shortcode is detected in the post content
      add_filter( 'get_header', array( $this, 'add_shortcode_includes' ) );

      // if embedded...
      if ( $this->civicrm_in_wordpress() ) {

        // output buffer in footer
        add_action( 'wp_footer', array( $this, 'buffer_end' ) );

        // we do this here rather than as an action, since we don't control the order
        $this->buffer_start();
        $this->wp_frontend();

      }

    }

  }


  /**
   * @description: initialize CiviCRM
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
            __( 'CiviCRM requires PHP Version %s or greater. You are running PHP Version %s', 'civicrm-wordpress' ),
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
        __( 'Please review the <a href="%s">WordPress Installation Guide</a> and the <a href="%s">Trouble-shooting page</a> for assistance. If you still need help installing, you can often find solutions to your issue by searching for the error message in the <a href="%s">installation support section of the community forum</a>.', 'civicrm-wordpress' ),
        $docLinkInstall,
        $docLinkTrouble,
        $forumLink
      );

      // does install message get used?
      $installMessage = sprintf(
        __( 'Click <a href="%s">here</a> for fresh install.', 'civicrm-wordpress' ),
        $installLink
      );

      if ($error == FALSE) {
        header( 'Location: ' . admin_url() . 'options-general.php?page=civicrm-install' );
        return FALSE;
      }

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
            __( 'Oops! - The path for including CiviCRM code files is not set properly. Most likely there is an error in the <em>civicrm_root</em> setting in your CiviCRM settings file (%s).', 'civicrm-wordpress' ),
            CIVICRM_SETTINGS_PATH
          ) .
          "</p><p class='error'> &raquo; " .
          sprintf(
            __( 'civicrm_root is currently set to: <em>%s</em>.', 'civicrm-wordpress' ),
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


  /**
   * @description: invoke CiviCRM in a WordPress context
   * Callback function from add_menu_page()
   * Callback from WordPress 'init' and 'the_content' hooks
   * Also called by add_shortcode_includes() and _civicrm_update_user()
   */
  public function invoke() {

    static $alreadyInvoked = FALSE;
    if ( $alreadyInvoked ) {
      return;
    }

    $alreadyInvoked = TRUE;
    if ( ! $this->initialize() ) {
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
    }

    // Add our standard css & js
    CRM_Core_Resources::singleton()->addCoreResources();

    // CRM-95XX
    // At this point we are calling a civicrm function
    // Since WP messes up and always quotes the request, we need to reverse
    // what it just did
    $this->remove_wp_magic_quotes();

    if ( isset( $_GET['q'] ) ) {
      $args = explode('/', trim($_GET['q']));
    } else {
      $_GET['q']     = 'civicrm/dashboard';
      $_GET['reset'] = 1;
      $args          = array('civicrm', 'dashboard');
    }

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

    // do the business
    CRM_Core_Invoke::invoke($args);

    // restore WP's timezone
    if ($wpBaseTimezone) {
      date_default_timezone_set($wpBaseTimezone);
    }

    // notify plugins
    do_action( 'civicrm_invoked' );

  }


  /**
   * @description: load translation files
   * A good reference on how to implement translation in WordPress:
   * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
   */
  public function enable_translation() {

    // not used, as there are no translations as yet
    load_plugin_textdomain(

      // unique name
      'civicrm-wordpress',

      // deprecated argument
      FALSE,

      // relative path to directory containing translation files
      dirname( plugin_basename( __FILE__ ) ) . '/languages/'

    );

  }


  /**
   * @description: Adds menu items to WordPress admin menu
   * Callback method for 'admin_menu' hook as set in register_hooks()
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
      add_menu_page(
        __( 'CiviCRM', 'civicrm-wordpress' ),
        __( 'CiviCRM', 'civicrm-wordpress' ),
        'access_civicrm',
        'CiviCRM',
        array( $this, 'invoke' ),
        $civilogo
      );

    } else {

      // add menu item to options menu
      add_options_page(
        __( 'CiviCRM Installer', 'civicrm-wordpress' ),
        __( 'CiviCRM Installer', 'civicrm-wordpress' ),
        'manage_options',
        'civicrm-install',
        array( $this, 'run_installer' )
      );

    }

  }


  /**
   * @description: callback function for add_options_page() that runs the CiviCRM installer
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
   * @description: callback function for missing settings file in register_hooks()
   */
  public function show_setup_warning() {

    $installLink = admin_url() . "options-general.php?page=civicrm-install";
    echo '<div id="civicrm-warning" class="updated fade">' .
       '<p><strong>' .
       __( 'CiviCRM is almost ready.', 'civicrm-wordpress' ) .
       '</strong> ' .
       sprintf(
        __( 'You must <a href="%s">configure CiviCRM</a> for it to work.', 'civicrm-wordpress' ),
        $installLink
       ) .
       '</p></div>';

  }


  /**
   * @description: merge CiviCRM's HTML header with the WordPress theme's header
   * Callback from WordPress 'admin_head' and 'wp_head' hooks
   */
  public function wp_head() {

    // CRM-11823 - If Civi bootstrapped, then merge its HTML header with the CMS's header
    global $civicrm_root;
    if ( empty( $civicrm_root ) ) {
      return;
    }

    $region = CRM_Core_Region::instance('html-header', FALSE);
    if ( $region ) {
      echo $region->render( '' );
    }

  }


  /**
   * @description: callback function for 'get_header' hook
   */
  public function add_shortcode_includes() {

    global $post;

    // don't parse content when there's no post object, eg on 404 pages
    if ( ! is_object( $post ) ) return;

    // check for existence of shortcode in content
    if ( preg_match( '/\[civicrm/', $post->post_content ) ) {

      if (!$this->initialize()) {
        return;
      }

      // add CiviCRM core resources
      CRM_Core_Resources::singleton()->addCoreResources();

    }

  }


  /**
   * @description: start buffering, called in register_hooks()
   */
  public function buffer_start() {
    ob_start( array( $this, 'buffer_callback' ) );
  }


  /**
   * @description: flush buffer, callback for 'wp_footer'
   */
  public function buffer_end() {
    ob_end_flush();
  }


  /**
   * @description: Callback for ob_start() in buffer_start()
   * @return string $buffer the markup
   */
  public function buffer_callback($buffer) {

    // modify buffer here, and then return the updated code
    return $buffer;

  }


  /**
  * @description: CiviCRM's theme integration method
  * Called by register_hooks() and shortcode_handler()
  */
  public function wp_frontend( $shortcode = FALSE ) {

    // kick out if not CiviCRM
    if ( ! $this->initialize() ) { return; }


    // add CiviCRM core resources
    CRM_Core_Resources::singleton()->addCoreResources();

    // set the frontend part for civicrm code
    $config = CRM_Core_Config::singleton();
    $config->userFrameworkFrontend = TRUE;

    $argString = NULL;
    $args = array();
    if (isset( $_GET['q'])) {
      $argString = trim($_GET['q']);
      $args = explode('/', $argString);
    }

    // CMW: hacky procedure for overriding WordPress page/post:
    // see comments on set_post_blank()
    if ( $shortcode ) {
      $this->turn_comments_off();
      // CMW: this fails, because a lot of the page (eg, title) has already been rendered
      $this->set_post_blank();
    } else {
      add_filter( 'get_header', array( $this, 'turn_comments_off' ) );
      add_filter( 'get_header', array( $this, 'set_post_blank' ) );
    }

    // check permission
    if ( ! $this->check_permission( $args ) ) {
      if ( $shortcode ) {
        $this->show_permission_denied();
      } else {
        add_filter( 'the_content', array( $this, 'show_permission_denied' ) );
      }
      return;
    }

    // CMW: why do we need this? Nothing that follows uses it...
    require_once ABSPATH . WPINC . '/pluggable.php';

    // output civicrm html only in a few cases and skip the WP header
    if (
      // snippet is set - i.e. ajax call
      CRM_Utils_Array::value('snippet', $_GET) ||
      // ical feed
      ($argString == 'civicrm/event/ical' &&
        // skip the html page since it is rendered in the CMS theme
        CRM_Utils_Array::value('html', $_GET) != 1) ||
      in_array(
        $argString,
        // ajax and file download urls
        array(
          'civicrm/ajax',
          'civicrm/file'
        )
      )
    ) {
      // from my limited understanding, putting this in the init hook allows civi to
      // echo all output and exit before the theme code outputs anything - lobo
      add_filter( 'init', array( $this, 'invoke' ) );
      return;
    }

    // this places civicrm inside frontend theme
    // wp documentation rocks if you know what you are looking for
    // but best way is to check other plugin implementation :)
    if ( $shortcode ) {

      // CMW: review in the light of proper shortcode research
      // First question: I can add more than one shortcode to a page, but
      // only the first one appears in the output, even though this runs twice

      ob_start(); // start buffering
      $this->invoke(); // now, instead of echoing, shortcode output ends up in buffer
      $content = ob_get_clean(); // save the output and flush the buffer
      return $content;

    } else {

      // see comments on set_post_blank()
      add_filter( 'the_content', array( $this, 'invoke' ) );

    }

  }


  /**
   * @description: override WordPress post comment status attribute in wp_frontend()
   * see comments on set_post_blank()
   */
  public function turn_comments_off() {

    global $post;

    // kick out when there's no post object, eg on 404 pages
    if ( ! is_object( $post ) ) return;

    // CMW: is there a reason why comments are not allowed?
    $post->comment_status = 'closed';

  }


  /**
   * @description: override WordPress post attributes in wp_frontend()
   *
   * CMW: the process of overriding WordPress post content should be done in a way
   * analogous to how BuddyPress injects its content into a theme. After I have
   * refactored the plugin, I will look into this more thoroughly.
   */
  public function set_post_blank() {

    global $post;

    // kick out when there's no post object, eg on 404 pages
    if ( ! is_object( $post ) ) return;

    /*
    CMW: the following is a proper no-no and affects much more than "posted on".

    Not only does it not do what CiviCRM hopes it will, it throws:
    Notice: Trying to get property of non-object in wp-includes/link-template.php on line 936
    in most themes.
    */
    // to hide posted on
    $post->post_type = '';

    /*
    CMW: depending on the theme, clearing the post title here may not be effective.

    Indeed, in the case of shortcodes (which are only parsed when the template has already
    written out the markup for the post title) this absolutely cannot work.

    What needs to be done is that the templating process needs to be intercepted and an
    alternative dummy post needs to be created, which entirely replaces the existing post
    object. BuddyPress has absctracted this, so it shouldn't be too problematic to copy
    the logic it deploys.

    I'm beginning to wonder why CiviCRM is even trying to take over the post/page...

    If CiviCRM wants to offer pages that are Civi-specific, surely a Custom Post Type
    would be a better option? There could then be CPTs for each "type" of CiviCRM content,
    perhaps even auto-generated by CiviCRM through day-to-day use of the system.
    */

    // to hide post title
    $post->post_title = '';

    // CMW: why is the author's edit post link cleared?
    // hide the edit link
    add_action( 'edit_post_link', array( $this, 'set_blank' ) );

  }


  /**
   * @description: callback from 'edit_post_link' hook to remove edit link in set_post_blank()
   * @return string always empty
   */
  public function set_blank() {
    return '';
  }


  /**
   * @description: authentication function used by wp_frontend()
   * @return bool true if authenticated, false otherwise
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
    $validPaths = array('profile', 'user', 'dashboard', 'friend', 'file', 'ajax');
    if ( in_array( $arg1, $validPaths ) ) {
      return TRUE;
    }

    $arg2 = CRM_Utils_Array::value(2, $args);
    $arg3 = CRM_Utils_Array::value(3, $args);

    // allow editing of related contacts
    if (
      $arg1 == 'contact' &&
      $arg2 == 'relatedcontact'
    ) {
      return TRUE;
    }

    // a contribution page
    if ( in_array( 'CiviContribute', $config->enableComponents ) ) {

      if (
        $arg1 == 'contribute' &&
        in_array( $arg2, array('transact', 'campaign', 'pcp', 'updaterecur', 'updatebilling', 'unsubscribe') )
      ) {
        return TRUE;
      }

      if (
        $arg1 == 'pcp' &&
        ( !$arg2 || in_array( $arg2, array('info') ) )
      ) {
        return TRUE;
      }

    }

    // an event registration page is valid
    if ( in_array( 'CiviEvent', $config->enableComponents ) ) {

      if (
        $arg1 == 'event' &&
        in_array( $arg2, array('register', 'info', 'participant', 'ical', 'confirm') )
      ) {
        return TRUE;
      }

      // also allow events to be mapped
      if (
        $arg1 == 'contact' &&
        $arg2 == 'map' &&
        $arg3 == 'event'
      ) {
        return TRUE;
      }

      if (
        $arg1 == 'pcp' &&
        ( !$arg2 || in_array( $arg2, array('info') ) )
      ) {
        return TRUE;
      }

    }

    // allow mailing urls to be processed
    if (
      $arg1 == 'mailing' &&
      in_array( 'CiviMail', $config->enableComponents )
    ) {
      if (
        in_array(
          $arg2,
          array('forward', 'unsubscribe', 'resubscribe', 'optout', 'subscribe', 'confirm', 'view')
        )
      ) {
        return TRUE;
      }
    }

    // allow petition sign in, CRM-7401
    if ( in_array( 'CiviCampaign', $config->enableComponents ) ) {
      $validPaths = array('sign', 'thankyou', 'confirm');
      if (
        $arg1 == 'petition' &&
        in_array($arg2, $validPaths)
      ) {
        return TRUE;
      }
    }

    return FALSE;

  }


  /**
   * @description: called when authentication fails in wp_frontend()
   * @return string warning message
   */
  public function show_permission_denied() {
    return __( 'You do not have permission to execute this url.', 'civicrm-wordpress' );
  }


  /**
   * @description: only called by invoke() to undo WordPress default behaviour
   * CMW: Should probably be a private method
   */
  public function remove_wp_magic_quotes() {

    // reassign globals
    $_GET     = stripslashes_deep($_GET);
    $_POST    = stripslashes_deep($_POST);
    $_COOKIE  = stripslashes_deep($_COOKIE);
    $_REQUEST = stripslashes_deep($_REQUEST);

  }


  /**
   * @description: keep WordPress user synced with CiviCRM Contact
   * Callback function for 'user_register' hook
   * Callback function for 'profile_update' hook
   *
   * CMW: seems to (wrongly) create new CiviCRM Contact every time a user changes their
   * first_name or last_name attributes in WordPress.
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
   * @description: function to create 'anonymous_user' role, if 'anonymous_user' role is not
   * in the WordPress installation and assign minimum capabilities for all WordPress roles
   *
   * The legacy global scope function civicrm_wp_set_capabilities() is called from
   * upgrade_4_3_alpha1()
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
        __( 'Anonymous User', 'civicrm-wordpress' ),
        $min_capabilities
      );
    }

  }


  /**
   * @description: add CiviCRM access capabilities to WordPress roles
   * this is a callback for the 'init' hook in register_hooks()
   *
   * The legacy global scope function wp_civicrm_capability() is called by
   * postProcess() in civicrm/CRM/ACL/Form/WordPress/Permissions.php
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
   * @description: get CiviCRM contact type
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


  /**
   * @description: handles CiviCRM-defined shortcodes
   * @return string HTML for output
   */
  public function shortcode_handler( $atts ) {

    extract( shortcode_atts( array(
      'component' => 'contribution',
      'action' => NULL,
      'mode' => NULL,
      'id' => NULL,
      'cid' => NULL,
      'gid' => NULL,
      'cs' => NULL,
      ),
      $atts
    ) );

    $args = array(
      'reset' => 1,
      'id'    => $id,
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
            break;

          default:
            echo '<p>' . __( 'Do not know how to handle this shortcode', 'civicrm-wordpress' ) . '</p>';
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

        echo '<p>' . __( 'Do not know how to handle this shortcode', 'civicrm-wordpress' ) . '</p>';
        return;

    }

    foreach ( $args as $key => $value ) {
      if ( $value !== NULL ) {
        $_REQUEST[$key] = $_GET[$key] = $value;
      }
    }

    // call wp_frontend with $shortcode param
    return $this->wp_frontend( TRUE );

  }


  /**
   * @description: callback method for 'media_buttons_context' hook as set in register_hooks()
   * @return string HTML for output or empty if CiviCRM not initialized
   */
  public function add_form_button( $context ) {

    // get screen object
    $screen = get_current_screen();

    // only add on default WP post types
    if ( $screen->post_type == 'post' OR $screen->post_type == 'page' ) {

      if ( ! $this->initialize() ) {
        return '';
      }

      $config      = CRM_Core_Config::singleton();
      $imageBtnURL = $config->resourceBase . 'i/logo16px.png';
      $out         = '<a href="#TB_inline?width=480&inlineId=civicrm_frontend_pages" class="button thickbox" id="add_civi" style="padding-left: 4px;" title="' . __( 'Add CiviCRM Public Pages', 'civicrm-wordpress' ) . '"><img src="' . $imageBtnURL . '" height="15" width="15" alt="' . __( 'Add CiviCRM Public Pages', 'civicrm-wordpress' ) . '" />'. __( 'CiviCRM', 'civicrm-wordpress' ) .'</a>';
      return $context . $out;

    }

  }


  /**
   * @description: callback method for 'admin_enqueue_scripts' hook as set in register_hooks()
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
   * @description: callback method for 'admin_footer' hook as set in register_hooks()
   */
  public function add_form_button_html() {

    // get screen object
    $screen = get_current_screen();

    // only add on edit page for default WP post types
    if (
      $screen->base == 'post' AND
      ( $screen->id == 'post' OR $screen->id == 'page' ) AND
      ( $screen->post_type == 'post' OR $screen->post_type == 'page' )
    ) {

      $title = __( 'Please select a CiviCRM front-end page type.', 'civicrm-wordpress' );
      ?>
      <div id="civicrm_frontend_pages" style="display:none;">
        <div class="wrap">
          <div>
            <div style="padding:15px 15px 0 15px;">
              <h3 style="color:#5A5A5A!important; font-family:Georgia,Times New Roman,Times,serif!important; font-size:1.8em!important; font-weight:normal!important;">
              <?php echo $title; ?>
              </h3>
              <span>
                <?php echo $title; ?>
              </span>
            </div>
            <div style="padding:15px 15px 0 15px;">
              <select id="add_civicomponent_id">
                <option value=""><?php _e( 'Select a frontend element.', 'civicrm-wordpress' ); ?></option>
                <option value="contribution"><?php _e( 'Contribution Page', 'civicrm-wordpress' ); ?></option>
                <option value="event"><?php _e( 'Event Page', 'civicrm-wordpress' ); ?></option>
                <option value="profile"><?php _e( 'Profile', 'civicrm-wordpress' ); ?></option>
                <option value="user-dashboard"><?php _e( 'User Dashboard', 'civicrm-wordpress' ); ?></option>
                <option value="petition"><?php _e( 'Petition', 'civicrm-wordpress' ); ?></option>
              </select>

               <span id="contribution-section" style="display:none;">
                <select id="add_contributepage_id">
                <?php
                  $contributionPages = $this->get_contribution_pages();
                  foreach ($contributionPages as $key => $value) { ?>
                  <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
                  <?php
                  }
                  ?>
                </select>
              </span>

              <span id="event-section" style="display:none;">
                <select id="add_eventpage_id">
                <?php
                  $eventPages = $this->get_event();
                  foreach ($eventPages as $key => $value) { ?>
                  <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
                  <?php
                  }
                  ?>
                </select>
              </span>
              <br>
              <span id="action-section-event" style="display:none;">
                 <div style="padding:15px 15px 0 15px;">
                <input type="radio" name="event_action" value="info" checked="checked" /> <?php _e( 'Event Info Page', 'civicrm-wordpress' ); ?>
                <input type="radio" name="event_action" value="register" /> <?php _e( 'Event Registration Page', 'civicrm-wordpress' ); ?>
                 </div>
              </span>
              <br/>
              <span id="component-section" style="display:none;">
                 <div style="padding:15px 15px 0 15px;">
                <input type="radio" name="component_mode" value="live" checked="checked"/> <?php _e( 'Live Page', 'civicrm-wordpress' ); ?>
                <input type="radio" name="component_mode" value="test" /> <?php _e( 'Test Drive', 'civicrm-wordpress' ); ?>
                 </div>
              </span>
              <br/>

              <span id="profile-section" style="display:none;">
                 <select id="add_profilepage_id">
                 <?php
                 $profilePages = $this->get_profile_page();
                 foreach ($profilePages as $key => $value) { ?>
                   <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
                   <?php
                }
                ?>
                 </select>
              </span>
              <br/>

              <span id="profile-mode-section" style="display:none;">
                 <div style="padding:15px 15px 0 15px;">
                <input type="radio" name="profile_mode" value="create" checked="checked"/> <?php _e( 'Create', 'civicrm-wordpress' ); ?>
                <input type="radio" name="profile_mode" value="edit" /> <?php _e( 'Edit', 'civicrm-wordpress' ); ?>
                <input type="radio" name="profile_mode" value="edit" /> <?php _e( 'View', 'civicrm-wordpress' ); ?>
                 </div>
              </span>

              <span id="petition-section" style="display:none;">
		            <select id="add_petition_id">
                <?php
                $petitionPages = $this->get_petition();
                foreach ($petitionPages as $key => $value) { ?>
                  <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
                <?php
                }
                ?>
                </select>
              </span>

              <div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A"><?php _e( "Can't find your form? Make sure it is active.", 'civicrm-wordpress' ); ?></div>
            </div>
            <div style="padding:15px;">
              <input type="button" class="button-primary" value="Insert Form" id="crm-wp-insert-shortcode"/>&nbsp;&nbsp;&nbsp;
              <a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;"><?php _e( 'Cancel', 'civicrm-wordpress' ); ?></a>
            </div>
          </div>
        </div>
      </div>

    <?php

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
  return civi_wp()->set_blank();
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
  return civi_wp()->show_permission_denied();
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
 * FIXME: This is probably a hack to work around some drupal-centric issue. This function should be removed.
 */
function t( $str, $sub = NULL ) {
  if ( is_array( $sub ) ) {
    $str = str_replace( array_keys( $sub ), array_values( $sub ), $str );
  }
  return $str;
}
