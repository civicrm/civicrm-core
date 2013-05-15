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

define('CIVICRM_UF_HEAD', TRUE);

// there is no session handling in WP hence we start it for CiviCRM pages
if (!session_id()) {
  session_start();
}

//this is require for ajax calls in civicrm
if (civicrm_wp_in_civicrm()) {
  $_GET['noheader'] = TRUE;
}
else {
  $_GET['civicrm_install_type'] = 'wordpress';
}

/**
 * Method that runs only when civicrm plugin is activated.
 */
register_activation_hook( __FILE__, 'civicrm_activate');

function civicrm_activate() {
  // Assign minimum capabilities for all wordpress roles and create anonymous_user' role
  civicrm_wp_set_capabilities();
}

/*
 * Function to create anonymous_user' role, if 'anonymous_user' role is not in the wordpress installation
 * and assign minimum capabilities for all wordpress roles
 */
function civicrm_wp_set_capabilities() {
  global $wp_roles;
  if (!isset($wp_roles)) {
    $wp_roles = new WP_Roles();
  }

  //Minimum capabilities (Civicrm permissions) arrays
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
    $roleObj = $wp_roles->get_role($role);
    foreach ($min_capabilities as $capability_name => $capability_value) {
      $roleObj->add_cap($capability_name);
    }
  }

  //Add the 'anonymous_user' role with minimum capabilities.
  if (!in_array('anonymous_user' , $wp_roles->roles)) {
    add_role(
      'anonymous_user',
      'Anonymous User',
      $min_capabilities
    );
  }
}

function civicrm_wp_add_menu_items() {
  $settingsFile =
    WP_PLUGIN_DIR . DIRECTORY_SEPARATOR .
    'civicrm'     . DIRECTORY_SEPARATOR .
    'civicrm.settings.php';

  if (file_exists($settingsFile)) {
    $civilogo =
      plugins_url() . DIRECTORY_SEPARATOR .
      'civicrm'     . DIRECTORY_SEPARATOR .
      'civicrm'     . DIRECTORY_SEPARATOR .
      'i'           . DIRECTORY_SEPARATOR .
      'logo16px.png';

    add_menu_page('CiviCRM', 'CiviCRM', 'access_civicrm', 'CiviCRM', 'civicrm_wp_invoke', $civilogo);
  }
  else {
    add_options_page('CiviCRM Installer', 'CiviCRM Installer', 'manage_options', 'civicrm-install', 'civicrm_run_installer');
  }
}

function civicrm_run_installer() {
  $installFile =
    WP_PLUGIN_DIR . DIRECTORY_SEPARATOR .
    'civicrm' . DIRECTORY_SEPARATOR .
    'civicrm' . DIRECTORY_SEPARATOR .
    'install' . DIRECTORY_SEPARATOR .
    'index.php';
  include ($installFile);
}

function civicrm_setup_warning() {
  $installLink = admin_url() . "options-general.php?page=civicrm-install";
  echo '<div id="civicrm-warning" class="updated fade"><p><strong>' . t('CiviCRM is almost ready.') . '</strong> ' . t('You must <a href="!1">configure CiviCRM</a> for it to work.', array(
    '!1' => $installLink)) . '</p></div>';
}

