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



// set version here: when it changes, will force JS to reload
define( 'CIVICRM_PLUGIN_VERSION', '4.3' );

// define commonly used items as constants
define( 'CIVICRM_PLUGIN_DIR', plugin_dir_path(__FILE__) );
define( 'CIVICRM_SETTINGS_PATH', CIVICRM_PLUGIN_DIR.'civicrm.settings.php' );

// prevent CiviCRM from rendering its own header
define( 'CIVICRM_UF_HEAD', true );



/*
--------------------------------------------------------------------------------
CiviCrmForWordPress Class
--------------------------------------------------------------------------------
*/

class CiviCRM_For_WordPress {



	/** 
	 * declare our properties
	 */
	
	// plugin context
	static $in_wordpress;
	
	
	
	/** 
	 * @description: initialises this object
	 * @return object
	 */
	function __construct() {
	
		// kick out if another instance is being inited
		if ( isset( $this->in_wordpress ) ) { 
			wp_die( 'Only one instance of CiviCRM_For_WordPress please' );
		}
	
		// store context
		$this->in_wordpress = ( isset( $_GET['page'] ) && $_GET['page'] == 'CiviCRM' ) ? true : false;
		
		// there is no session handling in WP hence we start it for CiviCRM pages
		if (!session_id()) {
			session_start();
		}

		// this is required for ajax calls in civicrm
		if ( $this->civicrm_in_wordpress() ) {
			$_GET['noheader'] = true;
		} else {
			$_GET['civicrm_install_type'] = 'wordpress';
		}
		
		// same procedure as civicrm_wp_main()
		$this->register_hooks();
		
		// --<
		return $this;

	}



	/**
	 * @description: method that runs only when CiviCRM plugin is activated
	 */
	function activate() {

		// Assign minimum capabilities for all WordPress roles and create 'anonymous_user' role
		$this->set_wp_user_capabilities();

	}



	/**
	 * @description: getter for testing if CiviCRM is currently being displayed in WordPress
	 * @return bool $in_wordpress
	 */
	public function civicrm_in_wordpress() {
		
		// already stored
		return $this->in_wordpress;

	}



