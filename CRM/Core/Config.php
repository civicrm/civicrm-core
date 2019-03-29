<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

require_once 'Log.php';
require_once 'Mail.php';

require_once 'api/api.php';

/**
 * Class CRM_Core_Config
 *
 * @property CRM_Utils_System_Base $userSystem
 * @property CRM_Core_Permission_Base $userPermissionClass
 * @property array $enableComponents
 * @property array $languageLimit
 * @property bool $debug
 * @property bool $doNotResetCache
 * @property string $maxFileSize
 * @property string $defaultCurrency
 * @property string $defaultCurrencySymbol
 * @property string $lcMessages
 * @property string $fieldSeparator
 * @property string $userFramework
 * @property string $verpSeparator
 * @property string $dateFormatFull
 * @property string $resourceBase
 * @property string $dsn
 * @property string $customTemplateDir
 * @property string $defaultContactCountry
 * @property string $defaultContactStateProvince
 * @property string $monetaryDecimalPoint
 * @property string $monetaryThousandSeparator
 * @property array fiscalYearStart
 */
class CRM_Core_Config extends CRM_Core_Config_MagicMerge {

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
    parent::__construct();
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

      self::$_singleton = new CRM_Core_Config();
      \Civi\Core\Container::boot($loadFromDB);
      if ($loadFromDB && self::$_singleton->dsn) {
        $domain = \CRM_Core_BAO_Domain::getDomain();
        \CRM_Core_BAO_ConfigSetting::applyLocale(\Civi::settings($domain->id), $domain->locales);

        unset($errorScope);

        CRM_Utils_Hook::config(self::$_singleton);
        self::$_singleton->authenticate();

        // Extreme backward compat: $config binds to active domain at moment of setup.
        self::$_singleton->getSettings();

        Civi::service('settings_manager')->useDefaults();

        self::$_singleton->handleFirstRun();
      }
    }
    return self::$_singleton;
  }

  /**
   * Returns the singleton logger for the application.
   *
   * @deprecated
   * @return object
   * @see Civi::log()
   */
  static public function &getLog() {
    if (!isset(self::$_log)) {
      self::$_log = Log::singleton('display');
    }

    return self::$_log;
  }

  /**
   * Retrieve a mailer to send any mail from the application.
   *
   * @return Mail
   * @deprecated
   * @see Civi::service()
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
   *
   * @param int $domainID
   * @param bool $reset
   *
   * @return int|null
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
   * Function to get environment.
   *
   * @param string $env
   * @param bool $reset
   *
   * @return string
   */
  public static function environment($env = NULL, $reset = FALSE) {
    if ($env) {
      $environment = $env;
    }
    if ($reset || empty($environment)) {
      $environment = Civi::settings()->get('environment');
    }
    if (!$environment) {
      $environment = 'Production';
    }
    return $environment;
  }

  /**
   * Do general cleanup of caches, temp directories and temp tables
   * CRM-8739
   *
   * @param bool $sessionReset
   */
  public function cleanupCaches($sessionReset = TRUE) {
    // cleanup templates_c directory
    $this->cleanup(1, FALSE);

    // clear all caches
    self::clearDBCache();
    Civi::cache('session')->clear();
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
    );

    foreach ($queries as $query) {
      CRM_Core_DAO::executeQuery($query);
    }

    if ($adapter = CRM_Utils_Constant::value('CIVICRM_BAO_CACHE_ADAPTER')) {
      return $adapter::clearDBCache();
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
        OR TABLE_NAME LIKE 'civicrm_report_temp%'
        OR TABLE_NAME LIKE 'civicrm_tmp_d%'
        )
    ";
    // NOTE: Cannot find use-cases where "civicrm_report_temp" would be durable. Could probably remove.

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
   *
   * @param string $path
   *
   * @return bool
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

    if ($path && preg_match('/^civicrm\/ajax\/l10n-js/', $path)
      && !empty($_SERVER['HTTP_REFERER'])
    ) {
      $ref = parse_url($_SERVER['HTTP_REFERER']);
      if (
        (!empty($ref['path']) && preg_match('/civicrm\/upgrade/', $ref['path'])) ||
        (!empty($ref['query']) && preg_match('/civicrm\/upgrade/', urldecode($ref['query'])))
      ) {
        return TRUE;
      }
    }

    return FALSE;
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
   *
   * @param string $defaultCurrency
   *
   * @return string
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

  /**
   * Conditionally fire an event during the first page run.
   *
   * The install system is currently implemented several times, so it's hard to add
   * new installation logic. We use a makeshift method to detect the first run.
   *
   * Situations to test:
   *  - New installation
   *  - Upgrade from an old version (predating first-run tracker)
   *  - Upgrade from an old version (with first-run tracking)
   */
  public function handleFirstRun() {
    // Ordinarily, we prefetch settings en masse and find that the system is already installed.
    // No extra SQL queries required.
    if (Civi::settings()->get('installed')) {
      return;
    }

    // Q: How should this behave during testing?
    if (defined('CIVICRM_TEST')) {
      return;
    }

    // If schema hasn't been loaded yet, then do nothing. Don't want to interfere
    // with the existing installers. NOTE: If we change the installer pageflow,
    // then we may want to modify this behavior.
    if (!CRM_Core_DAO::checkTableExists('civicrm_domain')) {
      return;
    }

    // If we're handling an upgrade, then the system has already been used, so this
    // is not the first run.
    if (CRM_Core_Config::isUpgradeMode()) {
      return;
    }
    $dao = CRM_Core_DAO::executeQuery('SELECT version FROM civicrm_domain');
    while ($dao->fetch()) {
      if ($dao->version && version_compare($dao->version, CRM_Utils_System::version(), '<')) {
        return;
      }
    }

    // The installation flag is stored in civicrm_setting, which is domain-aware. The
    // flag could have been stored under a different domain.
    $dao = CRM_Core_DAO::executeQuery('
      SELECT domain_id, value FROM civicrm_setting
      WHERE is_domain = 1 AND name = "installed"
    ');
    while ($dao->fetch()) {
      $value = unserialize($dao->value);
      if (!empty($value)) {
        Civi::settings()->set('installed', 1);
        return;
      }
    }

    // OK, this looks new.
    Civi::service('dispatcher')->dispatch(\Civi\Core\Event\SystemInstallEvent::EVENT_NAME, new \Civi\Core\Event\SystemInstallEvent());
    Civi::settings()->set('installed', 1);
  }

  /**
   * Is the system permitted to flush caches at the moment.
   */
  static public function isPermitCacheFlushMode() {
    return !CRM_Core_Config::singleton()->doNotResetCache;
  }

  /**
   * Set cache clearing to enabled or disabled.
   *
   * This might be enabled at the start of a long running process
   * such as an import in order to delay clearing caches until the end.
   *
   * @param bool $enabled
   *   If true then caches can be cleared at this time.
   */
  static public function setPermitCacheFlushMode($enabled) {
    CRM_Core_Config::singleton()->doNotResetCache = $enabled ? 0 : 1;
  }

}