function civicrm_remove_wp_magic_quotes() {
  $_GET     = stripslashes_deep($_GET);
  $_POST    = stripslashes_deep($_POST);
  $_COOKIE  = stripslashes_deep($_COOKIE);
  $_REQUEST = stripslashes_deep($_REQUEST);
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
  static $initialized = FALSE;
  static $failure = FALSE;

  if ($failure) {
    return FALSE;
  }

  if (!$initialized) {
    // Check for php version and ensure its greater than minPhpVersion
    $minPhpVersion = '5.3.3';
    if (version_compare(PHP_VERSION, $minPhpVersion) < 0) {
      echo "CiviCRM requires PHP Version $minPhpVersion or greater. You are running PHP Version " . PHP_VERSION . "<p>";
      exit();
    }

    $settingsFile = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm.settings.php';

    if (!file_exists($settingsFile)) {
      $error = FALSE;
    }
    else {
      define('CIVICRM_SETTINGS_PATH', $settingsFile);
      $error = include_once ($settingsFile);
    }

    // autoload
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    // get ready for problems
    $installLink    = admin_url() . "options-general.php?page=civicrm-install";
    $docLinkInstall = "http://wiki.civicrm.org/confluence/display/CRMDOC/WordPress+Installation+Guide";
    $docLinkTrouble = "http://wiki.civicrm.org/confluence/display/CRMDOC/Installation+and+Configuration+Trouble-shooting";
    $forumLink      = "http://forum.civicrm.org/index.php/board,6.0.html";

    $errorMsgAdd = t("Please review the <a href='!1'>WordPress Installation Guide</a> and the <a href='!2'>Trouble-shooting page</a> for assistance. If you still need help installing, you can often find solutions to your issue by searching for the error message in the <a href='!3'>installation support section of the community forum</a>.</strong></p>",
      array('!1' => $docLinkInstall, '!2' => $docLinkTrouble, '!3' => $forumLink)
    );

    $installMessage = t("Click <a href='!1'>here</a> for fresh install.", array('!1' => $installLink));

    if ($error == FALSE) {
      header('Location: ' . admin_url() . 'options-general.php?page=civicrm-install');
      return FALSE;
    }

    // this does pretty much all of the civicrm initialization
    if (!file_exists($civicrm_root . 'CRM/Core/Config.php')) {
      $error = FALSE;
    }
    else {
      $error = include_once ('CRM/Core/Config.php');
    }

    if ($error == FALSE) {
      $failure = TRUE;
      //FIX ME
      wp_die("<strong><p class='error'>" .
        t("Oops! - The path for including CiviCRM code files is not set properly. Most likely there is an error in the <em>civicrm_root</em> setting in your CiviCRM settings file (!1).",
          array('!1' => $settingsFile)
        ) .
        "</p><p class='error'> &raquo; " .
        t("civicrm_root is currently set to: <em>!1</em>.", array(
          '!1' => $civicrm_root)) .
        "</p><p class='error'>" . $errorMsgAdd . "</p></strong>"
      );
      return FALSE;
    }

    $initialized = TRUE;

    // initialize the system by creating a config object
    $config = CRM_Core_Config::singleton();

    // sync the logged in user with WP
    global $current_user;
    if ($current_user) {
      require_once 'CRM/Core/BAO/UFMatch.php';
      CRM_Core_BAO_UFMatch::synchronize(
        $current_user,
        FALSE,
        'WordPress',
        civicrm_get_ctype('Individual')
      );
    }
  }

  return TRUE;
}

/**
 * Function to get the contact type
 *
 * @param string $default contact type
 *
 * @return $ctype contact type
 */
function civicrm_get_ctype($default = NULL) {
  // here we are creating a new contact
  // get the contact type from the POST variables if any

  if (isset($_REQUEST['ctype'])) {
    $ctype = $_REQUEST['ctype'];
  }
  elseif (isset($_REQUEST['edit']) &&
    isset($_REQUEST['edit']['ctype'])
  ) {
    $ctype = $_REQUEST['edit']['ctype'];
  }
  else {
    $ctype = $default;
  }

  if ($ctype != 'Individual' &&
    $ctype != 'Organization' &&
    $ctype != 'Household'
  ) {
    $ctype = $default;
  }
  return $ctype;
}