	/** 
	 * @description: register hooks (same procedure as civicrm_wp_main() )
	 */
	public function register_hooks() {
	
		// always add the following hooks
		
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

			// Adding "embed form" button
			// CMW: better to use get_current_screen()
			// see http://codex.wordpress.org/Function_Reference/get_current_screen
			if (in_array(
			
				basename($_SERVER['PHP_SELF']),
				array('post.php', 'page.php', 'page-new.php', 'post-new.php')
				
			)) {
				
				// CMW: the check above does not allow Custom Post Types to make
				// use of the CiviCRM shortcode...

				// adds the CiviCRM button to post and page screens
				add_action( 'media_buttons_context', array( $this, 'add_form_button' ) );
				
				// adds the HTML triggered by the button above
				add_action( 'admin_footer', array( $this, 'add_form_button_html' ) );
				
				// add the javascript to make it all happen
				add_action( 'admin_enqueue_scripts', array( $this, 'add_form_button_js' ) );
				
			}

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
				'CiviCRM', 
				'CiviCRM', 
				'access_civicrm', 
				'CiviCRM', 
				array( $this, 'invoke' ), 
				$civilogo 
			);
		
		} else {
			
			// add menu item to options menu
			add_options_page( 
				'CiviCRM Installer', 
				'CiviCRM Installer', 
				'manage_options', 
				'civicrm-install', 
				array( $this, 'run_installer' )
			);

		}

	}



	/**
	 * @description: invoke CiviCRM in a WordPress context
	 * Callback function from add_menu_page() 
	 * Callback from WordPress 'init' and 'the_content' hooks
	 * Also used by civicrm_wp_shortcode_includes() and _civicrm_update_user()
	 */
	function invoke() {

		static $alreadyInvoked = false;
		if ( $alreadyInvoked ) {
			return;
		}

		$alreadyInvoked = true;
		if ( ! $this->initialize() ) {
			return '';
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
			CRM_Core_BAO_UFMatch::synchronize( $current_user, false, 'WordPress', 'Individual', true );
		}

		CRM_Core_Invoke::invoke($args);

	}



	/**
	 * @description: callback function for add_options_page() that runs the CiviCRM installer
	 */
	function run_installer() {

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
			 t('CiviCRM is almost ready.') . 
			 '</strong> ' . 
			 t(
			 	'You must <a href="!1">configure CiviCRM</a> for it to work.', 
			 	array( '!1' => $installLink )
			 ) .
			 '</p></div>';

	}



	/**
	 * @description: initialize CiviCRM
	 * @return bool $success
	 */
	public function initialize() {

		static $initialized = false;
		static $failure = false;

		if ( $failure ) {
			return false;
		}

		if ( ! $initialized ) {

			// Check for php version and ensure its greater than minPhpVersion
			$minPhpVersion = '5.3.3';
			if ( version_compare( PHP_VERSION, $minPhpVersion ) < 0 ) {
				echo "CiviCRM requires PHP Version $minPhpVersion or greater. You are running PHP Version " . PHP_VERSION . "<p>";
				exit();
			}

			if ( ! file_exists(CIVICRM_SETTINGS_PATH ) ) {
				$error = false;
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

			$errorMsgAdd = t(
				"Please review the <a href='!1'>WordPress Installation Guide</a> and the <a href='!2'>Trouble-shooting page</a> for assistance. If you still need help installing, you can often find solutions to your issue by searching for the error message in the <a href='!3'>installation support section of the community forum</a>.</strong></p>",
				array('!1' => $docLinkInstall, '!2' => $docLinkTrouble, '!3' => $forumLink)
			);

			$installMessage = t(
				"Click <a href='!1'>here</a> for fresh install.", 
				array('!1' => $installLink)
			);

			if ($error == false) {
				header( 'Location: ' . admin_url() . 'options-general.php?page=civicrm-install' );
				return false;
			}

			// this does pretty much all of the civicrm initialization
			if ( ! file_exists( $civicrm_root . 'CRM/Core/Config.php' ) ) {
				$error = false;
			} else {
				$error = include_once ( 'CRM/Core/Config.php' );
			}

			if ( $error == false ) {
				$failure = true;
				//FIX ME
				wp_die(			
					"<strong><p class='error'>" .
					t(
						"Oops! - The path for including CiviCRM code files is not set properly. Most likely there is an error in the <em>civicrm_root</em> setting in your CiviCRM settings file (!1).",
						array('!1' => CIVICRM_SETTINGS_PATH)
					) .
					"</p><p class='error'> &raquo; " .
					t(
						"civicrm_root is currently set to: <em>!1</em>.", 
						array('!1' => $civicrm_root)
					) .
					"</p><p class='error'>" . $errorMsgAdd . "</p></strong>"
				);
				return false;
			}

			$initialized = true;

			// initialize the system by creating a config object
			$config = CRM_Core_Config::singleton();

			// sync the logged in user with WP
			global $current_user;
			if ( $current_user ) {
				require_once 'CRM/Core/BAO/UFMatch.php';
				CRM_Core_BAO_UFMatch::synchronize(
					$current_user,
					false,
					'WordPress',
					$this->get_civicrm_contact_type('Individual')
				);
			}

		}

		return true;

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

		$region = CRM_Core_Region::instance('html-header', false);
		if ( $region ) {
			echo $region->render( '' );
		}

	}



	/**
	 * @description: callback function for 'get_header' hook
	 */
	function add_shortcode_includes() {

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
	 * @description: Callback for ob_start() in civicrm_buffer_start()
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
	public function wp_frontend( $shortcode = false ) {

		if ( ! $this->initialize() ) {
			return;
		}

		CRM_Core_Resources::singleton()->addCoreResources();

		// set the frontend part for civicrm code
		$config = CRM_Core_Config::singleton();
		$config->userFrameworkFrontend = true;

		if ( isset( $_GET['q'] ) ) {
			$args = explode( '/', trim( $_GET['q'] ) );
		}
		
		// CMW: hacky procedure for overriding WordPress page/post:
		// see comments on set_post_blank()
		if ( $shortcode ) {
			$this->turn_comments_off();
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

		// if snippet is set, which means ajax call, we just
		// output civicrm html and skip the header
		if (
			CRM_Utils_Array::value('snippet', $_GET) ||
			(
			CRM_Utils_Array::value(0, $args) == 'civicrm' &&
			CRM_Utils_Array::value(1, $args) == 'ajax'
			)  ||
			// we also follow this pattern where civicrm controls the
			// entire page like an ical feed
			(
			CRM_Utils_Array::value(0, $args) == 'civicrm' &&
			CRM_Utils_Array::value(1, $args) == 'event' &&
			CRM_Utils_Array::value(2, $args) == 'ical' &&
			// skip the html page since that is rendered in the CMS theme
			CRM_Utils_Array::value('html', $_GET) != 1
			)
		) {
			add_filter( 'init', array( $this, 'invoke' ) );
			return;
		}

		// this places civicrm inside frontend theme
		// wp documentation rocks if you know what you are looking for
		// but best way is to check other plugin implementation :)
		if ( $shortcode ) {
			
			// CMW: review in the light of proper shortcode research
			ob_start(); // start buffering
			$this->invoke(); // now, instead of echoing, shortcode output ends up in buffer
			$content = ob_get_clean(); // save the output and flush the buffer
			return $content;
			
		} else {
			
			// see comments on set_post_blank()
			add_filter('the_content', array( $this, 'invoke' ) );
			
		}

	}



	/**
	 * @description: override WordPress post comment status attribute in civicrm_wp_frontend()
	 * see comments on set_post_blank()
	 */
	function turn_comments_off() {

		global $post;
		
		// kick out when there's no post object, eg on 404 pages
		if ( ! is_object( $post ) ) return;

		$post->comment_status = 'closed';

	}



	/**
	 * @description: override WordPress post attributes in civicrm_wp_frontend()
	 *
	 * CMW: the process of overriding WordPress post content should be done in a way
	 * analogous to how BuddyPress injects its content into a theme. After I have
	 * refactored the plugin, I will look into this more thoroughly.
	 */
	function set_post_blank() {

		global $post;
		cividie( $post );
		
		// kick out when there's no post object, eg on 404 pages
		if ( ! is_object( $post ) ) return;

		// to hide posted on
		// CMW: this is a big no-no and affects much more than "posted on"
		$post->post_type = '';

		// to hide post title
		// CMW: depending on the theme, this may or may not be effective.
		// perhaps better to set the post title to the CiviCRM entity title?
		$post->post_title = '';

		// hide the edit link
		add_action( 'edit_post_link', array( $this, 'set_blank' ) );

	}



	/**
	 * @description: callback from 'edit_post_link' hook to remove edit link in civicrm_set_post_blank()
	 * @return string always empty
	 */
	function set_blank() {
		return '';
	}



	/**
	 * @description: authentication function used by wp_frontend()
	 * @return bool true if authenticated, false otherwise
	 */
	function check_permission( $args ) {

		if ( $args[0] != 'civicrm' ) {
			return false;
		}

		$config = CRM_Core_Config::singleton();

		// set frontend true
		$config->userFrameworkFrontend = true;

		require_once 'CRM/Utils/Array.php';
		// all profile and file urls, as well as user dashboard and tell-a-friend are valid
		$arg1 = CRM_Utils_Array::value(1, $args);
		$validPaths = array('profile', 'user', 'dashboard', 'friend', 'file', 'ajax');
		if ( in_array( $arg1, $validPaths ) ) {
			return true;
		}

		$arg2 = CRM_Utils_Array::value(2, $args);
		$arg3 = CRM_Utils_Array::value(3, $args);

		// allow editing of related contacts
		if (
			$arg1 == 'contact' && 
			$arg2 == 'relatedcontact'
		) {
			return true;
		}

		// a contribution page
		if ( in_array( 'CiviContribute', $config->enableComponents ) ) {

			if (
				$arg1 == 'contribute' &&
				in_array($arg2, array('transact', 'campaign', 'pcp'))
			) {
				return true;
			}

			if (
				$arg1 == 'pcp' &&
				in_array( $arg2, array('info') )
			) {
				return true;
			}
			
		}

		// an event registration page is valid
		if ( in_array( 'CiviEvent', $config->enableComponents ) ) {

			if (
				$arg1 == 'event' &&
				in_array( $arg2, array('register', 'info', 'participant', 'ical', 'confirm') )
			) {
				return true;
			}

			// also allow events to be mapped
			if (
				$arg1 == 'contact' &&
				$arg2 == 'map' &&
				$arg3 == 'event'
			) {
				return true;
			}

			if (
				$arg1 == 'pcp' &&
				in_array( $arg2, array('info') )
			) {
				return true;
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
				return true;
			}
		}

		// allow petition sign in, CRM-7401
		if ( in_array( 'CiviCampaign', $config->enableComponents ) ) {
			if (
				$arg1 == 'petition' &&
				$arg2 == 'sign'
			) {
				return true;
			}
		}

		return false;

	}



	/**
	 * @description: called when authentication fails in civicrm_wp_frontend()
	 * @return string warning message
	 */
	function show_permission_denied() {
		return ts( 'You do not have permission to execute this url.' );
	}



	/**
	 * @description: only called by civicrm_wp_invoke() to undo WordPress default behaviour
	 */
	function remove_wp_magic_quotes() {
	
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
	 * first_name or last_name attributes
	 */
	public function update_user( $userID ) {

		$user = get_userdata( $userID );
		if ( $user ) {

			if (!$this->initialize()) {
				return;
			}

			require_once 'CRM/Core/BAO/UFMatch.php';
			CRM_Core_BAO_UFMatch::synchronize(
				$user,
				true,
				'WordPress',
				'Individual'
			);

		}

	}



	/**
	 * @description: function to create 'anonymous_user' role, if 'anonymous_user' role is not 
	 * in the WordPress installation and assign minimum capabilities for all WordPress roles
	 * 
	 * The function in global scope is called on plugin activation and also from upgrade_4_3_alpha1()
	 */
	function set_wp_user_capabilities() {

		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		// Minimum capabilities (Civicrm permissions) arrays
		$min_capabilities =  array(
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
				'Anonymous User',
				$min_capabilities
			);
		}

	}



	/**
	 * @description: add CiviCRM access capabilities to WordPress roles
	 * this is a callback for the 'init' hook in register_hooks()
	 *
	 * The global scope function is called by postProcess() in 
	 * civicrm/CRM/ACL/Form/WordPress/Permissions.php
	 */
	public function set_access_capabilities() {
		
		// test for existing global
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		// give access to civicrm page menu link to particular roles
		$roles = array( 'super admin', 'administrator' );
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
	function get_civicrm_contact_type( $default = NULL ) {

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
	function shortcode_handler( $atts ) {

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
						echo '<p>Do not know how to handle this shortcode</p>';
						return;
				}
				break;

			case 'user-dashboard':

				$args['q'] = 'civicrm/user';
				unset( $args['id'] );
				break;

			case 'profile':

				if ( $mode == 'edit' ) {
					$args['q'] = 'civicrm/profile/edit';
				} elseif ( $mode == 'view' ) {
					$args['q'] = 'civicrm/profile/view';
				} else {
					$args['q'] = 'civicrm/profile/create';
				}
				$args['gid'] = $gid;
				break;

			default:

				echo '<p>Do not know how to handle this shortcode</p>';
				return;

		}

		foreach ( $args as $key => $value ) {
			if ( $value !== NULL ) {
				$_REQUEST[$key] = $_GET[$key] = $value;
			}
		}
		
		// --<
		return $this->wp_frontend( true );

	}



	/**
	 * @description: callback method for 'media_buttons_context' hook as set in register_hooks()
	 * @return string HTML for output or empty if CiviCRM not initialized
	 */
	public function add_form_button( $context ) {

		if ( ! $this->initialize() ) {
			return '';
		}

		$config      = CRM_Core_Config::singleton();
		$imageBtnURL = $config->resourceBase . 'i/logo16px.png';
		$out         = '<a href="#TB_inline?width=480&inlineId=civicrm_frontend_pages" class="button thickbox" id="add_civi" style="padding-left: 4px;" title="' . __("Add CiviCRM Public Pages", 'CiviCRM') . '"><img src="' . $imageBtnURL . '" height="15" width="15" alt="' . __("Add CiviCRM Public Pages", 'CiviCRM') . '" />CiviCRM</a>';
		return $context . $out;

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
		$in_footer = true;
		
		// construct path to file
		$src = plugins_url(
			'civicrm.js',
			__FILE__
		);

		wp_enqueue_script( 
			'civicrm_form_button_js', // handle
			$src, // src
			array( 'jquery' ), // deps
			CIVICRM_PLUGIN_VERSION, 
			$in_footer
		);

	}



	/**
	 * @description: callback method for 'admin_footer' hook as set in register_hooks()
	 */
	public function add_form_button_html() {

		$title = ts( 'Please select a CiviCRM front-end page type.' );

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

		$dao = CRM_Core_DAO::executeQuery( $sql );
		$contributionPages = array();
		while ( $dao->fetch() ) {
			$contributionPages[$dao->id] = $dao->title;
		}

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

		$dao = CRM_Core_DAO::executeQuery( $sql );
		$eventPages = array();
		while ( $dao->fetch() ) {
			$eventPages[$dao->id] = $dao->title;
		}


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

		$dao = CRM_Core_DAO::executeQuery( $sql );
		$profilePages = array();
		while ( $dao->fetch() ) {
			$profilePages[$dao->id] = $dao->title;
		}

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
							<option value="">  <?php echo ts("Select a frontend element."); ?>  </option>
							<option value="contribution">Contribution Page</option>
							<option value="event">Event Page</option>
							<option value="profile">Profile</option>
							<option value="user-dashboard">User Dashboard</option>
						</select>

						 <span id="contribution-section" style="display:none;">
							<select id="add_contributepage_id">
							<?php
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
							<input type="radio" name="event_action" value="info" checked="checked" /> Event Info Page
							<input type="radio" name="event_action" value="register" /> Event Registration Page
						   </div>
						</span>
						<br/>
						<span id="component-section" style="display:none;">
						   <div style="padding:15px 15px 0 15px;">
							<input type="radio" name="component_mode" value="live" checked="checked"/> Live Page
							<input type="radio" name="component_mode" value="test" /> Test Drive
						   </div>
						</span>
						<br/>

						<span id="profile-section" style="display:none;">
						   <select id="add_profilepage_id">
						   <?php
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
							<input type="radio" name="profile_mode" value="create" checked="checked"/> Create
							<input type="radio" name="profile_mode" value="edit" /> Edit
							<input type="radio" name="profile_mode" value="edit" /> View
						   </div>
						</span>

						<div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A"><?php echo ts("Can't find your form? Make sure it is active."); ?></div>
					</div>
					<div style="padding:15px;">
					  <input type="button" class="button-primary" value="Insert Form" id="crm-wp-insert-shortcode"/>&nbsp;&nbsp;&nbsp;
					  <a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;"><?php echo ts("Cancel"); ?></a>
					</div>
				</div>
			</div>
		</div>

	<?php
	}



} // class ends



/*
--------------------------------------------------------------------------------
Procedures start here
--------------------------------------------------------------------------------
*/


// stash our object in a global for now...
global $civicrm_for_wordpress;

// eventually, we'll stash it in an instance in the CiviCRM_For_WordPress class
$civicrm_for_wordpress = new CiviCRM_For_WordPress;

// tell WordPress to call plugin activation method
register_activation_hook( __FILE__, 'civicrm_activate' );



/*
--------------------------------------------------------------------------------
The global scope functions below are to maintain backwards compatibility with
previous versions of the CiviCRM WordPress plugin.
--------------------------------------------------------------------------------
*/


/**
 * register the plugin's WordPress hooks
 */
function civicrm_wp_main() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->register_hooks();
}

/**
 * add CiviCRM access capabilities to WordPress roles
 * Called by postProcess() in civicrm/CRM/ACL/Form/WordPress/Permissions.php
 * Also a callback for the 'init' hook in civicrm_wp_main()
 */
function wp_civicrm_capability() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->set_access_capabilities();
}

