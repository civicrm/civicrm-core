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
 * Config handles all the run time configuration changes that the system needs to deal with.
 * Typically we'll have different values for a user's sandbox, a qa sandbox and a production area.
 * The default values in general, should reflect production values (minimizes chances of screwing up)
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'Log.php';
require_once 'Mail.php';

require_once 'api/api.php';

/**
 * Class CRM_Core_Config
 */
class CRM_Core_Config extends CRM_Core_Config_Variables {
  ///
  /// BASE SYSTEM PROPERTIES (CIVICRM.SETTINGS.PHP)
  ///

  /**
   * the dsn of the database connection
   *
   * @var string
   */
  public $dsn;

  /**
   * the name of user framework
   *
   * @var string
   */
  public $userFramework = 'Drupal';

  /**
   * the name of user framework url variable name
   *
   * @var string
   */
  public $userFrameworkURLVar = 'q';

  /**
   * the dsn of the database connection for user framework
   *
   * @var string
   */
  public $userFrameworkDSN = NULL;

  /**
   * The connector module for the CMS/UF
   * @todo Introduce an interface.
   *
   * @var CRM_Utils_System_Base
   */
  public $userSystem = NULL;

  /**
   * @var CRM_Core_Permission_Base
   */
  public $userPermissionClass;

  /**
   * The root directory where Smarty should store compiled files
   *
   * @var string
   */
  public $templateCompileDir = './templates_c/en_US/';

  /**
   * @var string
   */
  public $configAndLogDir = NULL;

  // END: BASE SYSTEM PROPERTIES (CIVICRM.SETTINGS.PHP)

  ///
  /// BEGIN HELPER CLASS PROPERTIES
  ///

  /**
   * are we initialized and in a proper state
   *
   * @var string
   */
  public $initialized = 0;

  /**
   * @var string
   */
  public $customPHPPathDir;

  /**
   * the factory class used to instantiate our DB objects
   *
   * @var string
   */
  private $DAOFactoryClass = 'CRM_Contact_DAO_Factory';

  /**
   * The handle to the log that we are using
   * @var object
   */
  private static $_log = NULL;

  /**
   * the handle on the mail handler that we are using
   *
   * @var object
   */
  public static $_mail = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var CRM_Core_Config
   */
  private static $_singleton = NULL;

  /**
   * @var CRM_Core_Component
   */
  public $componentRegistry = NULL;

  ///
  /// END HELPER CLASS PROPERTIES
  ///

  ///
  /// RUNTIME SET CLASS PROPERTIES
  ///

  /**
   * @var bool
   *   TRUE, if the call is CiviCRM.
   *   FALSE, if the call is from the CMS.
   */
  public $inCiviCRM = FALSE;

  ///
  /// END: RUNTIME SET CLASS PROPERTIES
  ///

  /**
   * @var string
   */
  public $recaptchaPublicKey;

  /**
   * The constructor. Sets domain id if defined, otherwise assumes
   * single instance installation.
   */
  private function __construct() {
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param $loadFromDB boolean  whether to load from the database
   * @param $force      boolean  whether to force a reconstruction
   *
   * @return CRM_Core_Config
   * @static
   */
  static function &singleton($loadFromDB = TRUE, $force = FALSE) {
    if (self::$_singleton === NULL || $force) {
      // goto a simple error handler
      $GLOBALS['civicrm_default_error_scope'] = CRM_Core_TemporaryErrorScope::create(array('CRM_Core_Error', 'handle'));
      $errorScope = CRM_Core_TemporaryErrorScope::create(array('CRM_Core_Error', 'simpleHandler'));

      // lets ensure we set E_DEPRECATED to minimize errors
      // CRM-6327
      if (defined('E_DEPRECATED')) {
        error_reporting(error_reporting() & ~E_DEPRECATED);
      }

      // first, attempt to get configuration object from cache
      $cache = CRM_Utils_Cache::singleton();
      self::$_singleton = $cache->get('CRM_Core_Config' . CRM_Core_Config::domainID());
      // if not in cache, fire off config construction
      if (!self::$_singleton) {
        self::$_singleton = new CRM_Core_Config;
        self::$_singleton->_initialize($loadFromDB);

        //initialize variables. for gencode we cannot load from the
        //db since the db might not be initialized
        if ($loadFromDB) {
          // initialize stuff from the settings file
          self::$_singleton->setCoreVariables();

          self::$_singleton->_initVariables();

          // I don't think we need to do this twice
          // however just keeping this commented for now in 4.4
          // in case we hit any issues - CRM-13064
          // We can safely delete this once we release 4.4.4
          // self::$_singleton->setCoreVariables();
        }
        $cache->set('CRM_Core_Config' . CRM_Core_Config::domainID(), self::$_singleton);
      }
      else {
        // we retrieve the object from memcache, so we now initialize the objects
        self::$_singleton->_initialize($loadFromDB);

        // CRM-9803, NYSS-4822
        // this causes various settings to be reset and hence we should
        // only use the config object that we retrieved from memcache
      }

      self::$_singleton->initialized = 1;

      if (isset(self::$_singleton->customPHPPathDir) &&
        self::$_singleton->customPHPPathDir
      ) {
        $include_path = self::$_singleton->customPHPPathDir . PATH_SEPARATOR . get_include_path();
        set_include_path($include_path);
      }

      // set the callback at the very very end, to avoid an infinite loop
      // set the error callback
      unset($errorScope);

      // call the hook so other modules can add to the config
      // again doing this at the very very end
      CRM_Utils_Hook::config(self::$_singleton);

      // make sure session is always initialised
      $session = CRM_Core_Session::singleton();

      // for logging purposes, pass the userID to the db
      $userID = $session->get('userID');
      if ($userID) {
        CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
          array(1 => array($userID, 'Integer'))
        );
      }

      // initialize authentication source
      self::$_singleton->initAuthSrc();
    }
    return self::$_singleton;
  }

