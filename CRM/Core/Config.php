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
 * Config handles all the run time configuration changes that the system needs to deal with.
 *
 * Typically we'll have different values for a user's sandbox, a qa sandbox and a production area.
 * The default values in general, should reflect production values (minimizes chances of screwing up)
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
 * @property array $fiscalYearStart
 * @property string $customFileUploadDir user file upload directory with trailing slash
 * @property string $imageUploadDir media upload directory with trailing slash
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
  private static $_singleton;

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
      $GLOBALS['civicrm_default_error_scope'] = CRM_Core_TemporaryErrorScope::create(['CRM_Core_Error', 'exceptionHandler'], 1);
      $errorScope = CRM_Core_TemporaryErrorScope::create(['CRM_Core_Error', 'simpleHandler']);

      self::$_singleton = new CRM_Core_Config();

      \Civi\Core\Container::boot($loadFromDB);

      if ($loadFromDB && self::$_singleton->dsn) {
        self::$_singleton->userSystem->postContainerBoot();

        Civi::service('settings_manager')->bootComplete();

        $domain = \CRM_Core_BAO_Domain::getDomain();
        \CRM_Core_BAO_ConfigSetting::applyLocale(\Civi::settings($domain->id), $domain->locales);

        unset($errorScope);

        CRM_Utils_Hook::config(self::$_singleton, [
          'civicrm' => TRUE,
          'uf' => self::$_singleton->userSystem->isLoaded(),
        ]);
        self::$_singleton->authenticate();

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
  public static function &getLog() {
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
      CRM_Utils_File::cleanDir($this->templateCompileDir, $rmdir, FALSE);
      CRM_Utils_File::createDir($this->templateCompileDir);
    }
    if ($value & 2) {
      // clean upload dir
      CRM_Utils_File::cleanDir($this->uploadDir);
      CRM_Utils_File::createDir($this->uploadDir);
    }

    // Whether we delete/create or simply preserve directories, we should
    // certainly make sure the restrictions are enforced.
    foreach ([
      $this->templateCompileDir,
      $this->uploadDir,
      $this->configAndLogDir,
      $this->customFileUploadDir,
    ] as $dir) {
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
   *
   * @deprecated
   */
  public function reset() {
    // This is what it used to do. However, it hasn't meant anything since 4.6.
    // $query = "UPDATE civicrm_domain SET config_backend = null";
    // CRM_Core_DAO::executeQuery($query);
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
        [1 => [$userID, 'Integer']]
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
   * @return int
   */
  public static function domainID($domainID = NULL, $reset = FALSE) {
    static $domain;
    if ($domainID) {
      $domain = $domainID;
    }
    if ($reset || empty($domain)) {
      $domain = defined('CIVICRM_DOMAIN_ID') ? CIVICRM_DOMAIN_ID : 1;
    }

    return (int) $domain;
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
   * @see https://issues.civicrm.org/jira/browse/CRM-8739
   *
   * @param bool $sessionReset
   * @deprecated
   *   Deprecated Feb 2025 in favor of Civi::rebuild().
   *   Reassess after Jun 2026.
   *   For an extension bridging before+after, suggest guard like:
   *     if (version_compare(CRM_Utils_System::version(), 'X.Y.Z', '>=')) Civi::rebuild(...)->execute()
   *     else CRM_Core_Config::singleton()->cleanupCaches();
   *   Choose an 'X.Y.Z' after determining that your preferred rebuild-target(s) are specifically available in X.Y.Z.
   */
  public function cleanupCaches($sessionReset = FALSE) {
    Civi::rebuild([
      'files' => TRUE,
      'tables' => TRUE,
      'sessions' => $sessionReset,
      'metadata' => TRUE,
      'system' => TRUE,
      'userjob' => TRUE,
    ])->execute();
  }

  /**
   * Do general cleanup of module permissions.
   */
  public function cleanupPermissions() {
    $module_files = CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles();
    if ($this->userPermissionClass->isModulePermissionSupported()) {
      // Can store permissions -- so do it!
      $this->userPermissionClass->upgradePermissions(
        CRM_Core_Permission::basicPermissions(TRUE)
      );
    }
    elseif (get_class($this->userPermissionClass) !== 'CRM_Core_Permission_UnitTests') {
      // Cannot store permissions -- warn if any modules require them
      $modules_with_perms = [];
      foreach ($module_files as $module_file) {
        $perms = $this->userPermissionClass->getModulePermissions($module_file['prefix']);
        if (!empty($perms)) {
          $modules_with_perms[] = $module_file['prefix'];
        }
      }
      // FIXME: Setting a session status message here is probably wrong.
      // For starters we are not necessarily in the context of a user-facing form
      // for another thing this message will show indiscriminately to non-admin users
      // and finally, this message contains nothing actionable for the person reading it to do.
      if (!empty($modules_with_perms)) {
        CRM_Core_Session::setStatus(
          ts('Some modules define permissions, but the CMS cannot store them: %1', [1 => implode(', ', $modules_with_perms)]),
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
  public static function clearDBCache(): void {
    $queries = [
      'TRUNCATE TABLE civicrm_acl_cache',
      'TRUNCATE TABLE civicrm_acl_contact_cache',
      // Do not truncate, reduce risks of losing a quickform session
      'DELETE FROM civicrm_cache WHERE group_name NOT LIKE "CiviCRM%Session"',
      'TRUNCATE TABLE civicrm_prevnext_cache',
      'UPDATE civicrm_group SET cache_date = NULL',
      'TRUNCATE TABLE civicrm_group_contact_cache',
      'TRUNCATE TABLE civicrm_menu',
      'UPDATE civicrm_setting SET value = NULL WHERE name="navigation" AND contact_id IS NOT NULL',
    ];

    foreach ($queries as $query) {
      CRM_Core_DAO::executeQuery($query);
    }

    // Clear the Redis prev-next cache, if there is one.
    // Since we truncated the civicrm_cache table it is logical to also remove
    // the same from the Redis cache here.
    \Civi::service('prevnext')->deleteItem();

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
  public static function clearTempTables($timeInterval = FALSE): void {
    $query = "
      SELECT TABLE_NAME as tableName
      FROM   INFORMATION_SCHEMA.TABLES
      WHERE  TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME LIKE 'civicrm_tmp_d%'
    ";

    if ($timeInterval) {
      $query .= " AND CREATE_TIME < DATE_SUB(NOW(), INTERVAL {$timeInterval})";
    }

    $tableDAO = CRM_Core_DAO::executeQuery($query);
    $tables = [];
    while ($tableDAO->fetch()) {
      // If a User Job references the table do not drop it. This is a bit quick & dirty, but we don't want to
      // get into calling more sophisticated functions in a cache clear, and the table names are pretty unique
      // (ex: "civicrm_tmp_d_dflt_1234abcd5678efgh"), and the "metadata" may continue to evolve for the next
      // couple months.
      // TODO: Circa v5.60+, consider a more precise cleanup. Discussion: https://github.com/civicrm/civicrm-core/pull/24538
      // A separate process will reap the UserJobs but here the goal is just not to delete them during cache clearing
      // if they are still referenced.
      if (!CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_user_job WHERE metadata LIKE '%" . $tableDAO->tableName . "%'")) {
        $tables[] = $tableDAO->tableName;
      }
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

    $upgradeInProcess = CRM_Core_Session::singleton()->get('isUpgradePending');
    if ($upgradeInProcess) {
      return TRUE;
    }

    if (!$path) {
      // note: do not re-initialize config here, since this function is part of
      // config initialization itself
      $urlVar = 'q';
      if (defined('CIVICRM_UF') && CIVICRM_UF == 'Joomla') {
        $urlVar = 'task';
      }

      $path = $_GET[$urlVar] ?? NULL;
    }

    return ($path && preg_match('/^civicrm\/upgrade(\/.*)?$/', $path));
  }

  /**
   * Is back office credit card processing enabled for this site - ie are there any installed processors that support
   * it?
   * This function is used for determining whether to show the submit credit card link, not for determining which processors to show, hence
   * it is a config var
   * @return bool
   */
  public static function isEnabledBackOfficeCreditCardPayments() {
    return CRM_Financial_BAO_PaymentProcessor::hasPaymentProcessorSupporting(['BackOffice']);
  }

  /**
   * @deprecated
   */
  public function addressSequence() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Utils_Address::sequence(Civi::settings()->get(\'address_format\')');
    return CRM_Utils_Address::sequence(Civi::settings()->get('address_format'));
  }

  /**
   * @deprecated
   */
  public function defaultContactCountry() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_Country::defaultContactCountry');
    return CRM_Core_BAO_Country::defaultContactCountry();
  }

  /**
   * @deprecated
   */
  public function defaultContactCountryName() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_Country::defaultContactCountryName');
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
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_Country::defaultCurrencySymbol');
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
      $value = CRM_Utils_String::unserialize($dao->value);
      if (!empty($value)) {
        Civi::settings()->set('installed', 1);
        return;
      }
    }

    // OK, this looks new.
    Civi::dispatcher()->dispatch('civi.core.install', new \Civi\Core\Event\SystemInstallEvent());
    Civi::settings()->set('installed', 1);
  }

  /**
   * Is the system permitted to flush caches at the moment.
   */
  public static function isPermitCacheFlushMode() {
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
  public static function setPermitCacheFlushMode($enabled) {
    CRM_Core_Config::singleton()->doNotResetCache = $enabled ? 0 : 1;
  }

}