/**
 * Callback method for 'admin_menu' hook as set in civicrm_wp_main()
 * Adds menu items to WordPress admin menu
 */
function civicrm_wp_add_menu_items() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->add_menu_items();
}

/**
 * callback method for 'media_buttons_context' hook as set in civicrm_wp_main()
 */
function civicrm_add_form_button( $context ) {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->add_form_button( $context );
}

/**
 * Callback method for 'admin_footer' hook as set in civicrm_wp_main()
 */
function civicrm_add_form_button_html() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->add_form_button_html();
}

/**
 * Callback function for missing settings file in civicrm_wp_main()
 */
function civicrm_setup_warning() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->show_setup_warning();
}

/**
 * merge CiviCRM's HTML header with the WordPress theme's header
 * Callback from WordPress 'admin_head' and 'wp_head' hooks
 */
function civicrm_wp_head() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->wp_head();
}

/**
 * Callback function for 'user_register' hook
 */
function civicrm_user_register( $userID ) {
	_civicrm_update_user( $userID );
}

/**
 * Callback function for 'profile_update' hook
 */
function civicrm_profile_update( $userID ) {
	_civicrm_update_user( $userID );
}

/**
 * Common function for user create/update hooks above
 * Seems to (wrongly) create new CiviCRM Contact every time a user changes their
 * first_name or last_name attributes
 */
