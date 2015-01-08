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
 * Joomla specific stuff goes here
 */
class CRM_Utils_System_Joomla extends CRM_Utils_System_Base {
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
  }

  /**
   * Function to create a user of Joomla.
   *
   * @param array  $params associated array
   * @param string $mail email id for cms user
   *
   * @return uid if user exists, false otherwise
   *
   * @access public
   */
  function createUser(&$params, $mail) {
    $baseDir = JPATH_SITE;
    require_once $baseDir . '/components/com_users/models/registration.php';

    $userParams = JComponentHelper::getParams('com_users');
    $model      = new UsersModelRegistration();
    $ufID       = NULL;

    // get the default usertype
    $userType = $userParams->get('new_usertype');
    if (!$userType) {
      $userType = 2;
    }

    if (isset($params['name'])) {
      $fullname = trim($params['name']);
    }
    elseif (isset($params['contactID'])) {
      $fullname = trim(CRM_Contact_BAO_Contact::displayName($params['contactID']));
    }
    else {
      $fullname = trim($params['cms_name']);
    }

    // Prepare the values for a new Joomla user.
    $values              = array();
    $values['name']      = $fullname;
    $values['username']  = trim($params['cms_name']);
    $values['password1'] = $values['password2'] = $params['cms_pass'];
    $values['email1']    = $values['email2'] = trim($params[$mail]);

    $lang = JFactory::getLanguage();
    $lang->load('com_users', $baseDir);

    $register = $model->register($values);

    $ufID = JUserHelper::getUserId($values['username']);
    return $ufID;
  }

  /**
   *  Change user name in host CMS
   *
   *  @param integer $ufID User ID in CMS
   *  @param string $ufName User name
   */
  function updateCMSName($ufID, $ufName) {
    $ufID = CRM_Utils_Type::escape($ufID, 'Integer');
    $ufName = CRM_Utils_Type::escape($ufName, 'String');

    $values = array();
    $user = JUser::getInstance($ufID);

    $values['email'] = $ufName;
    $user->bind($values);

    $user->save();
  }

  /**
   * Check if username and email exists in the Joomla! db
   *
   * @params $params    array   array of name and mail values
   * @params $errors    array   array of errors
   * @params $emailName string  field label for the 'email'
   *
   * @param $params
   * @param $errors
   * @param string $emailName
   *
   * @return void
   */
  function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();

    $dao   = new CRM_Core_DAO();
    $name  = $dao->escape(CRM_Utils_Array::value('name', $params));
    $email = $dao->escape(CRM_Utils_Array::value('mail', $params));
    //don't allow the special characters and min. username length is two
    //regex \\ to match a single backslash would become '/\\\\/'
    $isNotValid = (bool) preg_match('/[\<|\>|\"|\'|\%|\;|\(|\)|\&|\\\\|\/]/im', $name);
    if ($isNotValid || strlen($name) < 2) {
      $errors['cms_name'] = ts('Your username contains invalid characters or is too short');
    }


    $JUserTable = &JTable::getInstance('User', 'JTable');

    $db = $JUserTable->getDbo();
    $query = $db->getQuery(TRUE);
    $query->select('username, email');
    $query->from($JUserTable->getTableName());
    $query->where('(LOWER(username) = LOWER(\'' . $name . '\')) OR (LOWER(email) = LOWER(\'' . $email . '\'))');
    $db->setQuery($query, 0, 10);
    $users = $db->loadAssocList();

    $row = array();;
    if (count($users)) {
      $row = $users[0];
    }

    if (!empty($row)) {
      $dbName = CRM_Utils_Array::value('username', $row);
      $dbEmail = CRM_Utils_Array::value('email', $row);
      if (strtolower($dbName) == strtolower($name)) {
        $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.',
          array(1 => $name)
        );
      }
      if (strtolower($dbEmail) == strtolower($email)) {
        $resetUrl = str_replace('administrator/', '', $config->userFrameworkBaseURL) . 'index.php?option=com_users&view=reset';
        $errors[$emailName] = ts('The email address %1 is already registered. <a href="%2">Have you forgotten your password?</a>',
          array(1 => $email, 2 => $resetUrl)
        );
      }
    }
  }

  /**
   * sets the title of the page
   *
   * @param string $title title to set
   * @param string $pageTitle
   *
   * @return void
   * @access public
   */
  function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign('pageTitle', $pageTitle);

    $document = JFactory::getDocument();
    $document->setTitle($title);

    return;
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
   */
  function appendBreadCrumb($breadCrumbs) {
    $template = CRM_Core_Smarty::singleton();
    $bc = $template->get_template_vars('breadcrumb');

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
        $bc[] = $crumbs;
      }
    }
    $template->assign_by_ref('breadcrumb', $bc);
    return;
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @internal param string $bc the new breadcrumb to be appended
   *
   * @return void
   * @access public
   */
  function resetBreadCrumb() {
    return;
  }

  /**
   * Append a string to the head of the html file
   *
   * @param null $string
   *
   * @internal param string $head the new string to be appended
   *
   * @return void
   * @access public
   */
  static function addHTMLHead($string = NULL) {
    if ($string) {
      $document = JFactory::getDocument();
      $document->addCustomTag($string);
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
    if ($region == 'html-header') {
      $document = JFactory::getDocument();
      $document->addStyleSheet($url);
      return TRUE;
    }
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
    if ($region == 'html-header') {
      $document = JFactory::getDocument();
      $document->addStyleDeclaration($code);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Generate an internal CiviCRM URL
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
  function url($path = NULL, $query = NULL, $absolute = TRUE,
    $fragment = NULL, $htmlize = TRUE,
    $frontend = FALSE, $forceBackend = FALSE
  ) {
    $config    = CRM_Core_Config::singleton();
    $separator = $htmlize ? '&amp;' : '&';
    $Itemid    = '';
    $script    = '';
    $path      = CRM_Utils_String::stripPathChars($path);

    if ($config->userFrameworkFrontend) {
      $script = 'index.php';
      if (JRequest::getVar("Itemid")) {
        $Itemid = "{$separator}Itemid=" . JRequest::getVar("Itemid");
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

    if (!empty($query)) {
      $url = "{$base}{$script}?option=com_civicrm{$separator}task={$path}{$Itemid}{$separator}{$query}{$fragment}";
    }
    else {
      $url = "{$base}{$script}?option=com_civicrm{$separator}task={$path}{$Itemid}{$fragment}";
    }

    // gross hack for joomla, we are in the backend and want to send a frontend url
    if ($frontend && $config->userFramework == 'Joomla') {
      // handle both joomla v1.5 and v1.6, CRM-7939
      $url = str_replace('/administrator/index2.php', '/index.php', $url);
      $url = str_replace('/administrator/index.php', '/index.php', $url);

      // CRM-8215
      $url = str_replace('/administrator/', '/index.php', $url);
    }
    elseif ($forceBackend) {
      if (defined('JVERSION')) {
        $joomlaVersion = JVERSION;
      } else {
        $jversion = new JVersion;
        $joomlaVersion = $jversion->getShortVersion();
      }

      if (version_compare($joomlaVersion, '1.6') >= 0) {
        $url = str_replace('/index.php', '/administrator/index.php', $url);
      }
    }
    return $url;
  }

  /**
   * rewrite various system urls to https
   *
   * @return void
   * access public
   */
  function mapConfigToSSL() {
    // dont need to do anything, let CMS handle their own switch to SSL
    return;
  }

  /**
   * figure out the post url for the form
   *
   * @param $action the default action if one is pre-specified
   *
   * @return string the url to post the form
   * @access public
   */
  function postURL($action) {
    if (!empty($action)) {
      return $action;
    }

    return $this->url(CRM_Utils_Array::value('task', $_GET),
      NULL, TRUE, NULL, FALSE
    );
  }

  /**
   * Function to set the email address of the user
   *
   * @param object $user handle to the user object
   *
   * @return void
   * @access public
   */
  function setEmail(&$user) {
    global $database;
    $query = "SELECT email FROM #__users WHERE id='$user->id'";
    $database->setQuery($query);
    $user->email = $database->loadResult();
  }

  /**
   * Authenticate the user against the joomla db
   *
   * @param string $name     the user name
   * @param string $password the password for the above user name
   * @param $loadCMSBootstrap boolean load cms bootstrap?
   *
   * @return mixed false if no auth
   *               array(
      contactID, ufID, unique string ) if success
   * @access public
   */
  function authenticate($name, $password, $loadCMSBootstrap = FALSE) {
    require_once 'DB.php';

    $config = CRM_Core_Config::singleton();
    $user = NULL;

    if ($loadCMSBootstrap) {
      $bootStrapParams = array();
      if ($name && $password) {
        $bootStrapParams = array(
          'name' => $name,
          'pass' => $password,
        );
      }
      CRM_Utils_System::loadBootStrap($bootStrapParams, TRUE, TRUE, FALSE);
    }

    jimport('joomla.application.component.helper');
    jimport('joomla.database.table');
    jimport('joomla.user.helper');

    $JUserTable = JTable::getInstance('User', 'JTable');

    $db = $JUserTable->getDbo();
    $query = $db->getQuery(TRUE);
    $query->select('id, name, username, email, password');
    $query->from($JUserTable->getTableName());
    $query->where('(LOWER(username) = LOWER(\'' . $name . '\')) AND (block = 0)');
    $db->setQuery($query, 0, 0);
    $users = $db->loadObjectList();

    $row = array();
    if (count($users)) {
      $row = $users[0];
    }

    $joomlaBase = dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))));
    if ( !defined('JVERSION') ) {
      require $joomlaBase . '/libraries/cms/version/version.php';
      $jversion = new JVersion;
      define('JVERSION', $jversion->getShortVersion());
    }

    if (!empty($row)) {
      $dbPassword = $row->password;
      $dbId = $row->id;
      $dbEmail = $row->email;

      if ( version_compare(JVERSION, '2.5.18', 'lt') ||
        ( version_compare(JVERSION, '3.0', 'ge') && version_compare(JVERSION, '3.2.1', 'lt') )
      ) {
        // now check password
        if (strpos($dbPassword, ':') === FALSE) {
          if ($dbPassword != md5($password)) {
            return FALSE;
          }
        }
        else {
          list($hash, $salt) = explode(':', $dbPassword);
          $cryptpass = md5($password . $salt);
          if ($hash != $cryptpass) {
            return FALSE;
          }
        }
      }
      else {
        if (!JUserHelper::verifyPassword($password, $dbPassword, $dbId)) return FALSE;

        //include additional files required by Joomla 3.2.1+
        if ( version_compare(JVERSION, '3.2.1', 'ge') ) {
          require_once $joomlaBase . '/libraries/cms/application/helper.php';
          require_once $joomlaBase . '/libraries/cms/application/cms.php';
          require_once $joomlaBase . '/libraries/cms/application/administrator.php';
        }
      }

      CRM_Core_BAO_UFMatch::synchronizeUFMatch($row, $dbId, $dbEmail, 'Joomla');
      $contactID = CRM_Core_BAO_UFMatch::getContactId($dbId);
      if (!$contactID) {
        return FALSE;
      }
      return array($contactID, $dbId, mt_rand());
    }

    return FALSE;
  }

  /**
   * Set a init session with user object
   *
   * @param array $data  array with user specific data
   *
   * @access public
   */
  function setUserSession($data) {
    list($userID, $ufID) = $data;
    $user = new JUser( $ufID );
    $session = JFactory::getSession();
    $session->set('user', $user);

    parent::setUserSession($data);
  }

  /**
   * Set a message in the UF to display to a user
   *
   * @param string $message  the message to set
   *
   * @access public
   */
  function setMessage($message) {
    return;
  }

  /**
   * @param $user
   *
   * @return bool
   */
  function loadUser($user) {
    return TRUE;
  }

  function permissionDenied() {
    CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
  }

  function logout() {
    session_destroy();
    header("Location:index.php");
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string  the used locale or null for none
   */
  function getUFLocale() {
    if (defined('_JEXEC')) {
      $conf = JFactory::getConfig();
      $locale = $conf->get('language');
      return str_replace('-', '_', $locale);
    }
    return NULL;
  }

  /**
   * @return string
   */
  function getVersion() {
    if (class_exists('JVersion')) {
      $version = new JVersion;
      return $version->getShortVersion();
    }
    else {
      return 'Unknown';
    }
  }

  /**
   * load joomla bootstrap
   *
   * @param $params array with uid or name and password
   * @param $loadUser boolean load cms user?
   * @param bool|\throw $throwError throw error on failure?
   * @param null $realPath
   * @param bool $loadDefines
   *
   * @return bool
   */
  function loadBootStrap($params = array(), $loadUser = TRUE, $throwError = TRUE, $realPath = NULL, $loadDefines = TRUE) {
    // Setup the base path related constant.
    $joomlaBase = dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))));

    // load BootStrap here if needed
    // We are a valid Joomla entry point.
    if ( ! defined( '_JEXEC' ) && $loadDefines ) {
      define('_JEXEC', 1);
      define('DS', DIRECTORY_SEPARATOR);
      define('JPATH_BASE', $joomlaBase . '/administrator');
      require $joomlaBase . '/administrator/includes/defines.php';
    }

    // Get the framework.
    if (file_exists($joomlaBase . '/libraries/import.legacy.php')) {
      require $joomlaBase . '/libraries/import.legacy.php';
    }
    require $joomlaBase . '/libraries/import.php';
    require $joomlaBase . '/libraries/joomla/event/dispatcher.php';
    require $joomlaBase . '/configuration.php';

    // Files may be in different places depending on Joomla version
    if ( !defined('JVERSION') ) {
      require $joomlaBase . '/libraries/cms/version/version.php';
      $jversion = new JVersion;
      define('JVERSION', $jversion->getShortVersion());
    }

    if( version_compare(JVERSION, '3.0', 'lt') ) {
      require $joomlaBase . '/libraries/joomla/environment/uri.php';
      require $joomlaBase . '/libraries/joomla/application/component/helper.php';
    }
    else {
      require $joomlaBase . '/libraries/cms.php';
      require $joomlaBase . '/libraries/joomla/uri/uri.php';
    }

    jimport('joomla.application.cli');

    // CRM-14281 Joomla wasn't available during bootstrap, so hook_civicrm_config never executes.
    $config = CRM_Core_Config::singleton();
    CRM_Utils_Hook::config($config);

    return TRUE;
  }

  /**
   * check is user logged in.
   *
   * @return boolean true/false.
   */
  public function isUserLoggedIn() {
    $user = JFactory::getUser();
    return ($user->guest) ? FALSE : TRUE;
  }

  /**
   * Get currently logged in user uf id.
   *
   * @return int logged in user uf id.
   */
  public function getLoggedInUfID() {
    $user = JFactory::getUser();
    return ($user->guest) ? NULL : $user->id;
  }

  /**
   * Get currently logged in user unique identifier - this tends to be the email address or user name.
   *
   * @return string $userID logged in user unique identifier
   */
  function getLoggedInUniqueIdentifier() {
    $user = JFactory::getUser();
    return $this->getUniqueIdentifierFromUserObject($user);
  }
  /**
   * Get User ID from UserFramework system (Joomla)
   * @param object $user object as described by the CMS
   * @return mixed <NULL, number>
   */
  function getUserIDFromUserObject($user) {
    return !empty($user->id) ? $user->id : NULL;
  }

  /**
   * Get Unique Identifier from UserFramework system (CMS)
   * @param object $user object as described by the User Framework
   * @return mixed $uniqueIdentifer Unique identifier from the user Framework system
   *
   */
  function getUniqueIdentifierFromUserObject($user) {
    return ($user->guest) ? NULL : $user->email;
  }

  /**
   * Get a list of all installed modules, including enabled and disabled ones
   *
   * @return array CRM_Core_Module
   */
  function getModules() {
    $result = array();

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('type, folder, element, enabled')
      ->from('#__extensions')
      ->where('type =' . $db->Quote('plugin'));
    $plugins = $db->setQuery($query)->loadAssocList();
    foreach ($plugins as $plugin) {
      // question: is the folder really a critical part of the plugin's name?
      $name = implode('.', array('joomla', $plugin['type'], $plugin['folder'], $plugin['element']));
      $result[] = new CRM_Core_Module($name, $plugin['enabled'] ? TRUE : FALSE);
    }

    return $result;
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination - if present, add destination to querystring (works for Drupal only)
   *
   * @return string - loginURL for the current CMS
   * @static
   */
  public function getLoginURL($destination = '') {
    $config = CRM_Core_Config::singleton();
    $loginURL = $config->userFrameworkBaseURL;
    $loginURL = str_replace('administrator/', '', $loginURL);
    $loginURL .= 'index.php?option=com_users&view=login';

    //CRM-14872 append destination
    if ( !empty($destination) ) {
      $loginURL .= '&return='.urlencode(base64_encode($destination));
    }
    return $loginURL;
  }

  /**
   * @param $form
   */
  public function getLoginDestination(&$form) {
    $args = NULL;

    $id = $form->get('id');
    if ($id) {
      $args .= "&id=$id";
    }
    else {
      $gid = $form->get('gid');
      if ($gid) {
        $args .= "&gid=$gid";
      }
      else {
        // Setup Personal Campaign Page link uses pageId
        $pageId = $form->get('pageId');
        if ($pageId) {
          $component = $form->get('component');
          $args .= "&pageId=$pageId&component=$component&action=add";
        }
      }
    }

    $destination = NULL;
    if ($args) {
      // append destination so user is returned to form they came from after login
      $args = 'reset=1'.$args;
      $destination = CRM_Utils_System::url(CRM_Utils_System::currentPath(), $args, TRUE, NULL, TRUE, TRUE);
    }

    return $destination;
  }

  /**
   * Return default Site Settings
   *
   * @param $dir
   *
   * @return array array
   * - $url, (Joomla - non admin url)
   * - $siteName,
   * - $siteRoot
   */
  function getDefaultSiteSettings($dir){
    $config = CRM_Core_Config::singleton();
    $url = preg_replace(
      '|/administrator|',
      '',
      $config->userFrameworkBaseURL
    );
    $siteRoot = preg_replace(
      '|/media/civicrm/.*$|',
      '',
      $config->imageUploadDir
    );
    return array($url, NULL, $siteRoot);
  }

  /**
   * Get Url to view user record
   * @param integer $contactID Contact ID
   *
   * @return string
   */
  function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    $userRecordUrl = NULL;
    // if logged in user is super user, then he can view other users joomla profile
    if (JFactory::getUser()->authorise('core.admin')) {
      return CRM_Core_Config::singleton()->userFrameworkBaseURL . "index.php?option=com_users&view=user&task=user.edit&id=" . $uid;
    }
    elseif (CRM_Core_Session::singleton()->get('userID') == $contactID) {
      return CRM_Core_Config::singleton()->userFrameworkBaseURL . "index.php?option=com_admin&view=profile&layout=edit&id=" . $uid;
    }
  }

  /**
   * Is the current user permitted to add a user
   * @return bool
   */
  function checkPermissionAddUser() {
    if (JFactory::getUser()->authorise('core.create', 'com_users')) {
      return TRUE;
    }
  }

  /**
   * output code from error function
   * @param string $content
   */
  function outputError($content) {
    if (class_exists('JErrorPage')) {
      $error = new Exception($content);
      JErrorPage::render($error);
    }
    else if (class_exists('JError')) {
      JError::raiseError('CiviCRM-001', $content);
    }
    else {
      parent::outputError($content);
    }
  }
  
  /**
   * Append to coreResourcesList
   */
  function appendCoreResources(&$list) {
    $list[] = 'js/crm.joomla.js';
  }
}

