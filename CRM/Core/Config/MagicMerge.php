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
 * Class CRM_Core_Config_MagicMerge
 *
 * Originally, the $config object was based on a single, serialized
 * data object stored in the database. As the needs for settings
 * grew (with robust metadata, system overrides, extension support,
 * and multi-tenancy), the $config started to store a mix of:
 *   (a) canonical config options,
 *   (b) dynamically generated runtime data,
 *   (c) cached data derived from other sources (esp civicrm_setting)
 *   (d) instances of service objects
 *
 * The config object is now deprecated. Settings and service objects
 * should generally be accessed via Civi::settings() and Civi::service().
 *
 * MagicMerge provides backward compatibility. You may still access
 * old properties via $config, but they will be loaded from their
 * new services.
 */
class CRM_Core_Config_MagicMerge {

  /**
   * Map old config properties to their contemporary counterparts.
   *
   * @var array
   *   Array(string $configAlias => Array(string $realType, string $realName)).
   */
  private $map;

  private $locals;
  private $settings;

  private $cache = [];

  /**
   * CRM_Core_Config_MagicMerge constructor.
   */
  public function __construct() {
    $this->map = self::getPropertyMap();
  }

  /**
   * Set the map to the property map.
   */
  public function __wakeup() {
    $this->map = self::getPropertyMap();
  }

  /**
   * Get a list of $config properties and the entities to which they map.
   *
   * This is used for two purposes:
   *
   *  1. Runtime: Provide backward-compatible interface for reading these
   *     properties.
   *  2. Upgrade: Migrate old properties of config_backend into settings.
   *
   * @return array
   */
  public static function getPropertyMap() {
    // Each mapping: $propertyName => Array(0 => $type, 1 => $foreignName|NULL, ...).
    // If $foreignName is omitted/null, then it's assumed to match the $propertyName.
    // Other parameters may be specified, depending on the type.
    return [
      // "local" properties are unique to each instance of CRM_Core_Config (each request).
      'doNotResetCache' => ['local'],
      'inCiviCRM' => ['local'],
      'keyDisable' => ['local'],
      'userFrameworkFrontend' => ['local'],
      'userPermissionTemp' => ['local'],

      // "runtime" properties are computed from define()s, $_ENV, etc.
      // See also: CRM_Core_Config_Runtime.
      'dsn' => ['runtime'],
      'initialized' => ['runtime'],
      'userFramework' => ['runtime'],
      'userFrameworkClass' => ['runtime'],
      'userFrameworkDSN' => ['runtime'],
      'userFrameworkURLVar' => ['runtime'],
      'userHookClass' => ['runtime'],
      'cleanURL' => ['runtime'],
      'templateDir' => ['runtime'],

      // "boot-svc" properties are critical services needed during init.
      // See also: Civi\Core\Container::getBootService().
      'userSystem' => ['boot-svc'],
      'userPermissionClass' => ['boot-svc'],

      'userFrameworkBaseURL' => ['user-system', 'getAbsoluteBaseURL'],
      'userFrameworkVersion' => ['user-system', 'getVersion'],
    // ugh typo.
      'useFrameworkRelativeBase' => ['user-system', 'getRelativeBaseURL'],

      // "setting" properties are loaded through the setting layer, esp
      // table "civicrm_setting" and global $civicrm_setting.
      // See also: Civi::settings().
      'backtrace' => ['setting'],
      'contact_default_language' => ['setting'],
      'countryLimit' => ['setting'],
      'customTranslateFunction' => ['setting'],
      'dateInputFormat' => ['setting'],
      'dateformatDatetime' => ['setting'],
      'dateformatFull' => ['setting'],
      'dateformatPartial' => ['setting'],
      'dateformatTime' => ['setting'],
      'dateformatYear' => ['setting'],
      'dateformatFinancialBatch' => ['setting'],
      'dateformatshortdate' => ['setting'],
      // renamed.
      'debug' => ['setting', 'debug_enabled'],
      'defaultContactCountry' => ['setting'],
      'pinnedContactCountries' => ['setting'],
      'defaultContactStateProvince' => ['setting'],
      'defaultCurrency' => ['setting'],
      'defaultSearchProfileID' => ['setting'],
      'doNotAttachPDFReceipt' => ['setting'],
      'empoweredBy' => ['setting'],
      // renamed.
      'enableComponents' => ['setting', 'enable_components'],
      'enableSSL' => ['setting'],
      'fatalErrorHandler' => ['setting'],
      'fieldSeparator' => ['setting'],
      'fiscalYearStart' => ['setting'],
      'geoAPIKey' => ['setting'],
      'geoProvider' => ['setting'],
      'includeAlphabeticalPager' => ['setting'],
      'includeEmailInName' => ['setting'],
      'includeNickNameInName' => ['setting'],
      'includeOrderByClause' => ['setting'],
      'includeWildCardInName' => ['setting'],
      'inheritLocale' => ['setting'],
      'languageLimit' => ['setting'],
      'lcMessages' => ['setting'],
      'legacyEncoding' => ['setting'],
      'logging' => ['setting'],
      'mailThrottleTime' => ['setting'],
      'mailerBatchLimit' => ['setting'],
      'mailerJobSize' => ['setting'],
      'mailerJobsMax' => ['setting'],
      'mapAPIKey' => ['setting'],
      'mapProvider' => ['setting'],
      'maxFileSize' => ['setting'],
      // renamed.
      'maxAttachments' => ['setting', 'max_attachments'],
      'maxAttachmentsBackend' => ['setting', 'max_attachments_backend'],
      'monetaryDecimalPoint' => ['setting'],
      'monetaryThousandSeparator' => ['setting'],
      'moneyformat' => ['setting'],
      'moneyvalueformat' => ['setting'],
      'provinceLimit' => ['setting'],
      'recaptchaPublicKey' => ['setting'],
      'recaptchaPrivateKey' => ['setting'],
      'forceRecaptcha' => ['setting'],
      'replyTo' => ['setting'],
      'secondDegRelPermissions' => ['setting'],
      'smartGroupCacheTimeout' => ['setting'],
      'timeInputFormat' => ['setting'],
      'userFrameworkLogging' => ['setting'],
      'userFrameworkUsersTableName' => ['setting'],
      'verpSeparator' => ['setting'],
      'wkhtmltopdfPath' => ['setting'],
      'wpLoadPhp' => ['setting'],

      // "path" properties are managed via Civi::paths and $civicrm_paths
      // Option: `mkdir` - auto-create dir
      // Option: `restrict` - auto-restrict remote access
      'configAndLogDir' => ['path', 'civicrm.log', ['mkdir', 'restrict']],
      'templateCompileDir' => ['path', 'civicrm.compile', ['mkdir', 'restrict']],

      // "setting-path" properties are settings with special filtering
      // to return normalized file paths.
      // Option: `mkdir` - auto-create dir
      // Option: `restrict` - auto-restrict remote access
      'customFileUploadDir' => ['setting-path', NULL, ['mkdir', 'restrict']],
      'customPHPPathDir' => ['setting-path'],
      'customTemplateDir' => ['setting-path'],
      'extensionsDir' => ['setting-path', NULL, ['mkdir']],
      'imageUploadDir' => ['setting-path', NULL, ['mkdir']],
      'uploadDir' => ['setting-path', NULL, ['mkdir', 'restrict']],

      // "setting-url" properties are settings with special filtering
      // to return normalized URLs.
      // Option: `noslash` - don't append trailing slash
      // Option: `rel` - convert to relative URL (if possible)
      'customCSSURL' => ['setting-url', NULL, ['noslash']],
      'extensionsURL' => ['setting-url'],
      'imageUploadURL' => ['setting-url'],
      'resourceBase' => ['setting-url', 'userFrameworkResourceURL', ['rel']],
      'userFrameworkResourceURL' => ['setting-url'],

      // "callback" properties are generated on-demand by calling a function.
      'defaultCurrencySymbol' => ['callback', 'CRM_Core_BAO_Country', 'getDefaultCurrencySymbol'],
      'wpBasePage' => ['callback', 'CRM_Utils_System_WordPress', 'getBasePage'],
    ];
  }