function _civicrm_update_user( $userID ) {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->update_user( $userID );
}

/**
 * Callback function for 'get_header' hook
 */
function civicrm_wp_shortcode_includes() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->add_shortcode_includes();
}

/**
 * Test if CiviCRM is currently being displayed in WordPress
 * Called by setTitle() in civicrm/CRM/Utils/System/WordPress.php
 * Also called at the top of this plugin file to determine AJAX status
 */
function civicrm_wp_in_civicrm() {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->civicrm_in_wordpress();
}

/**
 * Start buffering, called in civicrm_wp_main()
 */
function civicrm_buffer_start() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->buffer_start();
}

/**
 * Flush buffer, callback for 'wp_footer'
 */
function civicrm_buffer_end() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->buffer_end();
}

/**
 * Callback for ob_start() in civicrm_buffer_start()
 */
function civicrm_buffer_callback($buffer) {
  // moved to class, given that it's never called directly
}

/**
 * CiviCRM's theme integration method
 * Called by civicrm_wp_main() and civicrm_shortcode_handler()
 */
function civicrm_wp_frontend( $shortcode = false ) {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->wp_frontend( $shortcode );
}

/**
 * This was the original name of the initialization function and is
 * retained for backward compatibility
 */
function civicrm_wp_initialize() {
  return civicrm_initialize();
}