function civicrm_wp_invoke() {
  static $alreadyInvoked = FALSE;
  if ($alreadyInvoked) {
    return;
  }

  $alreadyInvoked = TRUE;
  if (!civicrm_initialize()) {
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
  civicrm_remove_wp_magic_quotes();

  if (isset($_GET['q'])) {
    $args = explode('/', trim($_GET['q']));
  }
  else {
    $_GET['q']     = 'civicrm/dashboard';
    $_GET['reset'] = 1;
    $args          = array('civicrm', 'dashboard');
  }

  global $current_user;
  get_currentuserinfo();

  /* bypass synchronize if running upgrade
   * to avoid any serious non-recoverable error
   * which might hinder the upgrade process.
   */

  if (CRM_Utils_Array::value('q', $_GET) != 'civicrm/upgrade') {
    require_once 'CRM/Core/BAO/UFMatch.php';
    CRM_Core_BAO_UFMatch::synchronize($current_user, FALSE, 'WordPress', 'Individual', TRUE);
  }

  CRM_Core_Invoke::invoke($args);

  // restore WP's timezone
  if ($wpBaseTimezone) {
    date_default_timezone_set($wpBaseTimezone);
  }
}

function civicrm_wp_head() {
  // CRM-11823 - If Civi bootstrapped, then merge its HTML header with the CMS's header
  global $civicrm_root;
  if (empty($civicrm_root)) {
    return;
  }
  $region = CRM_Core_Region::instance('html-header', FALSE);
  if ($region) {
    echo $region->render('');
  }
}

function civicrm_wp_frontend($shortcode = FALSE) {
  if (!civicrm_initialize()) {
    return;
  }

  CRM_Core_Resources::singleton()->addCoreResources();

  // set the frontend part for civicrm code
  $config = CRM_Core_Config::singleton();
  $config->userFrameworkFrontend = TRUE;

  if (isset($_GET['q'])) {
    $args = explode('/', trim($_GET['q']));
  }

  if ($shortcode) {
    civicrm_turn_comments_off();
    civicrm_set_post_blank();
  }
  else {
    add_filter('get_header', 'civicrm_turn_comments_off');
    add_filter('get_header', 'civicrm_set_post_blank');
  }

  // check permission
  if (!civicrm_check_permission($args)) {
    if ($shortcode) {
      civicrm_set_frontendmessage();
    }
    else {
      add_filter('the_content', 'civicrm_set_frontendmessage');
    }
    return;
  }

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
    add_filter('init', 'civicrm_wp_invoke');
    return;
  }

  // this places civicrm inside frontend theme
  // wp documentation rocks if you know what you are looking for
  // but best way is to check other plugin implementation :)
  if ($shortcode) {
    ob_start(); // start buffering
    civicrm_wp_invoke( ); // now, instead of echoing, shortcode output ends up in buffer
    $content = ob_get_clean(); // save the output and flush the buffer
    return $content;
  }
  else {
    add_filter('the_content', 'civicrm_wp_invoke');
  }
}

function civicrm_set_blank() {
  return;
}

function civicrm_set_frontendmessage() {
  return ts('You do not have permission to execute this url.');
}

function civicrm_set_post_blank() {
  global $post;
  // to hide posted on
  $post->post_type = '';
  // to hide post title
  $post->post_title = '';
  // hide the edit link
  add_action('edit_post_link', 'civicrm_set_blank');
}

function civicrm_turn_comments_off() {
  global $post;
  $post->comment_status = "closed";
}