  /**
   * @param string $userFramework
   *   One of 'Drupal', 'Joomla', etc.
   */
  private function _setUserFrameworkConfig($userFramework) {

    $this->userFrameworkClass = 'CRM_Utils_System_' . $userFramework;
    $this->userHookClass = 'CRM_Utils_Hook_' . $userFramework;
    $userPermissionClass = 'CRM_Core_Permission_' . $userFramework;
    $this->userPermissionClass = new $userPermissionClass();

    $class = $this->userFrameworkClass;
    // redundant with _initVariables
    $this->userSystem = new $class();

    if ($userFramework == 'Joomla') {
      $this->userFrameworkURLVar = 'task';
    }

    if (defined('CIVICRM_UF_BASEURL')) {
      $this->userFrameworkBaseURL = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');

      //format url for language negotiation, CRM-7803
      $this->userFrameworkBaseURL = CRM_Utils_System::languageNegotiationURL($this->userFrameworkBaseURL);

      if (CRM_Utils_System::isSSL()) {
        $this->userFrameworkBaseURL = str_replace('http://', 'https://',
          $this->userFrameworkBaseURL
        );
      }
    }

    if (defined('CIVICRM_UF_DSN')) {
      $this->userFrameworkDSN = CIVICRM_UF_DSN;
    }

    // this is dynamically figured out in the civicrm.settings.php file
    if (defined('CIVICRM_CLEANURL')) {
      $this->cleanURL = CIVICRM_CLEANURL;
    }
    else {
      $this->cleanURL = 0;
    }

    $this->userFrameworkVersion = $this->userSystem->getVersion();

    if ($userFramework == 'Joomla') {
      /** @var object|null $mainframe */
      global $mainframe;
      $dbprefix = $mainframe ? $mainframe->getCfg('dbprefix') : 'jos_';
      $this->userFrameworkUsersTableName = $dbprefix . 'users';
    }
    elseif ($userFramework == 'WordPress') {
      global $wpdb;
      $dbprefix = $wpdb ? $wpdb->prefix : '';
      $this->userFrameworkUsersTableName = $dbprefix . 'users';
    }
  }

