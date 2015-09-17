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
 * Class CRM_Core_Config_MagicMerge
 *
 * Originally, the $config object was based on a single, serialized
 * data object stored in the database. As the needs for settings
 * grew (with robust metadata, system overrides, and extension support),
 * the $config started to store a mix of:
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

  private $runtime, $locals, $settings;

  private $cache = array();

  public function __construct() {
    $this->map = self::getPropertyMap();
  }

  public function __wakeup() {
    $this->map = self::getPropertyMap();
  }

  /**
   * @return array
   */
  public static function getPropertyMap() {
    // Each mapping: $propertyName => Array(0 => $type, 1 => $foreignName|NULL, ...).
    // If $foreignName is omitted/null, then it's assumed to match the $propertyName.
    // Other parameters may be specified, depending on the type.
    return array(
      'backtrace' => array('setting'),
      'countryLimit' => array('setting'),
      'dashboardCacheTimeout' => array('setting'),
      'dateInputFormat' => array('setting'),
      'dateformatDatetime' => array('setting'),
      'dateformatFull' => array('setting'),
      'dateformatPartial' => array('setting'),
      'dateformatTime' => array('setting'),
      'dateformatYear' => array('setting'),
      'debug' => array('setting', 'debug_enabled'), // renamed.
      'defaultContactCountry' => array('setting'),
      'defaultContactStateProvince' => array('setting'),
      'defaultCurrency' => array('setting'),
      'defaultSearchProfileID' => array('setting'),
      'doNotAttachPDFReceipt' => array('setting'),
      'empoweredBy' => array('setting'),
      'enableComponents' => array('setting', 'enable_components'), // renamed.
      'enableSSL' => array('setting'),
      'fatalErrorHandler' => array('setting'),
      'fieldSeparator' => array('setting'),
      'fiscalYearStart' => array('setting'),
      'geoAPIKey' => array('setting'),
      'geoProvider' => array('setting'),
      'includeAlphabeticalPager' => array('setting'),
      'includeEmailInName' => array('setting'),
      'includeNickNameInName' => array('setting'),
      'includeOrderByClause' => array('setting'),
      'includeWildCardInName' => array('setting'),
      'inheritLocale' => array('setting'),
      'languageLimit' => array('setting'),
      'lcMessages' => array('setting'),
      'legacyEncoding' => array('setting'),
      'logging' => array('setting'),
      'mailThrottleTime' => array('setting'),
      'mailerBatchLimit' => array('setting'),
      'mailerJobSize' => array('setting'),
      'mailerJobsMax' => array('setting'),
      'mapAPIKey' => array('setting'),
      'mapProvider' => array('setting'),
      'maxFileSize' => array('setting'),
      'maxAttachments' => array('setting', 'max_attachments'), // renamed.
      'monetaryDecimalPoint' => array('setting'),
      'monetaryThousandSeparator' => array('setting'),
      'moneyformat' => array('setting'),
      'moneyvalueformat' => array('setting'),
      'provinceLimit' => array('setting'),
      'recaptchaOptions' => array('setting'),
      'recaptchaPublicKey' => array('setting'),
      'recaptchaPrivateKey' => array('setting'),
      'replyTo' => array('setting'),
      'secondDegRelPermissions' => array('setting'),
      'smartGroupCacheTimeout' => array('setting'),
      'timeInputFormat' => array('setting'),
      'userFrameworkLogging' => array('setting'),
      'userFrameworkUsersTableName' => array('setting'),
      'verpSeparator' => array('setting'),
      'versionCheck' => array('setting'),
      'wkhtmltopdfPath' => array('setting'),
      'wpBasePage' => array('setting'),
      'wpLoadPhp' => array('setting'),

      'doNotResetCache' => array('local'),
      'inCiviCRM' => array('local'),
      'userFrameworkFrontend' => array('local'),

      'dsn' => array('runtime'),
      'initialized' => array('runtime'),
      'userFramework' => array('runtime'),
      'userFrameworkBaseURL' => array('runtime'),
      'userFrameworkClass' => array('runtime'),
      'userFrameworkDSN' => array('runtime'),
      'useFrameworkRelativeBase' => array('runtime', 'useFrameworkRelativeBase'),
      'userFrameworkURLVar' => array('runtime'),
      'userFrameworkVersion' => array('runtime'),
      'userPermissionClass' => array('runtime'),
      'userPermissionTemp' => array('runtime'),
      'userSystem' => array('runtime'),
      'userHookClass' => array('runtime'),
      'cleanURL' => array('runtime'),
      'configAndLogDir' => array('runtime'),
      'templateCompileDir' => array('runtime'),
      'templateDir' => array('runtime'),

      'customFileUploadDir' => array('setting-path', NULL, '[civicrm.files]/custom/', array('mkdir', 'restrict')),
      'customPHPPathDir' => array('setting-path'),
      'customTemplateDir' => array('setting-path'),
      'extensionsDir' => array('setting-path'),
      'imageUploadDir' => array('setting-path', NULL, '[civicrm.files]/persist/contribute/', array('mkdir')),
      'uploadDir' => array('setting-path', NULL, '[civicrm.files]/upload/', array('mkdir', 'restrict')),

      'customCSSURL' => array('setting-url-abs'),
      'extensionsURL' => array('setting-url-abs'),
      'imageUploadURL' => array('setting-url-abs', NULL, '[civicrm.files]/persist/contribute/'),
      'resourceBase' => array('setting-url-rel', 'userFrameworkResourceURL', '[civicrm]/.'),
      'userFrameworkResourceURL' => array('setting-url-abs', NULL, '[civicrm]/.'),

      'geocodeMethod' => array('callback', 'CRM_Utils_Geocode', 'getProviderClass'),
      'defaultCurrencySymbol' => array('callback', 'CRM_Core_BAO_Country', 'getDefaultCurrencySymbol'),
    );
  }

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
        // Array(0 => $type, 1 => $setting, 2 => $default, 3 => $actions).
        $value = $this->getSettings()->get($name);
        if (empty($value) && isset($this->map[$k][2])) {
          $value = $this->map[$k][2];
        }
        $value = Civi::paths()->getPath($value);
        if ($value) {
          $value = CRM_Utils_File::addTrailingSlash($value);
          if (isset($this->map[$k][3]) && in_array('mkdir', $this->map[$k][3])) {
            CRM_Utils_File::createDir($value);
          }
          if (isset($this->map[$k][3]) && in_array('restrict', $this->map[$k][3])) {
            CRM_Utils_File::restrictAccess($value);
          }
        }
        $this->cache[$k] = $value;
        return $value;

      case 'setting-url-abs':
        // Array(0 => $type, 1 => $setting, 2 => $default).
        $value = $this->getSettings()->get($name);
        if (empty($value) && isset($this->map[$k][2])) {
          $value = $this->map[$k][2];
        }
        $this->cache[$k] = Civi::paths()->getUrl($value, 'absolute');
        return $this->cache[$k];

      case 'setting-url-rel':
        // Array(0 => $type, 1 => $setting, 2 => $default).
        $value = $this->getSettings()->get($name);
        if (empty($value) && isset($this->map[$k][2])) {
          $value = $this->map[$k][2];
        }
        $this->cache[$k] = Civi::paths()->getUrl($value, 'relative');
        return $this->cache[$k];

      case 'runtime':
        return $this->getRuntime()->{$name};

      case 'local':
        $this->initLocals();
        return $this->locals[$name];

      case 'service':
        return \Civi::service($name);

      case 'callback':
        // Array(0 => $type, 1 => $obj, 2 => $getter, 3 => $setter, 4 => $unsetter).
        if (!isset($this->map[$k][1], $this->map[$k][2])) {
          throw new \CRM_Core_Exception("Cannot find getter for property CRM_Core_Config::\${$k}");
        }
        return \Civi\Core\Resolver::singleton()->call(array($this->map[$k][1], $this->map[$k][2]), array($k));

      default:
        throw new \CRM_Core_Exception("Cannot read property CRM_Core_Config::\${$k} ($type)");
    }
  }

  public function __set($k, $v) {
    if (!isset($this->map[$k])) {
      throw new \CRM_Core_Exception("Cannot set unrecognized property CRM_Core_Config::\${$k}");
    }
    unset($this->cache[$k]);
    $type = $this->map[$k][0];
    $name = isset($this->map[$k][1]) ? $this->map[$k][1] : $k;

    switch ($type) {
      case 'setting':
      case 'setting-path':
      case 'setting-url-abs':
      case 'setting-url-rel':
      case 'runtime':
      case 'callback':
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

  public function __isset($k) {
    return isset($this->map[$k]);
  }

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
      case 'setting-url-abs':
      case 'setting-url-rel':
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
        \Civi\Core\Resolver::singleton()->call(array($this->map[$k][1], $this->map[$k][4]), array($k));
        return;

      default:
        throw new \CRM_Core_Exception("Cannot unset property CRM_Core_Config::\${$k} ($type)");
    }
  }

  /**
   * @return CRM_Core_Config_Runtime
   */
  protected function getRuntime() {
    if ($this->runtime === NULL) {
      $this->runtime = new CRM_Core_Config_Runtime();
    }
    return $this->runtime;
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

  private function initLocals() {
    if ($this->locals === NULL) {
      $this->locals = array(
        'inCiviCRM' => FALSE,
        'doNotResetCache' => 0,
        'initialized' => FALSE,
        'userFrameworkFrontend' => FALSE,
      );
    }
  }

}
