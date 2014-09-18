<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * WordPress specific stuff goes here
 */
class CRM_Utils_System_WordPress extends CRM_Utils_System_Base {
  /**
   *
   */
  function __construct() {
    /**
     * deprecated property to check if this is a drupal install. The correct method is to have functions on the UF classes for all UF specific
     * functions and leave the codebase oblivious to the type of CMS
     * @deprecated
     * @var bool
     */
    $this->is_drupal = FALSE;
    $this->is_wordpress = TRUE;
  }

  /**
   * sets the title of the page
   *
   * @param string $title
   * @param null $pageTitle
   *
   * @paqram string $pageTitle
   *
   * @return void
   * @access public
   */
  function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }
    if (civicrm_wp_in_civicrm()) {
      global $civicrm_wp_title;
      $civicrm_wp_title = $pageTitle;
      $template = CRM_Core_Smarty::singleton();
      $template->assign('pageTitle', $pageTitle);
    }
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param $breadCrumbs
   *
   * @internal param string $title
   * @internal param string $url
   *
   * @return void
   * @access public
   * @static
   */
  function appendBreadCrumb($breadCrumbs) {
    $breadCrumb = wp_get_breadcrumb();

    if (is_array($breadCrumbs)) {
      foreach ($breadCrumbs as $crumbs) {
        if (stripos($crumbs['url'], 'id%%')) {
          $args = array('cid', 'mid');
          foreach ($args as $a) {
            $val = CRM_Utils_Request::retrieve($a, 'Positive', CRM_Core_DAO::$_nullObject,
              FALSE, NULL, $_GET
            );
            if ($val) {
              $crumbs['url'] = str_ireplace("%%{$a}%%", $val, $crumbs['url']);
            }
          }
        }
        $breadCrumb[] = "<a href=\"{$crumbs['url']}\">{$crumbs['title']}</a>";
      }
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign_by_ref('breadcrumb', $breadCrumb);
    wp_set_breadcrumb($breadCrumb);
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @return void
   * @access public
   * @static
   */
  function resetBreadCrumb() {
    $bc = array();
    wp_set_breadcrumb($bc);
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $head the new string to be appended
   *
   * @return void
   * @access public
   * @static
   */
  function addHTMLHead($head) {
    static $registered = FALSE;
    if (!$registered) {
      // front-end view
      add_action('wp_head', array(__CLASS__, '_showHTMLHead'));
      // back-end views
      add_action('admin_head', array(__CLASS__, '_showHTMLHead'));
    }
    CRM_Core_Region::instance('wp_head')->add(array(
      'markup' => $head,
    ));
  }

  static function _showHTMLHead() {
    $region = CRM_Core_Region::instance('wp_head', FALSE);
    if ($region) {
      echo $region->render('');
    }
  }

  /**
   * Add a script file
   *
   * @param $url: string, absolute path to file
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addScriptUrl($url, $region) {
    return FALSE;
  }

  /**
   * Add an inline script
   *
   * @param $code: string, javascript code
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addScript($code, $region) {
    return FALSE;
  }

  /**
   * Add a css file
   *
   * @param $url: string, absolute path to file
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addStyleUrl($url, $region) {
    return FALSE;
  }

  /**
   * Add an inline style
   *
   * @param $code: string, css code
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addStyle($code, $region) {
    return FALSE;
  }

  /**
   * rewrite various system urls to https
   *
   * @param null
   *
   * @return void
   * @access public
   * @static
   */
  function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
  }

  /**
   * figure out the post url for the form
   *
   * @param mix $action the default action if one is pre-specified
   *
   * @return string the url to post the form
   * @access public
   * @static
   */
  function postURL($action) {
    if (!empty($action)) {
      return $action;
    }

    return $this->url($_GET['q'], NULL, TRUE, NULL, FALSE);
  }

  /**
   * Generate an internal CiviCRM URL (copied from DRUPAL/includes/common.inc#url)
   *
   * @param $path     string   The path being linked to, such as "civicrm/add"
   * @param $query    string   A query string to append to the link.
   * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param $fragment string   A fragment identifier (named anchor) to append to the link.
   * @param $htmlize  boolean  whether to convert to html eqivalant
   * @param $frontend boolean  a gross joomla hack
   *
   * @param bool $forceBackend
   *
   * @return string            an HTML string containing a link to the given path.
   * @access public
   */
  function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $htmlize = TRUE,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $config    = CRM_Core_Config::singleton();
    $script    = '';
    $separator = $htmlize ? '&amp;' : '&';
    $wpPageParam    = '';
    $fragment = isset($fragment) ? ('#' . $fragment) : '';

    $path = CRM_Utils_String::stripPathChars($path);

    //this means wp function we are trying to use is not available,
    //so load bootStrap
    if (!function_exists('get_option')) {
      $this->loadBootStrap(); // FIXME: Why bootstrap in url()? Generally want to define 1-2 strategic places to put bootstrap
    }
    if ($config->userFrameworkFrontend) {
      if (get_option('permalink_structure') != '') {
        global $post;
        $script = get_permalink($post->ID);
      }

      // when shortcode is included in page
      // also make sure we have valid query object
      global $wp_query;
      if ( method_exists( $wp_query, 'get' ) ) {
        if (get_query_var('page_id')) {
          $wpPageParam = "page_id=" . get_query_var('page_id');
        }
        elseif (get_query_var('p')) {
          // when shortcode is inserted in post
          $wpPageParam = "p=" . get_query_var('p');
        }
      }
    }

    $base = $this->getBaseUrl($absolute, $frontend, $forceBackend);

    if (!isset($path) && !isset($query)) {
      // FIXME: This short-circuited codepath is the same as the general one below, except
      // in that it ignores "permlink_structure" /  $wpPageParam / $script . I don't know
      // why it's different (and I can only find two obvious use-cases for this codepath,
      // of which at least one looks gratuitous). A more ambitious person would simply remove
      // this code.
      return $base . $fragment;
    }

    if (!$forceBackend && get_option('permalink_structure') != '' && ($wpPageParam || $script != '')) {
      $base = $script;
    }

    $queryParts = array();
    if (isset($path)) {
      $queryParts[] = 'page=CiviCRM';
      $queryParts[] = "q={$path}";
    }
    if ($wpPageParam) {
      $queryParts[] = $wpPageParam;
    }
    if (isset($query)) {
      $queryParts[] = $query;
    }

    return $base . '?' . implode($separator, $queryParts) . $fragment;
  }

  /**
   * @param $absolute
   * @param $frontend
   * @param $forceBackend
   *
   * @return mixed|null|string
   */
  private function getBaseUrl($absolute, $frontend, $forceBackend) {
    $config    = CRM_Core_Config::singleton();

    if (!isset($config->useFrameworkRelativeBase)) {
      $base = parse_url($config->userFrameworkBaseURL);
      $config->useFrameworkRelativeBase = $base['path'];
    }

    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    if ((is_admin() && !$frontend) || $forceBackend) {
      $base .= 'wp-admin/admin.php';
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

  /**
   * Authenticate the user against the wordpress db
   *
   * @param string $name the user name
   * @param string $password the password for the above user name
   *
   * @param bool $loadCMSBootstrap
   * @param null $realPath
   *
   * @return mixed false if no auth
   *               array(
   * contactID, ufID, unique string ) if success
   * @access public
   * @static
   */
  function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $config = CRM_Core_Config::singleton();

    if ($loadCMSBootstrap) {
      $config->userSystem->loadBootStrap($name, $password);
    }

    $user = wp_authenticate($name, $password);
    if (is_a($user, 'WP_Error')) {
      return FALSE;
    }

    // need to change this to make sure we matched only one row

    CRM_Core_BAO_UFMatch::synchronizeUFMatch($user->data, $user->data->ID, $user->data->user_email, 'WordPress');
    $contactID = CRM_Core_BAO_UFMatch::getContactId($user->data->ID);
    if (!$contactID) {
      return FALSE;
    }
    return array($contactID, $user->data->ID, mt_rand());
  }

  /**
   * Set a message in the UF to display to a user
   *
   * @param string $message the message to set
   *
   * @access public
   * @static
   */
  function setMessage($message) {
  }

  /**
   * @param $user
   *
   * @return bool
   */
  function loadUser( $user ) {
    return true;
  }

  function permissionDenied() {
    CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
  }

  function logout() {
    // destroy session
    if (session_id()) {
      session_destroy();
    }
    wp_logout();
    wp_redirect(wp_login_url());
  }

  function updateCategories() {}

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string  with the locale or null for none
   */
  function getUFLocale() {
    // WPML plugin
    if (defined('ICL_LANGUAGE_CODE')) {
      $language = ICL_LANGUAGE_CODE;
    }

    // TODO: set language variable for others WordPress plugin

    if (isset($language)) {
      return CRM_Core_I18n_PseudoConstant::longForShort(substr($language, 0, 2));
    } else {
      return NULL;
    }
  }

  /**
   * load wordpress bootstrap
   *
   * @param $name string  optional username for login
   * @param $pass string  optional password for login
   *
   * @return bool
   */
  function loadBootStrap($name = NULL, $pass = NULL) {
    global $wp, $wp_rewrite, $wp_the_query, $wp_query, $wpdb;

    $cmsRootPath = $this->cmsRootPath();
    if (!$cmsRootPath) {
      CRM_Core_Error::fatal("Could not find the install directory for WordPress");
    }

    require_once ($cmsRootPath . DIRECTORY_SEPARATOR . 'wp-load.php');
    $wpUserTimezone = get_option('timezone_string');
    if ($wpUserTimezone) {
      date_default_timezone_set($wpUserTimezone);
      CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();
    }
    require_once ($cmsRootPath . DIRECTORY_SEPARATOR . 'wp-includes/pluggable.php');
    $uid = CRM_Utils_Array::value('uid', $name);
    if ($uid) {
      $account = wp_set_current_user($uid);
      if ($account && $account->data->ID) {
        global $user;
        $user = $account;
        return TRUE;
      }
    }
    return true;
  }

  /**
   * @param $dir
   *
   * @return bool
   */
  function validInstallDir($dir) {
    $includePath = "$dir/wp-includes";
    if (
      @opendir($includePath) &&
      file_exists("$includePath/version.php")
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine the location of the CMS root.
   *
   * @return string|NULL local file system path to CMS root, or NULL if it cannot be determined
   */
  /**
   * @return NULL|string
   */
  function cmsRootPath() {
    $cmsRoot = $valid = NULL;
    if (defined('CIVICRM_CMSDIR')) {
      if ($this->validInstallDir(CIVICRM_CMSDIR)) {
        $cmsRoot = CIVICRM_CMSDIR;
        $valid = TRUE;
      }
    }
    else {
      $pathVars = explode('/', str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']));

      //might be windows installation.
      $firstVar = array_shift($pathVars);
      if ($firstVar) {
        $cmsRoot = $firstVar;
      }

      //start w/ csm dir search.
      foreach ($pathVars as $var) {
        $cmsRoot .= "/$var";
        if ($this->validInstallDir($cmsRoot)) {
          //stop as we found bootstrap.
          $valid = TRUE;
          break;
        }
      }
    }

    return ($valid) ? $cmsRoot : NULL;
  }

  /**
   * @param $params
   * @param $mail
   *
   * @return mixed
   */
  function createUser(&$params, $mail) {
    $user_data = array(
      'ID' => '',
      'user_pass' => $params['cms_pass'],
      'user_login' => $params['cms_name'],
      'user_email' => $params[$mail],
      'nickname' => $params['cms_name'],
      'role' => get_option('default_role'),
    );
    if (isset($params['contactID'])) {
      $contactType = CRM_Contact_BAO_Contact::getContactType($params['contactID']);
      if ($contactType == 'Individual') {
        $user_data['first_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'first_name'
        );
        $user_data['last_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'last_name'
        );
      }
    }

    $uid = wp_insert_user($user_data);

    $creds = array();
    $creds['user_login'] = $params['cms_name'];
    $creds['user_password'] = $params['cms_pass'];
    $creds['remember'] = TRUE;
    $user = wp_signon($creds, FALSE);

    wp_new_user_notification($uid, $user_data['user_pass']);
    return $uid;
  }

  /**
   * Change user name in host CMS
   *
   * @param integer $ufID User ID in CMS
   * @param string $ufName User name
   */
  function updateCMSName($ufID, $ufName) {
    // CRM-10620
    if (function_exists('wp_update_user')) {
      $ufID   = CRM_Utils_Type::escape($ufID, 'Integer');
      $ufName = CRM_Utils_Type::escape($ufName, 'String');

      $values = array ('ID' => $ufID, 'user_email' => $ufName);
      if( $ufID ) {
        wp_update_user( $values ) ;
      }
    }
  }

  /**
   * @param $params
   * @param $errors
   * @param string $emailName
   */
  function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();

    $dao   = new CRM_Core_DAO();
    $name  = $dao->escape(CRM_Utils_Array::value('name', $params));
    $email = $dao->escape(CRM_Utils_Array::value('mail', $params));

    if (!empty($params['name'])) {
      if (!validate_username($params['name'])) {
        $errors['cms_name'] = ts("Your username contains invalid characters");
      }
      elseif (username_exists(sanitize_user($params['name']))) {
        $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', array(1 => $params['name']));
      }
    }

    if (!empty($params['mail'])) {
      if (!is_email($params['mail'])) {
        $errors[$emailName] = "Your email is invaid";
      }
      elseif (email_exists($params['mail'])) {
        $resetUrl = $config->userFrameworkBaseURL . 'wp-login.php?action=lostpassword';
        $errors[$emailName] = ts('The email address %1 is already registered. <a href="%2">Have you forgotten your password?</a>',
          array(1 => $params['mail'], 2 => $resetUrl)
        );
      }
    }
  }

  /**
   * check is user logged in.
   *
   * @return boolean true/false.
   */
  public function isUserLoggedIn() {
    $isloggedIn = FALSE;
    if (function_exists('is_user_logged_in')) {
      $isloggedIn = is_user_logged_in();
    }

    return $isloggedIn;
  }

  /**
   * @return mixed
   */
  function getLoggedInUserObject() {
    if (function_exists('is_user_logged_in') &&
    is_user_logged_in()) {
      global $current_user;
    }
    return $current_user;
  }
  /**
   * Get currently logged in user uf id.
   *
   * @return int $userID logged in user uf id.
   */
  public function getLoggedInUfID() {
    $ufID = NULL;
    $current_user = $this->getLoggedInUserObject();
    return isset($current_user->ID) ? $current_user->ID : NULL;
  }

  /**
   * Get currently logged in user unique identifier - this tends to be the email address or user name.
   *
   * @return string $userID logged in user unique identifier
   */
  function getLoggedInUniqueIdentifier() {
    $user = $this->getLoggedInUserObject();
    return $this->getUniqueIdentifierFromUserObject($user);
  }

  /**
   * Get User ID from UserFramework system (Joomla)
   * @param object $user object as described by the CMS
   * @return mixed <NULL, number>
   */
  function getUserIDFromUserObject($user) {
    return !empty($user->ID) ? $user->ID : NULL;
  }

  /**
   * Get Unique Identifier from UserFramework system (CMS)
   * @param object $user object as described by the User Framework
   * @return mixed $uniqueIdentifer Unique identifier from the user Framework system
   *
   */
  function getUniqueIdentifierFromUserObject($user) {
    return empty($user->user_email) ? NULL : $user->user_email;
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination - if present, add destination to querystring (works for Drupal only)
   *
   * @return string - loginURL for the current CMS
   *
   */
  public function getLoginURL($destination = '') {
    $config = CRM_Core_Config::singleton();
    $loginURL = $config->userFrameworkBaseURL;
    $loginURL .= 'wp-login.php';
    return $loginURL;
  }

  /**
   * @param $form
   */
  public function getLoginDestination(&$form) {
    return;
  }

  /**
   * Return the current WordPress version if relevant function exists
   *
   * @return string - version number
   *
   */
  function getVersion() {
    if (function_exists('get_bloginfo')) {
      return get_bloginfo('version', 'display');
    }
    else {
      return 'Unknown';
    }
  }

  /**
   * get timezone as a string
   * @return string Timezone e.g. 'America/Los_Angeles'
   */
  function getTimeZoneString() {
    return get_option('timezone_string');
  }

  /**
   * Get Url to view user record
   * @param integer $contactID Contact ID
   *
   * @return string
   */
  function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    if (CRM_Core_Session::singleton()->get('userID') == $contactID || CRM_Core_Permission::checkAnyPerm(array('cms:administer users'))) {
      return CRM_Core_Config::singleton()->userFrameworkBaseURL . "wp-admin/user-edit.php?user_id=" . $uid;
    }
  }
}