  /**
   * Initializes the entire application.
   * Reads constants defined in civicrm.settings.php and
   * stores them in config properties.
   *
   * @param bool $loadFromDB
   */
  private function _initialize($loadFromDB = TRUE) {

    // following variables should be set in CiviCRM settings and
    // as crucial ones, are defined upon initialisation
    // instead of in CRM_Core_Config_Defaults
    if (defined('CIVICRM_DSN')) {
      $this->dsn = CIVICRM_DSN;
    }
    elseif ($loadFromDB) {
      // bypass when calling from gencode
      echo 'You need to define CIVICRM_DSN in civicrm.settings.php';
      exit();
    }

    if (defined('CIVICRM_TEMPLATE_COMPILEDIR')) {
      $this->templateCompileDir = CRM_Utils_File::addTrailingSlash(CIVICRM_TEMPLATE_COMPILEDIR);

      // also make sure we create the config directory within this directory
      // the below statement will create both the templates directory and the config and log directory
      $this->configAndLogDir =
        CRM_Utils_File::baseFilePath($this->templateCompileDir) .
        'ConfigAndLog' . DIRECTORY_SEPARATOR;
      CRM_Utils_File::createDir($this->configAndLogDir);
      CRM_Utils_File::restrictAccess($this->configAndLogDir);

      // we're automatically prefixing compiled templates directories with country/language code
      global $tsLocale;
      if (!empty($tsLocale)) {
        $this->templateCompileDir .= CRM_Utils_File::addTrailingSlash($tsLocale);
      }
      elseif (!empty($this->lcMessages)) {
        $this->templateCompileDir .= CRM_Utils_File::addTrailingSlash($this->lcMessages);
      }

      CRM_Utils_File::createDir($this->templateCompileDir);
      CRM_Utils_File::restrictAccess($this->templateCompileDir);
    }
    elseif ($loadFromDB) {
      echo 'You need to define CIVICRM_TEMPLATE_COMPILEDIR in civicrm.settings.php';
      exit();
    }

    $this->_initDAO();

    if (defined('CIVICRM_UF')) {
      $this->userFramework = CIVICRM_UF;
      $this->_setUserFrameworkConfig($this->userFramework);
    }
    else {
      echo 'You need to define CIVICRM_UF in civicrm.settings.php';
      exit();
    }

    // also initialize the logger
    self::$_log = Log::singleton('display');

    // initialize component registry early to avoid "race"
    // between CRM_Core_Config and CRM_Core_Component (they
    // are co-dependant)
    $this->componentRegistry = new CRM_Core_Component();
  }

  /**
   * initialize the DataObject framework
   *
   * @return void
   * @access private
   */
  private function _initDAO() {
    CRM_Core_DAO::init($this->dsn);

    $factoryClass = $this->DAOFactoryClass;
    require_once str_replace('_', DIRECTORY_SEPARATOR, $factoryClass) . '.php';
    CRM_Core_DAO::setFactory(new $factoryClass());
    if (CRM_Utils_Constant::value('CIVICRM_MYSQL_STRICT', CRM_Utils_System::isDevelopment())) {
      CRM_Core_DAO::executeQuery('SET SESSION sql_mode = STRICT_TRANS_TABLES');
    }
  }

  /**
   * returns the singleton logger for the application
   *
   * @param
   * @access private
   *
   * @return object
   */
  static public function &getLog() {
    if (!isset(self::$_log)) {
      self::$_log = Log::singleton('display');
    }

    return self::$_log;
  }

