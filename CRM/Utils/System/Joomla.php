<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * @internal
   * @return bool
   */
  public function isLoaded(): bool {
    return class_exists('JFactory') || class_exists('\Joomla\CMS\Factory');
  }

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $mailParam) {
    $baseDir = JPATH_SITE;
    $userParams = JComponentHelper::getParams('com_users');

    if (version_compare(JVERSION, '4.0.0', 'ge')) {
      $factoryClassName = $this->factoryClassName();

      $model = $factoryClassName::getApplication()->bootComponent('com_users')->getMVCFactory()->createModel('Registration', 'Site');
      $model->set('data', new \stdClass());
    }
    else {
      require_once $baseDir . '/components/com_users/models/registration.php';
      $model = new UsersModelRegistration();
    }

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
    $values = [];
    $values['name'] = $fullname;
    $values['username'] = trim($params['cms_name']);
    $values['password1'] = $values['password2'] = $params['cms_pass'];
    $values['email1'] = $values['email2'] = trim($params[$mailParam]);
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $lang = JFactory::getLanguage();
    }
    else {
      $lang = $factoryClassName::getApplication()->getLanguage();
    }
    $lang->load('com_users', $baseDir);

    $register = $model->register($values);
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $ufID = JUserHelper::getUserId($values['username']);
      JUserHelper::addUserToGroup($ufID, $userType);
    }
    else {
      $ufID = \Joomla\CMS\User\UserHelper::getUserId($values['username']);
      \Joomla\CMS\User\UserHelper::addUserToGroup($ufID, $userType);
    }
    return $ufID;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $ufName) {
    $ufID = CRM_Utils_Type::escape($ufID, 'Integer');
    $ufName = CRM_Utils_Type::escape($ufName, 'String');

    $values = [];
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $user = JUser::getInstance($ufID);
    }
    else {
      $factoryClassName = $this->factoryClassName();
      $user = $factoryClassName::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($ufID);
    }

    $values['email'] = $ufName;
    $user->bind($values);

    $user->save();
  }

  /**
   * @inheritdoc
   */
  public function getEmailFieldName(CRM_Core_Form $form, array $fields):string {
    $emailName = '';
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();
    if (array_key_exists("email-{$billingLocationTypeID}", $fields)) {
      // this is a transaction related page
      $emailName = 'email-' . $billingLocationTypeID;
    }
    else {
      // find the email field in a profile page
      foreach ($fields as $name => $dontCare) {
        if (str_starts_with($name, 'email')) {
          $emailName = $name;
          break;
        }
      }
    }

    return $emailName;
  }

  /**
   * @inheritdoc
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();

    $name = $params['name'] ?? NULL;
    $email = $params['mail'] ?? NULL;
    //don't allow the special characters and min. username length is two
    //regex \\ to match a single backslash would become '/\\\\/'
    $isNotValid = (bool) preg_match('/[\<|\>|\"|\'|\%|\;|\(|\)|\&|\\\\|\/]/im', $name);
    if ($isNotValid || strlen($name) < 2) {
      $errors['cms_name'] = ts('Your username contains invalid characters or is too short');
    }

    if (version_compare(JVERSION, '4.0', 'lt')) {
      $JUserTable = &JTable::getInstance('User', 'JTable');
    }
    else {
      $factoryClassName = $this->factoryClassName();
      $db = $factoryClassName::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
      $JUserTable = new \Joomla\CMS\Table\User($db);
    }
    $db = $JUserTable->getDbo();
    $query = $db->getQuery(TRUE);
    $query->select('username, email');
    $query->from($JUserTable->getTableName());

    // LOWER in query below roughly translates to 'hurt my database without deriving any benefit' See CRM-19811.
    $query->where('(LOWER(username) = LOWER(' . $db->quote($name) . ')) OR (LOWER(email) = LOWER(' . $db->quote($email) . '))');
    $db->setQuery($query, 0, 10);
    $users = $db->loadAssocList();

    $row = [];
    if (count($users)) {
      $row = $users[0];
    }

    if (!empty($row)) {
      $dbName = $row['username'] ?? NULL;
      $dbEmail = $row['email'] ?? NULL;
      if (strtolower($dbName) == strtolower($name)) {
        $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.',
          [1 => $name]
        );
      }
      if (strtolower($dbEmail) == strtolower($email)) {
        $resetUrl = str_replace('administrator/', '', $config->userFrameworkBaseURL) . 'index.php?option=com_users&view=reset';
        $errors[$emailName] = ts('The email address %1 already has an account associated with it. <a href="%2">Have you forgotten your password?</a>',
          [1 => $email, 2 => $resetUrl]
        );
      }
    }
  }

  /**
   * @inheritdoc
   *
   * Joomla has a very slightly different main template
   * from the shared CMSPrint.tpl
   *
   * @todo can we merge these and do away with this
   * override? might need to update the breadcrumbs
   * function below to match the others
   */
  public static function getContentTemplate($print = 0): string {
    // I fear some callers of this function still pass FALSE rather than int
    if (!$print) {
      return 'CRM/common/joomla.tpl';
    }
    return parent::getContentTemplate($print);
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

    $document = $this->getJoomlaDocument();
    $document->setTitle($title);
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadCrumbs) {
    $template = CRM_Core_Smarty::singleton();
    $bc = $template->getTemplateVars('breadcrumb');

    if (is_array($breadCrumbs)) {
      foreach ($breadCrumbs as $crumbs) {
        if (stripos($crumbs['url'], 'id%%')) {
          $args = ['cid', 'mid'];
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
    $template->assign('breadcrumb', $bc);
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
      $document = $this->getJoomlaDocument();
      $document->addCustomTag($string);
    }
  }

  /**
   * @inheritDoc
   */
  public function addStyleUrl($url, $region) {
    if ($region == 'html-header') {
      $document = $this->getJoomlaDocument();
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
      $document = $this->getJoomlaDocument();
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

      // Get Itemid using JInput::get()
      $factoryClassName = $this->factoryClassName();
      $input = $factoryClassName::getApplication()->getInput();
      $itemIdNum = $input->get("Itemid");
      if ($itemIdNum && (!str_contains($path, 'civicrm/payment/ipn'))) {
        $Itemid = "{$separator}Itemid=" . $itemIdNum;
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
    if ($frontend) {
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
        $joomlaBase = self::getBasePath();
        $joomlaVersion = $this->getJVersion($joomlaBase);
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
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $JUserTable = &JTable::getInstance('User', 'JTable');
    }
    else {
      $factoryClassName = $this->factoryClassName();
      $db = $factoryClassName::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
      $JUserTable = new \Joomla\CMS\Table\User($db);
    }
    $db = $JUserTable->getDbo();
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

    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

    $config = CRM_Core_Config::singleton();
    $user = NULL;

    if ($loadCMSBootstrap) {
      $bootStrapParams = [];
      if ($name && $password) {
        $bootStrapParams = [
          'name' => $name,
          'pass' => $password,
        ];
      }
      CRM_Utils_System::loadBootStrap($bootStrapParams, TRUE, TRUE, FALSE);
    }

    if (version_compare(JVERSION, '4.0', 'lt')) {
      jimport('joomla.application.component.helper');
      jimport('joomla.database.table');
      jimport('joomla.user.helper');
      $JUserTable = &JTable::getInstance('User', 'JTable');
    }
    else {
      $factoryClassName = $this->factoryClassName();
      $db = $factoryClassName::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
      $JUserTable = new \Joomla\CMS\Table\User($db);
    }

    $db = $JUserTable->getDbo();
    $query = $db->getQuery(TRUE);
    $query->select('id, name, username, email, password');
    $query->from($JUserTable->getTableName());
    $query->where('(LOWER(username) = LOWER(' . $db->quote($name) . ')) AND (block = 0)');
    $db->setQuery($query, 0, 0);
    $users = $db->loadObjectList();

    $row = [];
    if (count($users)) {
      $row = $users[0];
    }

    $joomlaBase = self::getBasePath();
    self::getJVersion($joomlaBase);

    if (!empty($row)) {
      $dbPassword = $row->password;
      $dbId = $row->id;
      $dbEmail = $row->email;

      if (version_compare(JVERSION, '2.5.18', 'lt') ||
        (version_compare(JVERSION, '3.0', 'ge') && version_compare(JVERSION, '3.2.1', 'lt'))
      ) {
        // now check password
        [$hash, $salt] = explode(':', $dbPassword);
        $cryptpass = md5($password . $salt);
        if ($hash != $cryptpass) {
          return FALSE;
        }
      }
      else {
        if (version_compare(JVERSION, '4.0', 'lt') && !JUserHelper::verifyPassword($password, $dbPassword, $dbId)) {
          return FALSE;
        }
        elseif (!\Joomla\CMS\User\UserHelper::verifyPassword($password, $dbPassword, $dbId)) {
          return FALSE;
        }

        if (version_compare(JVERSION, '3.8.0', 'ge')) {
          jimport('joomla.application.helper');
          jimport('joomla.application.cms');
          jimport('joomla.application.administrator');
        }
        //include additional files required by Joomla 3.2.1+
        elseif (version_compare(JVERSION, '3.2.1', 'ge')) {
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
      return [$contactID, $dbId, mt_rand()];
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
    [$userID, $ufID] = $data;
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $user = new JUser($ufID);
    }
    else {
      $user = new \Joomla\CMS\User\User($ufID);
    }
    $factoryClassName = $this->factoryClassName();
    $session = $factoryClassName::getApplication()->getSession();
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

  public function factoryClassName(): string {
    if (version_compare(JVERSION, '4.0.0', 'ge')) {
      return '\Joomla\CMS\Factory';
    }
    return 'JFactory';
  }

  /**
   * @param \string $username
   * @param \string $password
   *
   * @return bool
   */
  public function loadUser($username, $password = NULL) {
    $factoryClassName = self::factoryClassName();
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $uid = JUserHelper::getUserId($username);
    }
    else {
      $uid = \Joomla\CMS\User\UserHelper::getUserId($username);
    }
    if (empty($uid)) {
      return FALSE;
    }
    $contactID = CRM_Core_BAO_UFMatch::getContactId($uid);

    if (!empty($password)) {
      if (version_compare(JVERSION, '4.0.0', 'ge')) {
        $this->loadJoomlaApplication('site');
        $instance = $factoryClassName::getApplication();
      }
      else {
        $instance = $factoryClassName::getApplication('site');
      }
      $params = [
        'username' => $username,
        'password' => $password,
      ];
      //perform the login action
      $instance->login($params);
    }

    // Save details in Joomla session
    if (version_compare(JVERSION, '4.0.0', 'ge')) {
      $user = $factoryClassName::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($uid);
    }
    else {
      $user = $factoryClassName::getUser($uid);
    }
    $jsession = $factoryClassName::getApplication()->getSession();
    $jsession->set('user', $user);

    // Save details in Civi session
    $session = CRM_Core_Session::singleton();
    $session->set('ufID', $uid);
    $session->set('userID', $contactID);
    return TRUE;
  }

  public function getUfId($username) {
    if (version_compare(JVERSION, '4.0', 'lt')) {
      jimport('joomla.user.helper');
      $uid = JUserHelper::getUserId($username);
    }
    else {
      $uid = \Joomla\CMS\User\UserHelper::getUserId($username);
    }
    return empty($uid) ? NULL : $uid;
  }

  /**
   * FIXME: Use CMS-native approach
   * @throws \CRM_Core_Exception.
   */
  public function permissionDenied() {
    throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
  }

  /**
   * @inheritDoc
   */
  public function postLogoutUrl(): string {
    return "/index.php";
  }

  /**
   * @inheritDoc
   */
  public function getUFLocale() {
    if (defined('_JEXEC')) {
      $factoryClassName = $this->factoryClassName();
      $conf = $factoryClassName::getApplication()->getConfig();
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
    elseif (class_exists('Version')) {
      $version = new Version();
      return $version->getShortVersion();
    }
    else {
      return 'Unknown';
    }
  }

  public function getJVersion($joomlaBase) {
    // Files may be in different places depending on Joomla version
    if (!defined('JVERSION')) {
      // Joomla 3.8.0+
      $versionPhp = $joomlaBase . '/libraries/src/Version.php';
      if (!file_exists($versionPhp)) {
        // Joomla < 3.8.0
        $versionPhp = $joomlaBase . '/libraries/cms/version/version.php';
      }
      require $versionPhp;
      $class = 'jVersion';
      if (!class_exists('jVersion')) {
        $class = 'Version';
      }
      $jversion = new $class();
      define('JVERSION', $jversion->getShortVersion());
    }
  }

  protected function getJoomlaDocument() {
    $factoryClassName = $this->factoryClassName();
    $document = $factoryClassName::getApplication()->getDocument();
    return $document;
  }

  /**
   * Setup the base path related constant.
   * @return mixed
   */
  public function getBasePath() {
    global $civicrm_root;
    $joomlaPath = explode(DIRECTORY_SEPARATOR . 'administrator', $civicrm_root);
    $joomlaBase = $joomlaPath[0];
    return $joomlaBase;
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
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL, $loadDefines = TRUE) {
    $joomlaBase = self::getBasePath();

    // load BootStrap here if needed
    // We are a valid Joomla entry point.
    // dev/core#1384 Use DS to ensure a correct JPATH_BASE in Windows
    if (!defined('_JEXEC') && $loadDefines) {
      define('_JEXEC', 1);
      define('DS', DIRECTORY_SEPARATOR);
      define('JPATH_BASE', $joomlaBase . DS . 'administrator');
      require $joomlaBase . '/administrator/includes/defines.php';
    }

    // Get the framework.
    if (file_exists($joomlaBase . '/libraries/import.legacy.php') && !file_exists($joomlaBase . '/libraries/bootstrap.php')) {
      require $joomlaBase . '/libraries/import.legacy.php';
    }

    if (!file_exists($joomlaBase . '/libraries/bootstrap.php')) {
      require $joomlaBase . '/libraries/cms.php';
    }
    else {
      require $joomlaBase . '/libraries/bootstrap.php';
      require_once $joomlaBase . '/includes/framework.php';
    }
    self::getJVersion($joomlaBase);

    if (version_compare(JVERSION, '3.8', 'lt')) {
      require $joomlaBase . '/libraries/import.php';
      require $joomlaBase . '/libraries/joomla/event/dispatcher.php';
    }

    require_once $joomlaBase . '/configuration.php';

    if (version_compare(JVERSION, '3.0', 'lt')) {
      require $joomlaBase . '/libraries/joomla/environment/uri.php';
      require $joomlaBase . '/libraries/joomla/application/component/helper.php';
    }
    elseif (version_compare(JVERSION, '3.8', 'lt')) {
      jimport('joomla.environment.uri');
    }

    if (version_compare(JVERSION, '3.8', 'lt')) {
      jimport('joomla.application.cli');
    }

    if (!defined('JDEBUG')) {
      define('JDEBUG', FALSE);
    }

    if (version_compare(JVERSION, '4.0', '>=')) {
      if (PHP_SAPI == 'cli') {
        $this->loadJoomlaApplication('cli');
      }
      else {
        $this->loadJoomlaApplication('admin');
      }
    }

    // Set timezone for Joomla on Cron
    $factoryClassName = $this->factoryClassName();
    $config = $factoryClassName::getApplication()->getConfig();
    $timezone = $config->get('offset');
    if ($timezone) {
      $this->setTimeZone($timezone);
    }

    // CRM-14281 Joomla wasn't available during bootstrap, so hook_civicrm_config never executes.
    $config = CRM_Core_Config::singleton();
    CRM_Utils_Hook::config($config, ['uf' => TRUE]);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    $factoryClassName = $this->factoryClassName();
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $user = $factoryClassName::getUser();
    }
    else {
      $user = $factoryClassName::getApplication()->getIdentity();
    }
    return !$user->guest;
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $userParams = JComponentHelper::getParams('com_users');
    }
    else {
      $userParams = \Joomla\CMS\Component\ComponentHelper::getParams('com_users');
    }
    if (!$userParams->get('allowUserRegistration')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isPasswordUserGenerated() {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUfID() {
    $factoryClassName = $this->factoryClassName();
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $user = $factoryClassName::getUser();
    }
    else {
      $user = $factoryClassName::getApplication()->getIdentity();
    }
    return ($user->guest) ? NULL : $user->id;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUniqueIdentifier() {
    $factoryClassName = $this->factoryClassName();
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $user = $factoryClassName::getUser();
    }
    else {
      $user = $factoryClassName::getApplication()->getIdentity();
    }
    return $this->getUniqueIdentifierFromUserObject($user);
  }

  /**
   * @inheritDoc
   */
  public function getUser($contactID) {
    $user_details = parent::getUser($contactID);
    $factoryClassName = $this->factoryClassName();
    if (version_compare(JVERSION, '4.0.0', 'ge')) {
      $user = $factoryClassName::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($user_details['id']);
    }
    else {
      $user = $factoryClassName::getUser($user_details['id']);
    }
    $user_details['name'] = $user->name;
    return $user_details;
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
   * @inheritDoc
   */
  public function getTimeZoneString() {
    $factoryClassName = $this->factoryClassName();
    $timezone = $factoryClassName::getApplication()->getConfig()->get('offset');
    return !$timezone ? date_default_timezone_get() : $timezone;
  }

  /**
   * Get a list of all installed modules, including enabled and disabled ones
   *
   * @return array
   *   CRM_Core_Module
   */
  public function getModules() {
    $result = [];
    $factoryClassName = $this->factoryClassName();
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $db = $factoryClassName::getDbo();
    }
    else {
      $db = $factoryClassName::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
    }
    $query = $db->getQuery(TRUE);
    $query->select('type, folder, element, enabled')
      ->from('#__extensions')
      ->where('type =' . $db->Quote('plugin'));
    $plugins = $db->setQuery($query)->loadAssocList();
    foreach ($plugins as $plugin) {
      // question: is the folder really a critical part of the plugin's name?
      $name = implode('.', ['joomla', $plugin['type'], $plugin['folder'], $plugin['element']]);
      $result[] = new CRM_Core_Module($name, !empty($plugin['enabled']));
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
   * Determine the location of the CMS root.
   *
   * @return string|NULL
   *   local file system path to CMS root, or NULL if it cannot be determined
   */
  public function cmsRootPath() {
    global $civicrm_paths;
    if (!empty($civicrm_paths['cms.root']['path'])) {
      return $civicrm_paths['cms.root']['path'];
    }

    [$url, $siteName, $siteRoot] = $this->getDefaultSiteSettings();
    if (file_exists("$siteRoot/administrator/index.php")) {
      return $siteRoot;
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSiteSettings($dir = NULL) {
    $config = CRM_Core_Config::singleton();
    $url = preg_replace(
      '|/administrator|',
      '',
      $config->userFrameworkBaseURL
    );
    // CRM-19453 revisited. Under Windows, the pattern wasn't recognised.
    // This is the original pattern, but it doesn't work under Windows.
    // By setting the pattern to the one used before the change first and only
    // changing it means that the change code only affects Windows users.
    $pattern = '|/media/civicrm/.*$|';
    if (DIRECTORY_SEPARATOR == '\\') {
      // This regular expression will handle Windows as well as Linux
      // and any combination of forward and back slashes in directory
      // separators.  We only apply it if the directory separator is the one
      // used by Windows.
      $pattern = '|[\\\\/]media[\\\\/]civicrm[\\\\/].*$|';
    }
    $siteRoot = preg_replace(
      $pattern,
      '',
      $config->imageUploadDir
    );
    return [$url, NULL, $siteRoot];
  }

  /**
   * @inheritDoc
   */
  public function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    $userRecordUrl = NULL;
    $factoryClassName = $this->factoryClassName();
    // if logged in user has user edit access, then allow link to other users joomla profile
    if ($factoryClassName::getApplication()->getIdentity()->authorise('core.edit', 'com_users')) {
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
    $factoryClassName = $this->factoryClassName();
    if ($factoryClassName::getApplication()->getIdentity()->authorise('core.create', 'com_users')) {
      return TRUE;
    }
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

    if (version_compare(JVERSION, '4.0', 'lt')) {
      $JUserTable = &JTable::getInstance('User', 'JTable');
    }
    else {
      $factoryClassName = $this->factoryClassName();
      $db = $factoryClassName::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
      $JUserTable = new \Joomla\CMS\Table\User($db);
    }
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
    }

    return [
      'contactCount' => $contactCount,
      'contactMatching' => $contactMatching,
      'contactCreated' => $contactCreated,
    ];
  }

  /**
   * Determine the location of the CiviCRM source tree.
   *
   * FIXME:
   *  1. This was pulled out from a bigger function. It should be split
   *     into even smaller pieces and marked abstract.
   *  2. This would be easier to compute by a calling a CMS API, but
   *     for whatever reason we take the hard way.
   *
   * @return array
   *   - url: string. ex: "http://example.com/sites/all/modules/civicrm"
   *   - path: string. ex: "/var/www/sites/all/modules/civicrm"
   */
  public function getCiviSourceStorage():array {
    global $civicrm_root;
    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }
    $baseURL = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');
    if (CRM_Utils_System::isSSL()) {
      $baseURL = str_replace('http://', 'https://', $baseURL);
    }

    // For Joomla CiviCRM Core files always live within the admistrator folder and $base_url is different on the frontend compared to the backend.
    if (!str_contains($baseURL, 'administrator')) {
      $userFrameworkResourceURL = $baseURL . "administrator/components/com_civicrm/civicrm/";
    }
    else {
      $userFrameworkResourceURL = $baseURL . "components/com_civicrm/civicrm/";
    }

    return [
      'url' => CRM_Utils_File::addTrailingSlash($userFrameworkResourceURL, '/'),
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    $ufAccessURL = '';
    $jAccessParams = '';
    $config = CRM_Core_Config::singleton();
    //condition based on Joomla version; <= 2.5 uses modal window; >= 3.0 uses full page with return value
    if (version_compare(JVERSION, '3.0', 'lt')) {
      JHTML::_('behavior.modal');
      $ufAccessURL = $config->userFrameworkBaseURL . 'index.php?option=com_config&view=component&component=com_civicrm&tmpl=component';
      $jAccessParams = 'rel="{handler: \'iframe\', size: {x: 875, y: 550}, onClose: function() {}}" class="modal"';
    }
    else {
      $uri = (string) JUri::getInstance();
      $return = urlencode(base64_encode($uri));
      $ufAccessURL = $config->userFrameworkBaseURL . 'index.php?option=com_config&view=component&component=com_civicrm&return=' . $return;
    }
    return [
      'ufAccessURL' => $ufAccessURL,
      'jAccessParams' => $jAccessParams,
    ];
  }

  /**
   * @inheritdoc
   */
  public function getContactDetailsFromUser($uf_match):array {
    $contactParameters = [];
    $user = $uf_match['user'];
    $contactParameters['email'] = $user->email;
    if ($user->name) {
      CRM_Utils_String::extractName($user->name, $contactParameters);
    }

    return $contactParameters;
  }

  /**
   * @inheritdoc
   */
  public function modifyStandaloneProfile($profile, $params):string {
    $urlReplaceWith = 'civicrm/profile/create&amp;gid=' . $params['gid'] . '&amp;reset=1';
    $profile = str_replace('civicrm/admin/uf/group', $urlReplaceWith, $profile);

    // FIXME: (CRM-3587) hack to make standalone profile work
    // in Joomla without administrator login.
    $profile = str_replace('/administrator/', '/index.php', $profile);
    return $profile;
  }

  protected function loadJoomlaApplication(string $applicationName) {
    // Boot the DI container
    if ($applicationName === 'site') {
      $applicationClass = \Joomla\CMS\Application\SiteApplication::class;
      $session = 'session.web.site';
    }
    elseif ($applicationName == 'admin') {
      $applicationClass = \Joomla\CMS\Application\AdministratorApplication::class;
      $session = 'session.web.administrator';
    }
    else {
      $applicationClass = \Joomla\CMS\Application\ConsoleApplication::class;
      $session = 'session.cli';
    }
    $container = \Joomla\CMS\Factory::getContainer();
    /*
     * Alias the session service keys to the web session service as that is the primary session backend for this application
     *
     * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
     * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
     * deprecated to be removed when the class name alias is removed as well.
     */
    $container->alias('session', $session)
      ->alias('JSession', $session)
      ->alias(\Joomla\CMS\Session\Session::class, $session)
      ->alias(\Joomla\Session\Session::class, $session)
      ->alias(\Joomla\Session\SessionInterface::class, $session);
    // Instantiate the application.
    $app = $container->get($applicationClass);
    $app->createExtensionNamespaceMap();
    // Set the application as global app
    \Joomla\CMS\Factory::$application = $app;
  }

}
