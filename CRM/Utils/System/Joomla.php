<?php
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
 */

/**
 * Joomla specific stuff goes here.
 */
class CRM_Utils_System_Joomla extends CRM_Utils_System_Base {
  /**
   * Class constructor.
   */
  public function __construct() {
    /**
     * deprecated property to check if this is a drupal install. The correct method is to have functions on the UF classes for all UF specific
     * functions and leave the codebase oblivious to the type of CMS
     * @deprecated
     * @var bool
     */
    $this->is_drupal = FALSE;
  }

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $mail) {
    $baseDir = JPATH_SITE;
    require_once $baseDir . '/components/com_users/models/registration.php';

    $userParams = JComponentHelper::getParams('com_users');
    $model = new UsersModelRegistration();
    $ufID = NULL;

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
    $values = array();
    $values['name'] = $fullname;
    $values['username'] = trim($params['cms_name']);
    $values['password1'] = $values['password2'] = $params['cms_pass'];
    $values['email1'] = $values['email2'] = trim($params[$mail]);

    $lang = JFactory::getLanguage();
    $lang->load('com_users', $baseDir);

    $register = $model->register($values);

    $ufID = JUserHelper::getUserId($values['username']);
    return $ufID;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $ufName) {
    $ufID = CRM_Utils_Type::escape($ufID, 'Integer');
    $ufName = CRM_Utils_Type::escape($ufName, 'String');

    $values = array();
    $user = JUser::getInstance($ufID);

    $values['email'] = $ufName;
    $user->bind($values);

    $user->save();
  }

  /**
   * Check if username and email exists in the Joomla db.
   *
   * @param array $params
   *   Array of name and mail values.
   * @param array $errors
   *   Array of errors.
   * @param string $emailName
   *   Field label for the 'email'.
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();

    $dao = new CRM_Core_DAO();
    $name = $dao->escape(CRM_Utils_Array::value('name', $params));
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

    $row = array();
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
        $errors[$emailName] = ts('The email address %1 already has an account associated with it. <a href="%2">Have you forgotten your password?</a>',
          array(1 => $email, 2 => $resetUrl)
        );
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign('pageTitle', $pageTitle);

    $document = JFactory::getDocument();
    $document->setTitle($title);
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadCrumbs) {
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
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($string = NULL) {
    if ($string) {
      $document = JFactory::getDocument();
      $document->addCustomTag($string);
    }
  }

  /**
   * @inheritDoc
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
   * @inheritDoc
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
   * @inheritDoc
   */
  public function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    $separator = '&';
    $Itemid = '';
    $script = '';
    $path = CRM_Utils_String::stripPathChars($path);

    if ($config->userFrameworkFrontend) {
      $script = 'index.php';
      if (JRequest::getVar("Itemid")) {
        $Itemid = "{$separator}Itemid=" . JRequest::getVar("Itemid");
      }
    }

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
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
      }
      else {
        $jversion = new JVersion();
        $joomlaVersion = $jversion->getShortVersion();
      }

      if (version_compare($joomlaVersion, '1.6') >= 0) {
        $url = str_replace('/index.php', '/administrator/index.php', $url);
      }
    }
    return $url;
  }

  /**
   * Set the email address of the user.
   *
   * @param object $user
   *   Handle to the user object.
   */
  public function setEmail(&$user) {
    global $database;
    $query = $db->getQuery(TRUE);
    $query->select($db->quoteName('email'))
          ->from($db->quoteName('#__users'))
          ->where($db->quoteName('id') . ' = ' . $user->id);
    $database->setQuery($query);
    $user->email = $database->loadResult();
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
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
    if (!defined('JVERSION')) {
      require $joomlaBase . '/libraries/cms/version/version.php';
      $jversion = new JVersion();
      define('JVERSION', $jversion->getShortVersion());
    }

    if (!empty($row)) {
      $dbPassword = $row->password;
      $dbId = $row->id;
      $dbEmail = $row->email;

      if (version_compare(JVERSION, '2.5.18', 'lt') ||
        (version_compare(JVERSION, '3.0', 'ge') && version_compare(JVERSION, '3.2.1', 'lt'))
      ) {
        // now check password
        list($hash, $salt) = explode(':', $dbPassword);
        $cryptpass = md5($password . $salt);
        if ($hash != $cryptpass) {
          return FALSE;
        }
      }
      else {
        if (!JUserHelper::verifyPassword($password, $dbPassword, $dbId)) {
          return FALSE;
        }

        //include additional files required by Joomla 3.2.1+
        if (version_compare(JVERSION, '3.2.1', 'ge')) {
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
   * Set a init session with user object.
   *
   * @param array $data
   *   Array with user specific data.
   */
  public function setUserSession($data) {
    list($userID, $ufID) = $data;
    $user = new JUser($ufID);
    $session = JFactory::getSession();
    $session->set('user', $user);

    parent::setUserSession($data);
  }

  /**
   * FIXME: Do something
   *
   * @param string $message
   */
  public function setMessage($message) {
  }

  /**
   * FIXME: Do something
   *
   * @param \obj $user
   *
   * @return bool
   */
  public function loadUser($user) {
    return TRUE;
  }

  /**
   * FIXME: Use CMS-native approach
   */
  public function permissionDenied() {
    CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
  }

  /**
   * @inheritDoc
   */
  public function logout() {
    session_destroy();
    CRM_Utils_System::setHttpHeader("Location", "index.php");
  }

  /**
   * @inheritDoc
   */
  public function getUFLocale() {
    if (defined('_JEXEC')) {
      $conf = JFactory::getConfig();
      $locale = $conf->get('language');
      return str_replace('-', '_', $locale);
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setUFLocale($civicrm_language) {
    // TODO
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getVersion() {
    if (class_exists('JVersion')) {
      $version = new JVersion();
      return $version->getShortVersion();
    }
    else {
      return 'Unknown';
    }
  }

  /**
   * Load joomla bootstrap.
   *
   * @param array $params
   *   with uid or name and password.
   * @param bool $loadUser
   *   load cms user?.
   * @param bool|\throw $throwError throw error on failure?
   * @param null $realPath
   * @param bool $loadDefines
   *
   * @return bool
   */
  public function loadBootStrap($params = array(), $loadUser = TRUE, $throwError = TRUE, $realPath = NULL, $loadDefines = TRUE) {
    // Setup the base path related constant.
    $joomlaBase = dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))));

    // load BootStrap here if needed
    // We are a valid Joomla entry point.
    if (!defined('_JEXEC') && $loadDefines) {
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
    if (!defined('JVERSION')) {
      require $joomlaBase . '/libraries/cms/version/version.php';
      $jversion = new JVersion();
      define('JVERSION', $jversion->getShortVersion());
    }

    if (version_compare(JVERSION, '3.0', 'lt')) {
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
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    $user = JFactory::getUser();
    return ($user->guest) ? FALSE : TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUfID() {
    $user = JFactory::getUser();
    return ($user->guest) ? NULL : $user->id;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUniqueIdentifier() {
    $user = JFactory::getUser();
    return $this->getUniqueIdentifierFromUserObject($user);
  }

  /**
   * @inheritDoc
   */
  public function getUserIDFromUserObject($user) {
    return !empty($user->id) ? $user->id : NULL;
  }

  /**
   * @inheritDoc
   */
  public function getUniqueIdentifierFromUserObject($user) {
    return ($user->guest) ? NULL : $user->email;
  }

  /**
   * Get a list of all installed modules, including enabled and disabled ones
   *
   * @return array
   *   CRM_Core_Module
   */
  public function getModules() {
    $result = array();

    $db = JFactory::getDbo();
    $query = $db->getQuery(TRUE);
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
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $config = CRM_Core_Config::singleton();
    $loginURL = $config->userFrameworkBaseURL;
    $loginURL = str_replace('administrator/', '', $loginURL);
    $loginURL .= 'index.php?option=com_users&view=login';

    //CRM-14872 append destination
    if (!empty($destination)) {
      $loginURL .= '&return=' . urlencode(base64_encode($destination));
    }
    return $loginURL;
  }

  /**
   * @inheritDoc
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
      $args = 'reset=1' . $args;
      $destination = CRM_Utils_System::url(CRM_Utils_System::currentPath(), $args, TRUE, NULL, FALSE, TRUE);
    }

    return $destination;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSiteSettings($dir) {
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
   * @inheritDoc
   */
  public function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    $userRecordUrl = NULL;
    // if logged in user has user edit access, then allow link to other users joomla profile
    if (JFactory::getUser()->authorise('core.edit', 'com_users')) {
      return CRM_Core_Config::singleton()->userFrameworkBaseURL . "index.php?option=com_users&view=user&task=user.edit&id=" . $uid;
    }
    elseif (CRM_Core_Session::singleton()->get('userID') == $contactID) {
      return CRM_Core_Config::singleton()->userFrameworkBaseURL . "index.php?option=com_admin&view=profile&layout=edit&id=" . $uid;
    }
  }

  /**
   * @inheritDoc
   */
  public function checkPermissionAddUser() {
    if (JFactory::getUser()->authorise('core.create', 'com_users')) {
      return TRUE;
    }
  }

  /**
   * Output code from error function.
   * @param string $content
   */
  public function outputError($content) {
    if (class_exists('JErrorPage')) {
      $error = new Exception($content);
      JErrorPage::render($error);
    }
    elseif (class_exists('JError')) {
      JError::raiseError('CiviCRM-001', $content);
    }
    else {
      parent::outputError($content);
    }
  }

  /**
   * Append Joomla js to coreResourcesList.
   *
   * @param array $list
   */
  public function appendCoreResources(&$list) {
    $list[] = 'js/crm.joomla.js';
  }

  /**
   * @inheritDoc
   */
  public function synchronizeUsers() {
    $config = CRM_Core_Config::singleton();
    if (PHP_SAPI != 'cli') {
      set_time_limit(300);
    }
    $id = 'id';
    $mail = 'email';
    $name = 'name';

    $JUserTable = &JTable::getInstance('User', 'JTable');

    $db = $JUserTable->getDbo();
    $query = $db->getQuery(TRUE);
    $query->select($id . ', ' . $mail . ', ' . $name);
    $query->from($JUserTable->getTableName());
    $query->where($mail != '');

    $db->setQuery($query);
    $users = $db->loadObjectList();

    $user = new StdClass();
    $uf = $config->userFramework;
    $contactCount = 0;
    $contactCreated = 0;
    $contactMatching = 0;
    for ($i = 0; $i < count($users); $i++) {
      $user->$id = $users[$i]->$id;
      $user->$mail = $users[$i]->$mail;
      $user->$name = $users[$i]->$name;
      $contactCount++;
      if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($user,
        $users[$i]->$id,
        $users[$i]->$mail,
        $uf,
        1,
        'Individual',
        TRUE
      )
      ) {
        $contactCreated++;
      }
      else {
        $contactMatching++;
      }
      if (is_object($match)) {
        $match->free();
      }
    }

    return array(
      'contactCount' => $contactCount,
      'contactMatching' => $contactMatching,
      'contactCreated' => $contactCreated,
    );
  }

}
