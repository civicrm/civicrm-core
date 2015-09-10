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
 * Config handles all the run time configuration changes that the system needs to deal with.
 *
 * Typically we'll have different values for a user's sandbox, a qa sandbox and a production area.
 * The default values in general, should reflect production values (minimizes chances of screwing up)
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

require_once 'Log.php';
require_once 'Mail.php';

require_once 'api/api.php';

/**
 * Class CRM_Core_Config
 */
class CRM_Core_Config extends CRM_Core_Config_Variables {

  /**
   * The handle to the log that we are using
   * @var object
   */
  private static $_log = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var CRM_Core_Config
   */
  private static $_singleton = NULL;

  /**
   * The constructor. Sets domain id if defined, otherwise assumes
   * single instance installation.
   */
  public function __construct() {
    //parent::__construct();
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param bool $loadFromDB
   *   whether to load from the database.
   * @param bool $force
   *   whether to force a reconstruction.
   *
   * @return CRM_Core_Config
   */
  public static function &singleton($loadFromDB = TRUE, $force = FALSE) {
    if (self::$_singleton === NULL || $force) {
      $GLOBALS['civicrm_default_error_scope'] = CRM_Core_TemporaryErrorScope::create(array('CRM_Core_Error', 'handle'));
      $errorScope = CRM_Core_TemporaryErrorScope::create(array('CRM_Core_Error', 'simpleHandler'));

      if (defined('E_DEPRECATED')) {
        error_reporting(error_reporting() & ~E_DEPRECATED);
      }

      $cache = CRM_Utils_Cache::singleton();
      self::$_singleton = $cache->get('CRM_Core_Config' . CRM_Core_Config::domainID());
      if (!self::$_singleton) {
        self::$_singleton = new CRM_Core_Config();
        self::$_singleton->_initialize($loadFromDB);
        $cache->set('CRM_Core_Config' . CRM_Core_Config::domainID(), self::$_singleton);
      }
      else {
        self::$_singleton->_initialize(FALSE);
      }

      unset($errorScope);

      CRM_Utils_Hook::config(self::$_singleton);
      self::$_singleton->authenticate();
      Civi::service('settings_manager')->useDefaults();
    }
    return self::$_singleton;
  }

  /**
   * Initializes the entire application.
   * Reads constants defined in civicrm.settings.php and
   * stores them in config properties.
   *
   * @param bool $loadFromDB
   */
  private function _initialize($loadFromDB = TRUE) {
    if (!defined('CIVICRM_DSN') && $loadFromDB) {
      $this->fatal('You need to define CIVICRM_DSN in civicrm.settings.php');
    }
    $this->dsn = defined('CIVICRM_DSN') ? CIVICRM_DSN : NULL;

    if (!defined('CIVICRM_TEMPLATE_COMPILEDIR') && $loadFromDB) {
      $this->fatal('You need to define CIVICRM_TEMPLATE_COMPILEDIR in civicrm.settings.php');
    }

    if (defined('CIVICRM_TEMPLATE_COMPILEDIR')) {
      $this->configAndLogDir = CRM_Utils_File::baseFilePath() . 'ConfigAndLog' . DIRECTORY_SEPARATOR;
      CRM_Utils_File::createDir($this->configAndLogDir);
      CRM_Utils_File::restrictAccess($this->configAndLogDir);

      $this->templateCompileDir = defined('CIVICRM_TEMPLATE_COMPILEDIR') ? CRM_Utils_File::addTrailingSlash(CIVICRM_TEMPLATE_COMPILEDIR) : NULL;
      CRM_Utils_File::createDir($this->templateCompileDir);
      CRM_Utils_File::restrictAccess($this->templateCompileDir);
    }

    CRM_Core_DAO::init($this->dsn);

    if (!defined('CIVICRM_UF')) {
      $this->fatal('You need to define CIVICRM_UF in civicrm.settings.php');
    }
    $this->setUserFramework(CIVICRM_UF);

    if ($loadFromDB) {
      $this->_initVariables();
    }

    if (CRM_Utils_System::isSSL()) {
      $this->userSystem->mapConfigToSSL();
    }

    if (isset($this->customPHPPathDir) && $this->customPHPPathDir) {
      set_include_path($this->customPHPPathDir . PATH_SEPARATOR . get_include_path());
    }

    $this->initialized = 1;
  }

  /**
   * Returns the singleton logger for the application.
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
   * Initialize the config variables.
   */
  private function _initVariables() {
    $this->templateDir = array(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR);

    // retrieve serialised settings
    $variables = array();
    CRM_Core_BAO_ConfigSetting::retrieve($variables);

    // if settings are not available, go down the full path
    if (empty($variables)) {
      // Step 1. get system variables with their hardcoded defaults
      $variables = get_object_vars($this);

      // serialise settings
      $settings = $variables;
      CRM_Core_BAO_ConfigSetting::add($settings);
    }

    foreach ($variables as $key => $value) {
      $this->$key = $value;
    }

    $this->enableComponents = Civi::settings()->get('enable_components');

    $this->customFileUploadDir = CRM_Core_Config_Defaults::getCustomFileUploadDir();
    $this->customPHPPathDir = CRM_Core_Config_Defaults::getCustomPhpPathDir();
    $this->customTemplateDir = CRM_Core_Config_Defaults::getCustomTemplateDir();
    $this->extensionsDir = CRM_Core_Config_Defaults::getExtensionsDir();
    $this->imageUploadDir = CRM_Core_Config_Defaults::getImageUploadDir();
    $this->resourceBase = CRM_Core_Config_Defaults::getResourceBase();
    $this->uploadDir = CRM_Core_Config_Defaults::getUploadDir();

    $this->userFrameworkResourceURL = CRM_Core_Config_Defaults::getUserFrameworkResourceUrl();
    $this->customCSSURL = CRM_Core_Config_Defaults::getCustomCssUrl();
    $this->extensionsURL = CRM_Core_Config_Defaults::getExtensionsUrl();
    $this->imageUploadURL = CRM_Core_Config_Defaults::getImageUploadUrl();

    $this->geocodeMethod = CRM_Utils_Geocode::getProviderClass();
    $this->defaultCurrencySymbol = CRM_Core_Config_Defaults::getDefaultCurrencySymbol();
  }

  /**
   * Retrieve a mailer to send any mail from the application.
   *
   * @return Mail
   * @deprecated
   */
  public static function getMailer() {
    return Civi::service('pear_mail');
  }

  /**
   * Deletes the web server writable directories.
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
    foreach (array(
               $this->templateCompileDir,
               $this->uploadDir,
               $this->configAndLogDir,
               $this->customFileUploadDir,
             ) as $dir) {
      if ($dir && is_dir($dir)) {
        CRM_Utils_File::restrictAccess($dir);
      }
    }
  }

  /**
   * Verify that the needed parameters are not null in the config.
   *
   * @param CRM_Core_Config $config (reference) the system config object
   * @param array $required (reference) the parameters that need a value
   *
   * @return bool
   */
  public static function check(&$config, &$required) {
    foreach ($required as $name) {
      if (CRM_Utils_System::isNull($config->$name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Reset the serialized array and recompute.
   * use with care
   */
  public function reset() {
    $query = "UPDATE civicrm_domain SET config_backend = null";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * This method should initialize auth sources.
   */
  public function authenticate() {
    // make sure session is always initialised
    $session = CRM_Core_Session::singleton();

    // for logging purposes, pass the userID to the db
    $userID = $session->get('userID');
    if ($userID) {
      CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
        array(1 => array($userID, 'Integer'))
      );
    }

    if ($session->get('userID') && !$session->get('authSrc')) {
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_LOGIN);
    }

    // checksum source
    CRM_Contact_BAO_Contact_Permission::initChecksumAuthSrc();
  }

  /**
   * One function to get domain ID.
   */
  public static function domainID($domainID = NULL, $reset = FALSE) {
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
   * Do general cleanup of caches, temp directories and temp tables
   * CRM-8739
   */
  public function cleanupCaches($sessionReset = TRUE) {
    // cleanup templates_c directory
    $this->cleanup(1, FALSE);

    // clear all caches
    self::clearDBCache();
    CRM_Utils_System::flushCache();

    if ($sessionReset) {
      $session = CRM_Core_Session::singleton();
      $session->reset(2);
    }
  }

  /**
   * Do general cleanup of module permissions.
   */
  public function cleanupPermissions() {
    $module_files = CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles();
    if ($this->userPermissionClass->isModulePermissionSupported()) {
      // Can store permissions -- so do it!
      $this->userPermissionClass->upgradePermissions(
        CRM_Core_Permission::basicPermissions()
      );
    }
    else {
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
   * Flush information about loaded modules.
   */
  public function clearModuleList() {
    CRM_Extension_System::singleton()->getCache()->flush();
    CRM_Utils_Hook::singleton(TRUE);
    CRM_Core_PseudoConstant::getModuleExtensions(TRUE);
    CRM_Core_Module::getAll(TRUE);
  }

  /**
   * Clear db cache.
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
   * Clear leftover temporary tables.
   *
   * This is called on upgrade, during tests and site move, from the cron and via clear caches in the UI.
   *
   * Currently the UI clear caches does not pass a time interval - which may need review as it does risk
   * ripping the tables out from underneath a current action. This was considered but
   * out-of-scope for CRM-16167
   *
   * @param string|bool $timeInterval
   *   Optional time interval for mysql date function.g '2 day'. This can be used to prevent
   *   tables created recently from being deleted.
   */
  public static function clearTempTables($timeInterval = FALSE) {

    $dao = new CRM_Core_DAO();
    $query = "
      SELECT TABLE_NAME as tableName
      FROM   INFORMATION_SCHEMA.TABLES
      WHERE  TABLE_SCHEMA = %1
      AND (
        TABLE_NAME LIKE 'civicrm_import_job_%'
        OR TABLE_NAME LIKE 'civicrm_export_temp%'
        OR TABLE_NAME LIKE 'civicrm_task_action_temp%'
        OR TABLE_NAME LIKE 'civicrm_report_temp%'
        )
    ";
    if ($timeInterval) {
      $query .= " AND CREATE_TIME < DATE_SUB(NOW(), INTERVAL {$timeInterval})";
    }

    $tableDAO = CRM_Core_DAO::executeQuery($query, array(1 => array($dao->database(), 'String')));
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
   * Check if running in upgrade mode.
   */
  public static function isUpgradeMode($path = NULL) {
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
   * Wrapper function to allow unit tests to switch user framework on the fly.
   *
   * @param string $userFramework
   *   One of 'Drupal', 'Joomla', etc.
   */
  public function setUserFramework($userFramework) {
    $this->userFramework = $userFramework;
    $this->userFrameworkClass = 'CRM_Utils_System_' . $userFramework;
    $this->userHookClass = 'CRM_Utils_Hook_' . $userFramework;
    $userPermissionClass = 'CRM_Core_Permission_' . $userFramework;
    $this->userPermissionClass = new $userPermissionClass();

    $class = $this->userFrameworkClass;
    $this->userSystem = new $class();

    if ($userFramework == 'Joomla') {
      $this->userFrameworkURLVar = 'task';
    }

    if (defined('CIVICRM_UF_BASEURL')) {
      $this->userFrameworkBaseURL = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');

      //format url for language negotiation, CRM-7803
      $this->userFrameworkBaseURL = CRM_Utils_System::languageNegotiationURL($this->userFrameworkBaseURL);

      if (CRM_Utils_System::isSSL()) {
        $this->userFrameworkBaseURL = str_replace('http://', 'https://', $this->userFrameworkBaseURL);
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
  }

  /**
   * Is back office credit card processing enabled for this site - ie are there any installed processors that support
   * it?
   * This function is used for determining whether to show the submit credit card link, not for determining which processors to show, hence
   * it is a config var
   * @return bool
   */
  public static function isEnabledBackOfficeCreditCardPayments() {
    return CRM_Financial_BAO_PaymentProcessor::hasPaymentProcessorSupporting(array('BackOffice'));
  }

  /**
   * @deprecated
   */
  public function addressSequence() {
    return CRM_Utils_Address::sequence(Civi::settings()->get('address_format'));
  }

  /**
   * @deprecated
   */
  public function defaultContactCountry() {
    return CRM_Core_BAO_Country::defaultContactCountry();
  }

  /**
   * @deprecated
   */
  public function defaultContactCountryName() {
    return CRM_Core_BAO_Country::defaultContactCountryName();
  }

  /**
   * @deprecated
   */
  public function defaultCurrencySymbol($defaultCurrency = NULL) {
    return CRM_Core_BAO_Country::defaultCurrencySymbol($defaultCurrency);
  }

  /**
   * Resets the singleton, so that the next call to CRM_Core_Config::singleton()
   * reloads completely.
   *
   * While normally we could call the singleton function with $force = TRUE,
   * this function addresses a very specific use-case in the CiviCRM installer,
   * where we cannot yet force a reload, but we want to make sure that the next
   * call to this object gets a fresh start (ex: to initialize the DAO).
   */
  public function free() {
    self::$_singleton = NULL;
  }

  private function fatal($message) {
    echo $message;
    exit();
  }

}