  /**
   * Get value.
   *
   * @param string $k
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function __get($k) {
    if (!isset($this->map[$k])) {
      throw new \CRM_Core_Exception("Cannot read unrecognized property CRM_Core_Config::\${$k}.");
    }
    if (isset($this->cache[$k])) {
      return $this->cache[$k];
    }

    $type = $this->map[$k][0];
    $name = $this->map[$k][1] ?? $k;

    switch ($type) {
      case 'setting':
        return $this->getSettings()->get($name);

      // The interpretation of 'path' and 'setting-path' is similar, except
      // that the latter originates in a stored setting.
      case 'path':
      case 'setting-path':
        // Array(0 => $type, 1 => $setting, 2 => $actions).
        $value = ($type === 'path')
          ? Civi::paths()->getVariable($name, 'path')
          : Civi::paths()->getPath($this->getSettings()->get($name));
        if ($value) {
          $value = CRM_Utils_File::addTrailingSlash($value);
          if (isset($this->map[$k][2]) && in_array('mkdir', $this->map[$k][2])) {
            if (!is_dir($value) && !CRM_Utils_File::createDir($value, FALSE)) {
              // we want to warn the user about this error
              // ideally we show a browser alert
              // but this might not be possible if the session handler isn't up yet, so fallback to just printing it (sorry)
              $alertMessage = ts('Failed to make directory (%1) at "%2". Please update the settings or file permissions.', [
                1 => $k,
                2 => $value,
              ]);
              try {
                CRM_Core_Session::setStatus($alertMessage);
              }
              catch (\Error $e) {
                echo $alertMessage;
              }
            }
          }
          if (isset($this->map[$k][2]) && in_array('restrict', $this->map[$k][2])) {
            CRM_Utils_File::restrictAccess($value);
          }
        }
        $this->cache[$k] = $value;
        return $value;

      case 'setting-url':
        $options = !empty($this->map[$k][2]) ? $this->map[$k][2] : [];
        $value = $this->getSettings()->get($name);
        if ($value && !(in_array('noslash', $options))) {
          $value = CRM_Utils_File::addTrailingSlash($value, '/');
        }
        $this->cache[$k] = Civi::paths()->getUrl($value,
          in_array('rel', $options) ? 'relative' : 'absolute');
        return $this->cache[$k];

      case 'runtime':
        return \Civi\Core\Container::getBootService('runtime')->{$name};

      case 'boot-svc':
        $this->cache[$k] = \Civi\Core\Container::getBootService($name);
        return $this->cache[$k];

      case 'local':
        $this->initLocals();
        return $this->locals[$name];

      case 'user-system':
        $userSystem = \Civi\Core\Container::getBootService('userSystem');
        $this->cache[$k] = call_user_func([$userSystem, $name]);
        return $this->cache[$k];

      case 'service':
        return \Civi::service($name);

      case 'callback':
        // Array(0 => $type, 1 => $obj, 2 => $getter, 3 => $setter, 4 => $unsetter).
        if (!isset($this->map[$k][1], $this->map[$k][2])) {
          throw new \CRM_Core_Exception("Cannot find getter for property CRM_Core_Config::\${$k}");
        }
        return \Civi\Core\Resolver::singleton()->call([$this->map[$k][1], $this->map[$k][2]], [$k]);

      default:
        throw new \CRM_Core_Exception("Cannot read property CRM_Core_Config::\${$k} ($type)");
    }
  }

  /**
   * Set value.
   *
   * @param string $k
   * @param mixed $v
   *
   * @throws \CRM_Core_Exception
   */
  public function __set($k, $v) {
    if (!isset($this->map[$k])) {
      throw new \CRM_Core_Exception("Cannot set unrecognized property CRM_Core_Config::\${$k}");
    }
    unset($this->cache[$k]);
    $type = $this->map[$k][0];

    switch ($type) {
      case 'setting':
      case 'setting-path':
      case 'setting-url':
      case 'path':
      case 'user-system':
      case 'runtime':
      case 'callback':
      case 'boot-svc':
        // In the past, changes to $config were not persisted automatically.
        $this->cache[$k] = $v;
        return;

      case 'local':
        $this->initLocals();
        $this->locals[$k] = $v;
        return;

      default:
        throw new \CRM_Core_Exception("Cannot set property CRM_Core_Config::\${$k} ($type)");
    }
  }