/**
 * Initialize CiviCRM. Call this function from other modules too if
 * they use the CiviCRM API.
 */
function civicrm_initialize() {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->initialize();
}

/**
 * Override WordPress post comment status attribute in civicrm_wp_frontend()
 */
function civicrm_turn_comments_off() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->turn_comments_off();
}

/**
 * Override WordPress post attributes in civicrm_wp_frontend()
 */
function civicrm_set_post_blank() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->set_post_blank();
}

/**
 * Callback from 'edit_post_link' hook to remove edit link in civicrm_set_post_blank()
 */
function civicrm_set_blank() {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->set_blank();
}

/**
 * Authentication function used by civicrm_wp_frontend()
 */
function civicrm_check_permission( $args ) {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->check_permission( $args );
}

/**
 * Called when authentication fails in civicrm_wp_frontend()
 */
function civicrm_set_frontendmessage() {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->show_permission_denied();
}

/**
 * Invoke CiviCRM in a WordPress context
 * Callback function from add_menu_page() 
 * Callback from WordPress 'init' and 'the_content' hooks
 * Also used by civicrm_wp_shortcode_includes() and _civicrm_update_user()
 */
function civicrm_wp_invoke() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->invoke();
}

/**
 * Only called by civicrm_wp_invoke() to undo WordPress default behaviour
 */