  /**
   * initialize the config variables
   *
   * @return void
   * @access private
   */
  private function _initVariables() {
    // retrieve serialised settings
    $variables = array();
    CRM_Core_BAO_ConfigSetting::retrieve($variables);

    // if settings are not available, go down the full path
    if (empty($variables)) {
      // Step 1. get system variables with their hardcoded defaults
      $variables = get_object_vars($this);

      // Step 2. get default values (with settings file overrides if
      // available - handled in CRM_Core_Config_Defaults)
      CRM_Core_Config_Defaults::setValues($variables);

      // retrieve directory and url preferences also
      CRM_Core_BAO_Setting::retrieveDirectoryAndURLPreferences($variables);

      // add component specific settings
      $this->componentRegistry->addConfig($this);

      // serialise settings
      $settings = $variables;
      CRM_Core_BAO_ConfigSetting::add($settings);
    }

    $urlArray = array('userFrameworkResourceURL', 'imageUploadURL');
    $dirArray = array('uploadDir', 'customFileUploadDir');

    foreach ($variables as $key => $value) {
      if (in_array($key, $urlArray)) {
        $value = CRM_Utils_File::addTrailingSlash($value, '/');
      }
      elseif (in_array($key, $dirArray)) {
        if ($value) {
          $value = CRM_Utils_File::addTrailingSlash($value);
        }
        if (empty($value) || (CRM_Utils_File::createDir($value, FALSE) === FALSE)) {
          // seems like we could not create the directories
          // settings might have changed, lets suppress a message for now
          // so we can make some more progress and let the user fix their settings
          // for now we assign it to a know value
          // CRM-4949
          $value = $this->templateCompileDir;
          $url = CRM_Utils_System::url('civicrm/admin/setting/path', 'reset=1');
          CRM_Core_Session::setStatus(ts('%1 has an incorrect directory path. Please go to the <a href="%2">path setting page</a> and correct it.', array(
            1 => $key,
            2 => $url
          )), ts('Check Settings'), 'alert');
        }
      }
      elseif ($key == 'lcMessages') {
        // reset the templateCompileDir to locale-specific and make sure it exists
        if (substr($this->templateCompileDir, -1 * strlen($value) - 1, -1) != $value) {
          $this->templateCompileDir .= CRM_Utils_File::addTrailingSlash($value);
          CRM_Utils_File::createDir($this->templateCompileDir);
          CRM_Utils_File::restrictAccess($this->templateCompileDir);
        }
      }

      $this->$key = $value;
    }

    if ($this->userFrameworkResourceURL) {
      // we need to do this here so all blocks also load from an ssl server
      if (CRM_Utils_System::isSSL()) {
        CRM_Utils_System::mapConfigToSSL();
      }
      $rrb = parse_url($this->userFrameworkResourceURL);
      // don't use absolute path if resources are stored on a different server
      // CRM-4642
      $this->resourceBase = $this->userFrameworkResourceURL;
      if (isset($_SERVER['HTTP_HOST']) &&
        isset($rrb['host'])
      ) {
        $this->resourceBase = ($rrb['host'] == $_SERVER['HTTP_HOST']) ? $rrb['path'] : $this->userFrameworkResourceURL;
      }
    }

    if (!$this->customFileUploadDir) {
      $this->customFileUploadDir = $this->uploadDir;
    }

    if ($this->geoProvider) {
      $this->geocodeMethod = 'CRM_Utils_Geocode_' . $this->geoProvider;
    }
    elseif ($this->mapProvider) {
      $this->geocodeMethod = 'CRM_Utils_Geocode_' . $this->mapProvider;
    }

    require_once (str_replace('_', DIRECTORY_SEPARATOR, $this->userFrameworkClass) . '.php');
    $class = $this->userFrameworkClass;
    // redundant with _setUserFrameworkConfig
    $this->userSystem = new $class();
  }