  /**
   * Is value set.
   *
   * @param string $k
   *
   * @return bool
   */
  public function __isset($k) {
    return isset($this->map[$k]);
  }

  /**
   * Unset value.
   *
   * @param string $k
   *
   * @throws \CRM_Core_Exception
   */
  public function __unset($k) {
    if (!isset($this->map[$k])) {
      throw new \CRM_Core_Exception("Cannot unset unrecognized property CRM_Core_Config::\${$k}");
    }
    unset($this->cache[$k]);
    $type = $this->map[$k][0];
    $name = $this->map[$k][1] ?? $k;

    switch ($type) {
      case 'setting':
      case 'setting-path':
      case 'setting-url':
        $this->getSettings()->revert($k);
        return;

      case 'local':
        $this->initLocals();
        $this->locals[$name] = NULL;
        return;

      case 'callback':
        // Array(0 => $type, 1 => $obj, 2 => $getter, 3 => $setter, 4 => $unsetter).
        if (!isset($this->map[$k][1], $this->map[$k][4])) {
          throw new \CRM_Core_Exception("Cannot find unsetter for property CRM_Core_Config::\${$k}");
        }
        \Civi\Core\Resolver::singleton()->call([$this->map[$k][1], $this->map[$k][4]], [$k]);
        return;

      default:
        throw new \CRM_Core_Exception("Cannot unset property CRM_Core_Config::\${$k} ($type)");
    }
  }

  /**
   * @return \Civi\Core\SettingsBag
   */
  protected function getSettings() {
    if ($this->settings === NULL) {
      $this->settings = Civi::settings();
    }
    return $this->settings;
  }

  /**
   * Initialise local settings.
   */
  private function initLocals() {
    if ($this->locals === NULL) {
      $this->locals = [
        'inCiviCRM' => FALSE,
        'doNotResetCache' => 0,
        'keyDisable' => FALSE,
        'userFrameworkFrontend' => FALSE,
        'userPermissionTemp' => NULL,
      ];
    }
  }

}
