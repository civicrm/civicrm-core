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
 * $Id$
 *
 */

/**
 * WordPress specific stuff goes here
 */
class CRM_Utils_System_WordPress extends CRM_Utils_System_Base {
  function __construct() {
    $this->is_drupal = FALSE;
  }

  /**
   * sets the title of the page
   *
   * @param string $title
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
   * @param string $title
   * @param string $url
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
   * @return string            an HTML string containing a link to the given path.
   * @access public
   *
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
    $pageID    = '';

    $path = CRM_Utils_String::stripPathChars($path);

    //this means wp function we are trying to use is not available,
    //so load bootStrap
    if (!function_exists('get_option')) {
      $this->loadBootStrap();
    }
    $permlinkStructure = get_option('permalink_structure');
    if ($config->userFrameworkFrontend) {
      if ($permlinkStructure != '') {
        global $post;
        $script = get_permalink($post->ID);
      }

      // when shortcode is included in page
      // also make sure we have valid query object
      global $wp_query;
      if ( method_exists( $wp_query, 'get' ) ) {
        if (get_query_var('page_id')) {
          $pageID = "{$separator}page_id=" . get_query_var('page_id');
        }
        elseif (get_query_var('p')) {
          // when shortcode is inserted in post
          $pageID = "{$separator}p=" . get_query_var('p');
        }
      }
    }

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }

    if (!isset($config->useFrameworkRelativeBase)) {
      $base = parse_url($config->userFrameworkBaseURL);
      $config->useFrameworkRelativeBase = $base['path'];
    }

    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    if ((is_admin() && !$frontend) || $forceBackend) {
      $base .= 'wp-admin/admin.php';
    }
    elseif (defined('CIVICRM_UF_WP_BASEPAGE')) {
      $base .= CIVICRM_UF_WP_BASEPAGE;
    }
    elseif (isset($config->wpBasePage)) {
      $base .= $config->wpBasePage;
    }

    if (isset($path)) {
      if (isset($query)) {
        if ($permlinkStructure != '' && ($pageID || $script != '')) {
          return $script . '?page=CiviCRM&q=' . $path . $pageID . $separator . $query . $fragment;
        }
        else {
          return $base . '?page=CiviCRM&q=' . $path . $pageID . $separator . $query . $fragment;
        }
      }
      else {
        if ($permlinkStructure != '' && ($pageID || $script != '')) {
          return $script . '?page=CiviCRM&q=' . $path . $pageID . $fragment;
        }
        else {
          return $base .'?page=CiviCRM&q=' . $path . $pageID . $fragment;
        }
      }
    }
    else {
      if (isset($query)) {
        if ($permlinkStructure != '' && ($pageID || $script != '')) {
          return $script . '?' . $query . $pageID . $fragment;
        }
        else {
          return $base . $script . '?' . $query . $pageID . $fragment;
        }
      }
      else {
        return $base . $fragment;
      }
    }
  }

  /**
   * Authenticate the user against the wordpress db
   *
   * @param string $name     the user name
   * @param string $password the password for the above user name
   *
   * @return mixed false if no auth
   *               array(
      contactID, ufID, unique string ) if success
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
    return NULL;
  }

  /**
   * load wordpress bootstrap
   *
   * @param $name string  optional username for login
   * @param $pass string  optional password for login
   */
  function loadBootStrap($name = NULL, $pass = NULL) {
    global $wp, $wp_rewrite, $wp_the_query, $wp_query, $wpdb;

    $cmsRootPath = $this->cmsRootPath();
    if (!$cmsRootPath) {
      CRM_Core_Error::fatal("Could not find the install directory for WordPress");
    }

    require_once ($cmsRootPath . DIRECTORY_SEPARATOR . 'wp-load.php');
    return true;
  }

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

  /*
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

  function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();

    $dao   = new CRM_Core_DAO();
    $name  = $dao->escape(CRM_Utils_Array::value('name', $params));
    $email = $dao->escape(CRM_Utils_Array::value('mail', $params));

    if (CRM_Utils_Array::value('name', $params)) {
      if (!validate_username($params['name'])) {
        $errors['cms_name'] = ts("Your username contains invalid characters");
      }
      elseif (username_exists(sanitize_user($params['name']))) {
        $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', array(1 => $params['name']));
      }
    }

    if (CRM_Utils_Array::value('mail', $params)) {
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
   * Get currently logged in user uf id.
   *
   * @return int $userID logged in user uf id.
   */
  public function getLoggedInUfID() {
    $ufID = NULL;
    if (function_exists('is_user_logged_in') &&
      is_user_logged_in()
    ) {
      global $current_user;
      $ufID = $current_user->ID;
    }
    return $ufID;
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
}