function civicrm_remove_wp_magic_quotes() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->remove_wp_magic_quotes();
}

/**
 * Method that runs only when civicrm plugin is activated.
 */
function civicrm_activate() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->activate();
}

/**
 * Function to create anonymous_user' role, if 'anonymous_user' role is not in the wordpress installation
 * and assign minimum capabilities for all wordpress roles
 * This function is called on plugin activation and also from upgrade_4_3_alpha1()
 */
function civicrm_wp_set_capabilities() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->set_wp_user_capabilities();
}

/**
 * Callback function for add_options_page() that runs the CiviCRM installer
 */
function civicrm_run_installer() {
	global $civicrm_for_wordpress;
	$civicrm_for_wordpress->run_installer();
}

/**
 * Function to get the contact type
 * @param string $default contact type
 * @return $ctype contact type
 */
function civicrm_get_ctype( $default = NULL ) {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->get_civicrm_contact_type( $default );
}

/**
 * Handles CiviCRM-defined shortcodes, but probably never called directly
 */
function civicrm_shortcode_handler( $atts ) {
	global $civicrm_for_wordpress;
	return $civicrm_for_wordpress->shortcode_handler( $atts );
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
 * String replacement function similar to sprintf
 */
function t( $str, $sub = NULL ) {
  if ( is_array( $sub ) ) {
    $str = str_replace( array_keys( $sub ), array_values( $sub ), $str );
  }
  return $str;
}

/**
 * utility for debugging
 */
function cividie( $var ) {
	print_r( $var ); die();
}




