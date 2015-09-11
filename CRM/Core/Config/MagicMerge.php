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

  private $runtime, $locals;

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
    return array(
      'backtrace' => array('setting', 'backtrace'),
      'countryLimit' => array('setting', 'countryLimit'),
      'dashboardCacheTimeout' => array('setting', 'dashboardCacheTimeout'),
      'dateInputFormat' => array('setting', 'dateInputFormat'),
      'dateformatDatetime' => array('setting', 'dateformatDatetime'),
      'dateformatFull' => array('setting', 'dateformatFull'),
      'dateformatPartial' => array('setting', 'dateformatPartial'),
      'dateformatTime' => array('setting', 'dateformatTime'),
      'dateformatYear' => array('setting', 'dateformatYear'),
      'debug' => array('setting', 'debug_enabled'), // renamed.
      'defaultContactCountry' => array('setting', 'defaultContactCountry'),
      'defaultContactStateProvince' => array('setting', 'defaultContactStateProvince'),
      'defaultCurrency' => array('setting', 'defaultCurrency'),
      'defaultSearchProfileID' => array('setting', 'defaultSearchProfileID'),
      'doNotAttachPDFReceipt' => array('setting', 'doNotAttachPDFReceipt'),
      'empoweredBy' => array('setting', 'empoweredBy'),
      'enableComponents' => array('setting', 'enable_components'), // renamed.
      'enableSSL' => array('setting', 'enableSSL'),
      'fatalErrorHandler' => array('setting', 'fatalErrorHandler'),
      'fieldSeparator' => array('setting', 'fieldSeparator'),
      'fiscalYearStart' => array('setting', 'fiscalYearStart'),
      'geoAPIKey' => array('setting', 'geoAPIKey'),
      'geoProvider' => array('setting', 'geoProvider'),
      'includeAlphabeticalPager' => array('setting', 'includeAlphabeticalPager'),
      'includeEmailInName' => array('setting', 'includeEmailInName'),
      'includeNickNameInName' => array('setting', 'includeNickNameInName'),
      'includeOrderByClause' => array('setting', 'includeOrderByClause'),
      'includeWildCardInName' => array('setting', 'includeWildCardInName'),
      'inheritLocale' => array('setting', 'inheritLocale'),
      'languageLimit' => array('setting', 'languageLimit'),
      'lcMessages' => array('setting', 'lcMessages'),
      'legacyEncoding' => array('setting', 'legacyEncoding'),
      'logging' => array('setting', 'logging'),
      'mailThrottleTime' => array('setting', 'mailThrottleTime'),
      'mailerBatchLimit' => array('setting', 'mailerBatchLimit'),
      'mailerJobSize' => array('setting', 'mailerJobSize'),
      'mailerJobsMax' => array('setting', 'mailerJobsMax'),
      'mapAPIKey' => array('setting', 'mapAPIKey'),
      'mapProvider' => array('setting', 'mapProvider'),
      'maxFileSize' => array('setting', 'maxFileSize'),
      'maxAttachments' => array('setting', 'max_attachments'), // renamed.
      'monetaryDecimalPoint' => array('setting', 'monetaryDecimalPoint'),
      'monetaryThousandSeparator' => array('setting', 'monetaryThousandSeparator'),
      'moneyformat' => array('setting', 'moneyformat'),
      'moneyvalueformat' => array('setting', 'moneyvalueformat'),
      'provinceLimit' => array('setting', 'provinceLimit'),
      'recaptchaOptions' => array('setting', 'recaptchaOptions'),
      'recaptchaPublicKey' => array('setting', 'recaptchaPublicKey'),
      'recaptchaPrivateKey' => array('setting', 'recaptchaPrivateKey'),
      'secondDegRelPermissions' => array('setting', 'secondDegRelPermissions'),
      'smartGroupCacheTimeout' => array('setting', 'smartGroupCacheTimeout'),
      'timeInputFormat' => array('setting', 'timeInputFormat'),
      'userFrameworkLogging' => array('setting', 'userFrameworkLogging'),
      'userFrameworkUsersTableName' => array('setting', 'userFrameworkUsersTableName'),
      'verpSeparator' => array('setting', 'verpSeparator'),
      'wkhtmltopdfPath' => array('setting', 'wkhtmltopdfPath'),
      'wpBasePage' => array('setting', 'wpBasePage'),
      'wpLoadPhp' => array('setting', 'wpLoadPhp'),

      'doNotResetCache' => array('local', 'doNotResetCache'),
      'inCiviCRM' => array('local', 'inCiviCRM'),
      'userFrameworkFrontend' => array('local', 'userFrameworkFrontend'),
      'initialized' => array('local', 'initialized'),

      'dsn' => array('runtime', 'dsn'),
      'userFramework' => array('runtime', 'userFramework'),
      'userFrameworkBaseURL' => array('runtime', 'userFrameworkBaseURL'),
      'userFrameworkClass' => array('runtime', 'userFrameworkClass'),
      'userFrameworkDSN' => array('runtime', 'userFrameworkDSN'),
      'useFrameworkRelativeBase' => array('runtime', 'useFrameworkRelativeBase'),
      'userFrameworkURLVar' => array('runtime', 'userFrameworkURLVar'),
      'userPermissionClass' => array('runtime', 'userPermissionClass'),
      'userPermissionTemp' => array('runtime', 'userPermissionTemp'),
      'userSystem' => array('runtime', 'userSystem'),
      'userHookClass' => array('runtime', 'userHookClass'),
      'cleanURL' => array('runtime', 'cleanURL'),
      'configAndLogDir' => array('runtime', 'configAndLogDir'),
      'templateCompileDir' => array('runtime', 'templateCompileDir'),
      'templateDir' => array('runtime', 'templateDir'),

      'customFileUploadDir' => array('callback', 'CRM_Core_Config_Defaults', 'getCustomFileUploadDir', 'setPath', 'revert'),
      'customPHPPathDir' => array('callback', 'CRM_Core_Config_Defaults', 'getCustomPhpPathDir', 'setPath', 'revert'),
      'customTemplateDir' => array('callback', 'CRM_Core_Config_Defaults', 'getCustomTemplateDir', 'setPath', 'revert'),
      'extensionsDir' => array('callback', 'CRM_Core_Config_Defaults', 'getExtensionsDir', 'setPath', 'revert'),
      'imageUploadDir' => array('callback', 'CRM_Core_Config_Defaults', 'getImageUploadDir', 'setPath', 'revert'),
      'uploadDir' => array('callback', 'CRM_Core_Config_Defaults', 'getUploadDir', 'setPath'),

      'customCSSURL' => array('callback', 'CRM_Core_Config_Defaults', 'getCustomCssUrl', 'setUrl', 'revert'),
      'extensionsURL' => array('callback', 'CRM_Core_Config_Defaults', 'getExtensionsUrl', 'setUrl', 'revert'),
      'imageUploadURL' => array('callback', 'CRM_Core_Config_Defaults', 'getImageUploadUrl', 'setUrl', 'revert'),
      'resourceBase' => array('callback', 'CRM_Core_Config_Defaults', 'getResourceBase', 'setUrl', 'revert'),
      'userFrameworkResourceURL' => array('callback', 'CRM_Core_Config_Defaults', 'getUserFrameworkResourceUrl', 'setUrl', 'revert'),

      'geocodeMethod' => array('callback', 'CRM_Utils_Geocode', 'getProviderClass'),
      'defaultCurrencySymbol' => array('callback', 'CRM_Core_Config_Defaults', 'getDefaultCurrencySymbol'),
      //'customFileUploadDir' => array('runtime', 'customFileUploadDir'),
      //'customPHPPathDir' => array('runtime', 'customPHPPathDir'),
      //'customTemplateDir' => array('runtime', 'customTemplateDir'),
      //'extensionsDir' => array('runtime', 'extensionsDir'),
      //'imageUploadDir' => array('runtime', 'imageUploadDir'),
      //'uploadDir' => array('runtime', 'uploadDir'),
      //
      //'customCSSURL' => array('runtime', 'customCSSURL'),
      //'extensionsURL' => array('runtime', 'extensionsURL'),
      //'imageUploadURL' => array('runtime', 'imageUploadURL'),
      //'resourceBase' => array('runtime', 'resourceBase'),
      //'userFrameworkResourceURL' => array('runtime', 'userFrameworkResourceURL'),
      //
      //'geocodeMethod' => array('runtime', 'geocodeMethod'),
      //'defaultCurrencySymbol' => array('runtime', 'defaultCurrencySymbol'),
    );
  }

  public function __get($k) {
    if (!isset($this->map[$k])) {
      throw new \CRM_Core_Exception("Cannot read unrecognized property CRM_Core_Config::\${$k}.");
    }
    list ($type, $name) = $this->map[$k];

    switch ($type) {
      case 'setting':
        return \Civi::settings()->get($name);

      case 'setting-path':
        return \Civi::settings()->getPath($name);

      case 'setting-url':
        return \Civi::settings()->getUrl($name, 'absolute');

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
    list ($type, $name) = $this->map[$k];

    switch ($type) {
      case 'setting':
        \Civi::settings()->set($name, $v);
        return;

      case 'setting-path':
        \Civi::settings()->setPath($name, $v);
        return;

      case 'setting-url':
        \Civi::settings()->setUrl($name, $v);
        return;

      case 'runtime':
        $this->getRuntime()->{$name} = $v;
        return;

      case 'local':
        $this->initLocals();
        $this->locals[$name] = $v;
        return;

      case 'callback':
        // Array(0 => $type, 1 => $obj, 2 => $getter, 3 => $setter, 4 => $unsetter).
        if (!isset($this->map[$k][1], $this->map[$k][3])) {
          throw new \CRM_Core_Exception("Cannot find setter for property CRM_Core_Config::\${$k}");
        }
        \Civi\Core\Resolver::singleton()->call(array($this->map[$k][1], $this->map[$k][3]), array($k, $v));
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
    list ($type, $name) = $this->map[$k];

    switch ($type) {
      case 'setting':
      case 'setting-path':
      case 'setting-url':
        \Civi::settings()->revert($k);
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
