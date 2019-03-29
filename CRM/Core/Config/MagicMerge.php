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

  private $locals, $settings;

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
      'configAndLogDir' => ['runtime'],
      'templateCompileDir' => ['runtime'],
      'templateDir' => ['runtime'],

      // "boot-svc" properties are critical services needed during init.
      // See also: Civi\Core\Container::getBootService().
      'userSystem' => ['boot-svc'],
      'userPermissionClass' => ['boot-svc'],

      'userFrameworkBaseURL' => ['user-system', 'getAbsoluteBaseURL'],
      'userFrameworkVersion' => ['user-system', 'getVersion'],
      'useFrameworkRelativeBase' => ['user-system', 'getRelativeBaseURL'], // ugh typo.

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
      'debug' => ['setting', 'debug_enabled'], // renamed.
      'defaultContactCountry' => ['setting'],
      'defaultContactStateProvince' => ['setting'],
      'defaultCurrency' => ['setting'],
      'defaultSearchProfileID' => ['setting'],
      'doNotAttachPDFReceipt' => ['setting'],
      'empoweredBy' => ['setting'],
      'enableComponents' => ['setting', 'enable_components'], // renamed.
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
      'maxAttachments' => ['setting', 'max_attachments'], // renamed.
      'monetaryDecimalPoint' => ['setting'],
      'monetaryThousandSeparator' => ['setting'],
      'moneyformat' => ['setting'],
      'moneyvalueformat' => ['setting'],
      'provinceLimit' => ['setting'],
      'recaptchaOptions' => ['setting'],
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
      'wpBasePage' => ['setting'],
      'wpLoadPhp' => ['setting'],

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
      // @todo remove geocodeMethod. As of Feb 2018, $config->geocodeMethod works but gives a deprecation warning.
      'geocodeMethod' => ['callback', 'CRM_Utils_Geocode', 'getProviderClass'],
      'defaultCurrencySymbol' => ['callback', 'CRM_Core_BAO_Country', 'getDefaultCurrencySymbol'],
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
    $name = isset($this->map[$k][1]) ? $this->map[$k][1] : $k;

    switch ($type) {
      case 'setting':
        return $this->getSettings()->get($name);

      case 'setting-path':
        // Array(0 => $type, 1 => $setting, 2 => $actions).
        $value = $this->getSettings()->get($name);
        $value = Civi::paths()->getPath($value);
        if ($value) {
          $value = CRM_Utils_File::addTrailingSlash($value);
          if (isset($this->map[$k][2]) && in_array('mkdir', $this->map[$k][2])) {
            if (!is_dir($value) && !CRM_Utils_File::createDir($value, FALSE)) {
              CRM_Core_Session::setStatus(ts('Failed to make directory (%1) at "%2". Please update the settings or file permissions.', [
                1 => $k,
                2 => $value,
              ]));
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

    // If foreign name is set, use that name (except with callback types because
    // their second parameter is the object, not the foreign name).
    $name = isset($this->map[$k][1]) && $type != 'callback' ? $this->map[$k][1] : $k;

    switch ($type) {
      case 'setting':
      case 'setting-path':
      case 'setting-url':
      case 'user-system':
      case 'runtime':
      case 'callback':
      case 'boot-svc':
        // In the past, changes to $config were not persisted automatically.
        $this->cache[$name] = $v;
        return;

      case 'local':
        $this->initLocals();
        $this->locals[$name] = $v;
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
    $name = isset($this->map[$k][1]) ? $this->map[$k][1] : $k;

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
        'initialized' => FALSE,
        'userFrameworkFrontend' => FALSE,
        'userPermissionTemp' => NULL,
      ];
    }
  }

}