  /**
   * Retrieve a mailer to send any mail from the application
   *
   * @param boolean $persist open a persistent smtp connection, should speed up mailings
   * @access private
   * @return object
   */
  static function &getMailer($persist = FALSE) {
    if (!isset(self::$_mail)) {
      $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );

      if ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB ||
        (defined('CIVICRM_MAILER_SPOOL') && CIVICRM_MAILER_SPOOL)
      ) {
        self::$_mail = self::_createMailer('CRM_Mailing_BAO_Spool', array());
      }
      elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SMTP) {
        if ($mailingInfo['smtpServer'] == '' || !$mailingInfo['smtpServer']) {
          CRM_Core_Error::debug_log_message(ts('There is no valid smtp server setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the SMTP Server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
          CRM_Core_Error::fatal(ts('There is no valid smtp server setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the SMTP Server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
        }

        $params['host'] = $mailingInfo['smtpServer'] ? $mailingInfo['smtpServer'] : 'localhost';
        $params['port'] = $mailingInfo['smtpPort'] ? $mailingInfo['smtpPort'] : 25;

        if ($mailingInfo['smtpAuth']) {
          $params['username'] = $mailingInfo['smtpUsername'];
          $params['password'] = CRM_Utils_Crypt::decrypt($mailingInfo['smtpPassword']);
          $params['auth'] = TRUE;
        }
        else {
          $params['auth'] = FALSE;
        }

        // set the localhost value, CRM-3153
        $params['localhost'] = CRM_Utils_Array::value('SERVER_NAME', $_SERVER, 'localhost');

        // also set the timeout value, lets set it to 30 seconds
        // CRM-7510
        $params['timeout'] = 30;

        // CRM-9349
        $params['persist'] = $persist;

        self::$_mail = self::_createMailer('smtp', $params);
      }
      elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL) {
        if ($mailingInfo['sendmail_path'] == '' ||
          !$mailingInfo['sendmail_path']
        ) {
          CRM_Core_Error::debug_log_message(ts('There is no valid sendmail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the sendmail server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
          CRM_Core_Error::fatal(ts('There is no valid sendmail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the sendmail server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
        }
        $params['sendmail_path'] = $mailingInfo['sendmail_path'];
        $params['sendmail_args'] = $mailingInfo['sendmail_args'];

        self::$_mail = self::_createMailer('sendmail', $params);
      }
      elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MAIL) {
        self::$_mail = self::_createMailer('mail', array());
      }
      elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MOCK) {
        self::$_mail = self::_createMailer('mock', array());
      }
      elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED) {
        CRM_Core_Error::debug_log_message(ts('Outbound mail has been disabled. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
        CRM_Core_Session::setStatus(ts('Outbound mail has been disabled. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
      }
      else {
        CRM_Core_Error::debug_log_message(ts('There is no valid SMTP server Setting Or SendMail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
        CRM_Core_Session::setStatus(ts('There is no valid SMTP server Setting Or sendMail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
        CRM_Core_Error::debug_var('mailing_info', $mailingInfo);
      }
    }
    return self::$_mail;
  }

  /**
   * Create a new instance of a PEAR Mail driver
   *
   * @param string $driver 'CRM_Mailing_BAO_Spool' or a name suitable for Mail::factory()
   * @param array $params
   * @return Mail (More specifically, a class which implements the "send()" function)
   */
  public static function _createMailer($driver, $params) {
    if ($driver == 'CRM_Mailing_BAO_Spool') {
      $mailer = new CRM_Mailing_BAO_Spool($params);
    }
    else {
      $mailer = Mail::factory($driver, $params);
    }
    CRM_Utils_Hook::alterMail($mailer, $driver, $params);
    return $mailer;
  }

  /**
   * Deletes the web server writable directories
   *
   * @param int $value
   *   1: clean templates_c, 2: clean upload, 3: clean both
   * @param bool $rmdir
   */
  public function cleanup($value, $rmdir = TRUE) {
    $value = (int ) $value;

    if ($value & 1) {
      // clean templates_c
      CRM_Utils_File::cleanDir($this->templateCompileDir, $rmdir);
      CRM_Utils_File::createDir($this->templateCompileDir);
    }
    if ($value & 2) {
      // clean upload dir
      CRM_Utils_File::cleanDir($this->uploadDir);
      CRM_Utils_File::createDir($this->uploadDir);
    }

    // Whether we delete/create or simply preserve directories, we should
    // certainly make sure the restrictions are enforced.
    foreach (array($this->templateCompileDir, $this->uploadDir, $this->configAndLogDir, $this->customFileUploadDir) as $dir) {
      if ($dir && is_dir($dir)) {
        CRM_Utils_File::restrictAccess($dir);
      }
    }
  }

  /**
   * verify that the needed parameters are not null in the config
   *
   * @param CRM_Core_Config (reference ) the system config object
   * @param array           (reference ) the parameters that need a value
   *
   * @return boolean
   * @static
   * @access public
   */
  static function check(&$config, &$required) {
    foreach ($required as $name) {
      if (CRM_Utils_System::isNull($config->$name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * reset the serialized array and recompute
   * use with care
   */
  function reset() {
    $query = "UPDATE civicrm_domain SET config_backend = null";
    CRM_Core_DAO::executeQuery($query);
  }

  // This method should initialize auth sources
  function initAuthSrc() {
    $session = CRM_Core_Session::singleton();
    if ($session->get('userID') && !$session->get('authSrc')) {
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_LOGIN);
    }

    // checksum source
    CRM_Contact_BAO_Contact_Permission::initChecksumAuthSrc();
  }

  /**
   * one function to get domain ID
   */
  static function domainID($domainID = NULL, $reset = FALSE) {
    static $domain;
    if ($domainID) {
      $domain = $domainID;
    }
    if ($reset || empty($domain)) {
      $domain = defined('CIVICRM_DOMAIN_ID') ? CIVICRM_DOMAIN_ID : 1;
    }

    return $domain;
  }

  /**
   * do general cleanup of caches, temp directories and temp tables
   * CRM-8739
   */
  function cleanupCaches($sessionReset = TRUE) {
    // cleanup templates_c directory
    $this->cleanup(1, FALSE);

    // clear db caching
    self::clearDBCache();

    if ($sessionReset) {
      $session = CRM_Core_Session::singleton();
      $session->reset(2);
    }
  }

  /**
   * Do general cleanup of module permissions.
   */
  function cleanupPermissions() {
    $module_files = CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles();
    if ($this->userPermissionClass->isModulePermissionSupported()) {
      // Can store permissions -- so do it!
      $this->userPermissionClass->upgradePermissions(
        CRM_Core_Permission::basicPermissions()
      );
    } else {
      // Cannot store permissions -- warn if any modules require them
      $modules_with_perms = array();
      foreach ($module_files as $module_file) {
        $perms = $this->userPermissionClass->getModulePermissions($module_file['prefix']);
        if (!empty($perms)) {
          $modules_with_perms[] = $module_file['prefix'];
        }
      }
      if (!empty($modules_with_perms)) {
        CRM_Core_Session::setStatus(
          ts('Some modules define permissions, but the CMS cannot store them: %1', array(1 => implode(', ', $modules_with_perms))),
          ts('Permission Error'),
          'error'
        );
      }
    }
  }

  /**
   * Flush information about loaded modules
   */
  function clearModuleList() {
    CRM_Extension_System::singleton()->getCache()->flush();
    CRM_Utils_Hook::singleton(TRUE);
    CRM_Core_PseudoConstant::getModuleExtensions(TRUE);
    CRM_Core_Module::getAll(TRUE);
  }

  /**
   * clear db cache
   */
  public static function clearDBCache() {
    $queries = array(
      'TRUNCATE TABLE civicrm_acl_cache',
      'TRUNCATE TABLE civicrm_acl_contact_cache',
      'TRUNCATE TABLE civicrm_cache',
      'TRUNCATE TABLE civicrm_prevnext_cache',
      'UPDATE civicrm_group SET cache_date = NULL',
      'TRUNCATE TABLE civicrm_group_contact_cache',
      'TRUNCATE TABLE civicrm_menu',
      'UPDATE civicrm_setting SET value = NULL WHERE name="navigation" AND contact_id IS NOT NULL',
      'DELETE FROM civicrm_setting WHERE name="modulePaths"', // CRM-10543
    );

    foreach ($queries as $query) {
      CRM_Core_DAO::executeQuery($query);
    }

    // also delete all the import and export temp tables
    self::clearTempTables();
  }

  /**
   * clear leftover temporary tables
   */
  public static function clearTempTables() {
    // CRM-5645
    $dao = CRM_Core_DAO::executeQuery("SELECT DATABASE();");
    $query = "
SELECT TABLE_NAME as tableName
FROM   INFORMATION_SCHEMA.TABLES
WHERE  TABLE_SCHEMA = %1
AND
  ( TABLE_NAME LIKE 'civicrm_import_job_%'
  OR       TABLE_NAME LIKE 'civicrm_export_temp%'
  OR       TABLE_NAME LIKE 'civicrm_task_action_temp%'
  OR       TABLE_NAME LIKE 'civicrm_report_temp%'
  )
";

    $params = array(1 => array($dao->database(), 'String'));
    $tableDAO = CRM_Core_DAO::executeQuery($query, $params);
    $tables = array();
    while ($tableDAO->fetch()) {
      $tables[] = $tableDAO->tableName;
    }
    if (!empty($tables)) {
      $table = implode(',', $tables);
      // drop leftover temporary tables
      CRM_Core_DAO::executeQuery("DROP TABLE $table");
    }
  }

  /**
   * function to check if running in upgrade mode
   */
  static function isUpgradeMode($path = NULL) {
    if (defined('CIVICRM_UPGRADE_ACTIVE')) {
      return TRUE;
    }

    if (!$path) {
      // note: do not re-initialize config here, since this function is part of
      // config initialization itself
      $urlVar = 'q';
      if (defined('CIVICRM_UF') && CIVICRM_UF == 'Joomla') {
        $urlVar = 'task';
      }

      $path = CRM_Utils_Array::value($urlVar, $_GET);
    }

    if ($path && preg_match('/^civicrm\/upgrade(\/.*)?$/', $path)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Wrapper function to allow unit tests to switch user framework on the fly
   */
  public function setUserFramework($userFramework = NULL) {
    $this->userFramework = $userFramework;
    $this->_setUserFrameworkConfig($userFramework);
  }

  /**
   * Is back office credit card processing enabled for this site - ie are there any installed processors that support
   * on-site processing
   * @return bool
   */
  static function isEnabledBackOfficeCreditCardPayments() {
    // restrict to type=1 (credit card) payment processor payment_types and only include billing mode types 1 and 3
    $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE,
      "billing_mode IN ( 1, 3 ) AND payment_type = 1"
    );
    if (count($processors) > 0) {
      return TRUE;
    }
    return FALSE;
  }
}
// end CRM_Core_Config