function civicrm_check_permission($args) {
  if ($args[0] != 'civicrm') {
    return FALSE;
  }

  $config = CRM_Core_Config::singleton();

  // set frontend true
  $config->userFrameworkFrontend = TRUE;

  require_once 'CRM/Utils/Array.php';
  // all profile and file urls, as well as user dashboard and tell-a-friend are valid
  $arg1 = CRM_Utils_Array::value(1, $args);
  $validPaths = array('profile', 'user', 'dashboard', 'friend', 'file', 'ajax');
  if (in_array($arg1, $validPaths)) {
    return TRUE;
  }

  $arg2 = CRM_Utils_Array::value(2, $args);
  $arg3 = CRM_Utils_Array::value(3, $args);

  // allow editing of related contacts
  if ($arg1 == 'contact' && $arg2 == 'relatedcontact') {
    return TRUE;
  }

  // a contribution page
  if (in_array('CiviContribute', $config->enableComponents)) {
    if (
      $arg1 == 'contribute' &&
      in_array($arg2, array('transact', 'campaign', 'pcp'))
    ) {
      return TRUE;
    }

    if (
      $arg1 == 'pcp' &&
      (!$arg2 || in_array($arg2, array('info')))
    ) {
      return TRUE;
    }
  }

  // an event registration page is valid
  if (in_array('CiviEvent', $config->enableComponents)) {
    if (
      $arg1 == 'event' &&
      in_array($arg2, array('register', 'info', 'participant', 'ical', 'confirm'))
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
      (!$arg2 || in_array($arg2, array('info')))
    ) {
      return TRUE;
    }
  }

  // allow mailing urls to be processed
  if (
    $arg1 == 'mailing' &&
    in_array('CiviMail', $config->enableComponents)
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
  if (in_array('CiviCampaign', $config->enableComponents)) {
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

function wp_civicrm_capability() {
  global $wp_roles;
  if (!isset($wp_roles)) {
    $wp_roles = new WP_Roles();
  }

  //access civicrm page menu link to particular roles
  $roles = array('super admin', 'administrator');

  foreach ($roles as $role) {
    $roleObj = $wp_roles->get_role($role);
    if (
      is_object($roleObj) &&
      is_array($roleObj->capabilities) &&
      !array_key_exists('access_civicrm', $wp_roles->get_role($role)->capabilities )
    ) {
      $wp_roles->add_cap($role, 'access_civicrm');
    }
  }
}

function civicrm_wp_main() {
  add_action('init', 'wp_civicrm_capability');

  $isAdmin = is_admin();
  if ($isAdmin) {
    add_action('admin_menu', 'civicrm_wp_add_menu_items');

    //Adding "embed form" button
    if (in_array(
        basename($_SERVER['PHP_SELF']),
        array('post.php', 'page.php', 'page-new.php', 'post-new.php')
      )) {
      add_action('media_buttons_context', 'civicrm_add_form_button');
      add_action('admin_footer', 'civicrm_add_form_button_html');
    }

    // check if settings file exist, do not show configuration link on
    // install / settings page
    if (isset($_GET['page']) && $_GET['page'] != 'civicrm-install') {
      $settingsFile = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm.settings.php';

      if (!file_exists($settingsFile)) {
        add_action('admin_notices', 'civicrm_setup_warning');
      }
    }

    add_action('admin_head', 'civicrm_wp_head');
  }

  add_action('user_register', 'civicrm_user_register');
  add_action('profile_update', 'civicrm_profile_update');

  add_shortcode('civicrm', 'civicrm_shortcode_handler');

  if (!$isAdmin) {
    add_action('wp_head', 'civicrm_wp_head');
    add_filter('get_header', 'civicrm_wp_shortcode_includes');
  }

  if (!civicrm_wp_in_civicrm()) {
    return;
  }

  if (!$isAdmin) {
    add_action('wp_footer', 'civicrm_buffer_end');

    // we do this here rather than as an action, since we dont control
    // the order
    civicrm_buffer_start();
    civicrm_wp_frontend();
  }
}

function civicrm_add_form_button($context) {
  if (!civicrm_initialize()) {
    return '';
  }

  $config      = CRM_Core_Config::singleton();
  $imageBtnURL = $config->resourceBase . 'i/logo16px.png';
  $out         = '<a href="#TB_inline?width=480&inlineId=civicrm_frontend_pages" class="button thickbox" id="add_civi" style="padding-left: 4px;" title="' . __("Add CiviCRM Public Pages", 'CiviCRM') . '"><img src="' . $imageBtnURL . '" height="15" width="15" alt="' . __("Add CiviCRM Public Pages", 'CiviCRM') . '" />CiviCRM</a>';
  return $context . $out;
}

function civicrm_add_form_button_html() {
  $title = ts("Please select a CiviCRM front-end page type.");

  $now = date("Ymdhis");

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

  ?>
  <script type="text/javascript">
    jQuery(function($) {
      $('#crm-wp-insert-shortcode').on('click', function() {
        var form_id = $("#add_civicomponent_id").val();
        if (form_id == ""){
          alert ('Please select a frontend element.');
          return;
        }

        var component = $("#add_civicomponent_id").val( );
        var shortcode = '[civicrm component="' + component + '"';

        switch (component) {
          case 'contribution':
            shortcode += ' id="' + $("#add_contributepage_id").val() + '"';
            shortcode += ' mode="' + $("input[name='component_mode']:checked").val() + '"';
            break;
          case 'event':
            shortcode += ' id="' + $("#add_eventpage_id").val() + '"';
            shortcode += ' action="' + $("input[name='event_action']:checked").val() + '"';
            shortcode += ' mode="' + $("input[name='component_mode']:checked").val() + '"';
            break;
          case 'profile':
            shortcode += ' gid="' + $("#add_profilepage_id").val() + '"';
            shortcode += ' mode="' + $("input[name='profile_mode']:checked").val() + '"';
            break;
          case 'user-dashboard':
            break;
        }
        shortcode += ']';
        window.send_to_editor( shortcode );
      });

      $('#add_civicomponent_id').on('change', function() {
        switch ($(this).val()) {
          case 'contribution':
            $('#contribution-section, #component-section').show();
            $('#profile-section, #profile-mode-section').hide();
            $('#event-section, #action-section-event').hide();
            break;
          case 'event':
            $('#contribution-section').hide();
            $('#profile-section, #profile-mode-section').hide();
            $('#event-section, #component-section, #action-section-event').show();
            break;
          case 'profile':
            $('#contribution-section, #component-section').hide();
            $('#profile-section, #profile-mode-section').show();
            $('#event-section, #action-section-event').hide();
            break;
          default:
            $('#contribution-section, #event-section, #component-section, #action-section-event').hide();
            $('#profile-section, #profile-mode-section').hide();
            break;
        }
      });
    });
  </script>

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
                              }?>
                            </select>
                        </span>

                        <span id="event-section" style="display:none;">
                            <select id="add_eventpage_id">
                            <?php
                              foreach ($eventPages as $key => $value) { ?>
                                <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
                                <?php
                              }?>
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
                            }?>
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

function civicrm_shortcode_handler($atts) {
  extract(shortcode_atts(array(
        'component' => 'contribution',
        'action' => NULL,
        'mode' => NULL,
        'id' => NULL,
        'cid' => NULL,
        'gid' => NULL,
        'cs' => NULL,
      ),
      $atts
    ));

  $args = array(
    'reset' => 1,
    'id'    => $id,
  );

  switch ($component) {
    case 'contribution':
      if ($mode == 'preview' || $mode == 'test') {
        $args['action'] = 'preview';
      }
      $args['q'] = 'civicrm/contribute/transact';
      break;

    case 'event':
      switch ($action) {
        case 'register':
          $args['q'] = 'civicrm/event/register';
          if ($mode == 'preview' || $mode == 'test') {
            $args['action'] = 'preview';
          }
          break;

        case 'info':
          $args['q'] = 'civicrm/event/info';
          break;

        default:
          echo 'Do not know how to handle this shortcode<p>';
          return;
      }
      break;

    case 'user-dashboard':
      $args['q'] = 'civicrm/user';
      unset($args['id']);
      break;

    case 'profile':
      if ($mode == 'edit') {
        $args['q'] = 'civicrm/profile/edit';
      }
      else if ($mode == 'view') {
        $args['q'] = 'civicrm/profile/view';
      }
      else {
        $args['q'] = 'civicrm/profile/create';
      }
      $args['gid'] = $gid;
      break;

    default:
      echo 'Do not know how to handle this shortcode<p>';
      return;
  }

  foreach ($args as $key => $value) {
    if ($value !== NULL) {
      $_REQUEST[$key] = $_GET[$key] = $value;
    }
  }

  return civicrm_wp_frontend(TRUE);
}

function civicrm_wp_in_civicrm() {
  return (isset($_GET['page']) &&
    $_GET['page'] == 'CiviCRM'
  ) ? TRUE : FALSE;
}

function civicrm_wp_shortcode_includes() {
  global $post;
  if (preg_match('/\[civicrm/', $post->post_content)) {
    if (!civicrm_initialize()) {
      return;
    }

    CRM_Core_Resources::singleton()->addCoreResources();
  }
}

function wp_get_breadcrumb() {
  global $wp_set_breadCrumb;
  return $wp_set_breadCrumb;
}

function wp_set_breadcrumb($breadCrumb) {
  global $wp_set_breadCrumb;
  $wp_set_breadCrumb = $breadCrumb;
  return $wp_set_breadCrumb;
}

function t($str, $sub = NULL) {
  if (is_array($sub)) {
    $str = str_replace(array_keys($sub), array_values($sub), $str);
  }
  return $str;
}

function civicrm_user_register($userID) {
  _civicrm_update_user($userID);
}

function civicrm_profile_update($userID) {
  _civicrm_update_user($userID);
}

function _civicrm_update_user($userID) {
  $user = get_userdata($userID);
  if ($user) {
    if (!civicrm_initialize()) {
      return;
    }

    require_once 'CRM/Core/BAO/UFMatch.php';
    CRM_Core_BAO_UFMatch::synchronize(
      $user,
      TRUE,
      'WordPress',
      'Individual'
    );
  }
}

function civicrm_buffer_start() {
  ob_start("civicrm_buffer_callback");
}

function civicrm_buffer_end() {
  ob_end_flush();
}

function civicrm_buffer_callback($buffer) {
  // modify buffer here, and then return the updated code
  return $buffer;
}

civicrm_wp_main();
