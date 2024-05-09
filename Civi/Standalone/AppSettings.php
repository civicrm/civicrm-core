<?php

namespace Civi\Standalone;

use Civi\Standalone\AppSettings\PathLoader;
use Civi\Standalone\AppSettings\DsnLoader;

/**
 * CiviCRM App Settings loader
 *
 * Provides a wrapper for loading CiviCRM application settings
 *
 *
 * Some bootstrapping that happened in civicrm.settings.php is moved here (set memory limit + php paths)
 *
 * In here:
 * - can be upgraded automatically in core version releases
 * - can't be tweaked by users (without patching core)
 * - can't be broken by editing yours settings.php files
 *
 * In *.settings.php:
 * - opposite of above
 */
class AppSettings {

  /**
   * Constants to define.
   *
   * constant name => default value
   *
   * Value will be taken from first of:
   * - already defined constant / value set with AppSettings::enforce( )
   * - value from env var
   * - value set with AppSettings::set( ) [ if called multiple times, only the last call will be respected ]
   * - default value provided here
   */
  public const CONSTANTS = [
    'CIVICRM_INSTALLED' => NULL,
    // does this make sense as default?
    'CIVICRM_UF' => 'Standalone',
    // civicrm database
    'CIVICRM_DSN' => NULL,
    'CIVICRM_DB_HOST' => 'localhost',
    'CIVICRM_DB_PORT' => 3306,
    'CIVICRM_DB_NAME' => 'civicrm',
    'CIVICRM_DB_USER' => 'civicrm',
    'CIVICRM_DB_PASS' => NULL,
    'CIVICRM_DB_SSL' => NULL,
    // UF database
    'CIVICRM_UF_DSN' => NULL,
    'CIVICRM_UF_DB_HOST' => 'localhost',
    'CIVICRM_UF_DB_PORT' => 3306,
    'CIVICRM_UF_DB_NAME' => 'civicrm',
    'CIVICRM_UF_DB_USER' => 'civicrm',
    'CIVICRM_UF_DB_PASS' => NULL,
    'CIVICRM_UF_DB_SSL' => NULL,
    // logging database
    'CIVICRM_LOGGING_DSN' => NULL,
    'CIVICRM_LOGGING_DB_HOST' => 'localhost',
    'CIVICRM_LOGGING_DB_PORT' => 3306,
    'CIVICRM_LOGGING_DB_NAME' => 'civicrm',
    'CIVICRM_LOGGING_DB_USER' => 'civicrm',
    'CIVICRM_LOGGING_DB_PASS' => NULL,
    'CIVICRM_LOGGING_DB_SSL' => NULL,
    // database config - should this be 'auto' ?
    'DB_DSN_MODE' => NULL,
    'CIVICRM_DEADLOCK_RETRIES' => 3,

    // smarty
    'CIVICRM_SMARTY_DEFAULT_ESCAPE' => FALSE,
    //'CIVICRM_SMARTY_AUTOLOAD_PATH' => NULL,
    'CIVICRM_MAIL_SMARTY' => 0,

    // site vars
    'CIVICRM_SITE_HOST' => NULL,
    'CIVICRM_SITE_SCHEME' => NULL,
    // this is a path? but maybe we can manage without it
    'CIVICRM_CMSDIR' => NULL,

    'CIVICRM_CLEANURL' => 0,

    // crypto
    'CIVICRM_SITE_KEY' => NULL,
    'CIVICRM_CRED_KEYS' => NULL,
    'CIVICRM_DEPLOY_ID' => NULL,
    'CIVICRM_SIGN_KEYS' => NULL,

    // caching
    'CIVICRM_DB_CACHE_CLASS' => 'ArrayCache',
    'CIVICRM_DB_CACHE_HOST' => 'localhost',
    // this is the best default for Memcache or APCCache - need to set explicitly for redis
    'CIVICRM_DB_CACHE_PORT' => 11211,
    'CIVICRM_DB_CACHE_PASSWORD' => NULL,
    'CIVICRM_DB_CACHE_TIMEOUT' => 3600,
    'CIVICRM_DB_CACHE_PREFIX' => '',
    'CIVICRM_PSR16_STRICT' => FALSE,

    // localisation
    'CIVICRM_LANGUAGE_MAPPING_FR' => NULL,
    'CIVICRM_LANGUAGE_MAPPING_EN' => NULL,
    'CIVICRM_LANGUAGE_MAPPING_ES' => NULL,
    'CIVICRM_LANGUAGE_MAPPING_PT' => NULL,
    'CIVICRM_LANGUAGE_MAPPING_ZH' => NULL,
    'CIVICRM_LANGUAGE_MAPPING_NL' => NULL,
    'CIVICRM_GETTEXT_NATIVE' => NULL,

    // logging
    'CIVICRM_LOG_HASH' => TRUE,
    'CIVICRM_LOG_ROTATESIZE' => NULL,
    // enabling will stop mail being sent '[civicrm.log]/mail.log',
    'CIVICRM_MAIL_LOG' => NULL,
    'CIVICRM_MAIL_LOG_AND_SEND' => NULL,

    // domain id
    'CIVICRM_DOMAIN_ID' => 1,

    // system compilation / cacheing
    'CIVICRM_TEMPLATE_COMPILE_CHECK' => FALSE,
    // TODO: set to auto for unittests
    'CIVICRM_CONTAINER_CACHE' => NULL,
    // note - this is ignored if PHP_OS is windows
    'CIVICRM_EXCLUDE_DIRS_PATTERN' => '@/(\.|node_modules|js/|css/|bower_components|packages/|sites/default/files/private)@',

    // system resources
    'CIVICRM_MINIMUM_PHP_MEMORY' => '128M',
  ];

  /**
   * Path settings are split out as they often have two settings generated as a pair
   * .e.g CIVICRM_PATH_EXTENSIONS and CIVICRM_URL_EXTENSIONS=
   *
   * It is possible to use tokens referring to other paths and these will be resolved before
   * the paths are set.
   *
   * Urls will also be resolved based on their path
   *
   * At the moment token replacement wont work for values provided using
   * AppSettings::enforce or direct defines
   *
   * @see \Civi\Standalone\AppSettings\PathLoader
   */

  public const PATHS = [
    // provided to initialise
    'app_root' => [
      'hasUrl' => FALSE,
    ],
    'settings' => [
      'hasUrl' => FALSE,
      'default' => '[app_root]/settings',
    ],
    // paths
    'private' => [
      'hasUrl' => FALSE,
      'default' => '[app_root]/private',
    ],
    'compile'      => [
      'hasUrl' => FALSE,
      'default' => '[private]/compiler_cache',
    ],
    'private_uploads' => [
      'hasUrl' => FALSE,
      'default'  => '[private]/uploads',
    ],
    'tmp'          => [
      'hasUrl' => FALSE,
      'default' => '[private]/tmp',
    ],
    'log'          => [
      'hasUrl' => FALSE,
      'default' => '[private]/log',
    ],
    'translations' => [
      'hasUrl' => FALSE,
      'default' => '[private]/translations',
    ],
    // paths with URLS
    'web_root'    => [
      'hasUrl' => TRUE,
      // this will be overridden by $_SERVER['DOCUMENT_ROOT'] if available
      'default' => '[app_root]',
    ],
    // note: corresponds to civicrm.files
    'public'      => [
      'hasUrl' => TRUE,
      'default' => '[web_root]/public',
    ],
    'public_uploads' => [
      'hasUrl' => TRUE,
      'default' => '[public]/uploads',
    ],
    'extensions' => [
      'hasUrl' => TRUE,
      'default' => '[web_root]/extensions',
    ],
    // TODO should we use the userFrameworkResourceUrl for core url and anything under it?
    // or will the asset builder plugin work this out?
    'core'        => [
      'hasUrl' => TRUE,
      'default' => '[app_root]/core',
    ],
    'bower'       => [
      'hasUrl' => TRUE,
      'default' => '[core]/bower_components',
    ],
    'vendor'      => [
      'hasUrl' => TRUE,
      // TODO we should check composer for these
      'default' => '[core]/vendor',
    ],
    'packages'    => [
      'hasUrl' => TRUE,
      // 'default' => '[core]/packages', /check dynamically
    ],
    'setup'       => [
      'hasUrl' => TRUE,
      'default' => '[core]/setup',
    ],
    'smarty_autoload' => [
      'hasUrl' => FALSE,
      'default' => 'Smarty/Smarty.class.php',
    ],
  ];


  /**
   * Domain settings which can be overrided at boot
   *
   * setting name => civicrm_settings['domain'] key
   *
   * (The setting name should canonically be CIVICRM_SETTING_{SETTING_KEY_IN_BIG_SNAKE_CASE}
   *
   * Value will be taken from:
   * - already defined constant / value set with AppSettings::enforce( )
   * - value from env var
   * - value set with AppSettings::set( ) [ if called multiple times, only the last call will be respected ]
   *
   * Otherwise no override will be set (database value will be used)
   */
  public const DOMAIN_SETTINGS_OVERRIDES = [
    // exception to the CIVICRM_SETTING_ naming because this feels more general?
    'CIVICRM_ENVIRONMENT' => 'environment',
    // Override the Temporary Files directory.
    'CIVICRM_SETTING_UPLOAD_DIR' => 'uploadDir',
    // Override the custom files upload directory.
    'CIVICRM_SETTING_CUSTOM_FILE_UPLOAD_DIR' => 'customFileUploadDir',
    // Override the images directory.
    'CIVICRM_SETTING_IMAGE_UPLOAD_DIR' => 'imageUploadDir',
    // Override the custom templates directory.
    'CIVICRM_SETTING_CUSTOM_TEMPLATE_DIR' => 'customTemplateDir',
    // Override the Custom php path directory.
    'CIVICRM_SETTING_CUSTOM_PHP_PATH_DIR' => 'customPHPPathDir',
    // Override the extensions directory.
    'CIVICRM_SETTING_EXTENSIONS_DIR' => 'extensionsDir',
    // Override the resource url
    'CIVICRM_SETTING_USER_FRAMEWORK_RESOURCE_URL' => 'userFrameworkResourceURL',
    // Override the Image Upload URL (System Settings > Resource URLs)
    'CIVICRM_SETTING_IMAGE_UPLOAD_URL' => 'imageUploadURL',
    // Override the Custom CiviCRM CSS URL
    'CIVICRM_SETTING_CUSTOM_CSS_URL' => 'customCSSURL',
    // Override the extensions resource URL
    'CIVICRM_SETTING_EXTENSIONS_URL' => 'extensionsURL',
    // Disable display of Community Messages on home dashboard
    'CIVICRM_SETTING_COMMUNITY_MESSAGES_URL' => 'communityMessagesUrl',
    // Disable automatic download / installation of extensions
    'CIVICRM_SETTING_EXT_REPO_URL' => 'ext_repo_url',
    // set triggers to be managed offline per CRM-18212
    'CIVICRM_SETTING_LOGGING_NO_TRIGGER_PERMISSION' => 'logging_no_trigger_permission',
  ];

  public const DEPRECATED_ALIASES = [
    'CIVICRM_TEMPLATE_COMPILEDIR' => 'CIVICRM_PATH_COMPILE',
    'CIVICRM_UF_BASEURL' => 'CIVICRM_URL_WEB_ROOT',
    // rename so we can use tokens
    'CIVICRM_SMARTY_AUTOLOAD_PATH' => 'CIVICRM_PATH_SMARTY_AUTOLOAD',
    'CIVICRM_SMARTY_3_AUTOLOAD_PATH' => 'CIVICRM_PATH_SMARTY_AUTOLOAD',
  ];

  /**
   * Names of known settings that are valid to get/set
   * @var string[]
   */
  protected array $validSettingNames;
  /**
   * values for settings set
   * @var mixed[]
   */
  protected array $settingsSet = [];
  /**
   * $settingsEnforced values of settings enforced
   * note: we dont use this in practice but helps keey track
   * @var mixed[]
   */
  protected array $settingsEnforced = [];

  protected PathLoader $pathLoader;

  protected static ?AppSettings $singleton = NULL;

  protected function __construct() {
    $this->pathLoader = new PathLoader(self::PATHS);

    $pathSettings = $this->pathLoader->getPathAndUrlSettings();

    // for checking assignments are valid
    $this->validSettingNames = array_merge(
      array_keys(self::CONSTANTS),
      array_keys(self::DOMAIN_SETTINGS_OVERRIDES),
      array_keys($pathSettings)
    );

    // set the default values from self::CONSTANTS and pathSettings
    $this->settingsSet = array_merge(
      self::CONSTANTS,
      $pathSettings
    );
  }

  public static function singleton(): AppSettings {
    if (!self::$singleton) {
      throw new \CRM_Core_Exception('AppSettings must be initialised before use.');
    }

    return self::$singleton;
  }

  protected function getValidPreferredKey(string $settingName): ?string {
    if (in_array($settingName, $this->validSettingNames)) {
      // valid key - return it;
      return $settingName;
    }
    // not a recognised key
    // return preferred name if exists - otherwise return NULL
    return self::DEPRECATED_ALIASES[$settingName] ?? NULL;
  }

  public static function set(string $settingName, mixed $value): void {
    $settingName = self::$singleton->getValidPreferredKey($settingName);
    if (!$settingName) {
      // not a valid key - should we warn somehow?
      return;
    }
    self::$singleton->settingsSet[$settingName] = $value;
  }

  public static function enforce(string $settingName, mixed $value): void {
    $settingName = self::$singleton->getValidPreferredKey($settingName);
    if (!$settingName) {
      // not a valid key or alias
      // given its supposed to be enforced
      // this seems bad enough to bail out
      throw new \CRM_Core_Exception("Cannot enforce value for {$settingName} because this isn't a valid app setting name");
    }

    if (defined($settingName)) {
      // it might be fine that its already defined if the value is
      // what we want it to be - let's check it
      if (constant($settingName) !== $value) {
        throw new \CRM_Core_Exception("Conflicting values enforced for {$settingName}! Check for conflicting AppSettingsLoader::enforce and php define calls");
      }
    }
    else {
      // define it now so the value is enforced
      define($settingName, $value);
    }

    // this is just to track which settings have been explicitly enforced
    // using this class - which might be useful for validation
    self::$singleton->settingsEnforced[$settingName] = $value;
  }

  /**
   * Get value by checking Constant (enforced value), then Environment, then values that have been ::set()
   *
   * Note: once load is completed everything should be defined so will return at first step
   *
   * Should we limit to valid keys?:
   *
   * @return mixed
   */
  public static function get(string $settingName) {
    $settingName = self::singleton()->getValidPreferredKey($settingName);
    if (!$settingName) {
      // doesn't exist? should we warn or error?
      return NULL;
    }

    if (defined($settingName)) {
      return constant($settingName);
    }

    return self::getCastedEnvVar($settingName)
      ?? self::singleton()->settingsSet[$settingName]
      ?? NULL;
  }

  /**
   * Env vars are always strings - so this does some basic casting for bool / NULL values
   *
   * todo: support parsing basic arrays maybe?
   *
   * note: could be non-static if we wanted to have any config options for casting?
   * @return string|bool|int|null
   */
  protected static function getCastedEnvVar($settingName) {
    $value = getenv($settingName);
    if ($value === FALSE || $value === '') {
      return NULL;
    }
    if (strtoupper($value) === 'FALSE') {
      return FALSE;
    }
    if (strtoupper($value) === 'TRUE') {
      return TRUE;
    }
    if (is_numeric($value)) {
      return (int) $value;
    }
    return $value;
  }

  public static function initialise(string $appRootPath): AppSettings {
    if (self::$singleton) {
      throw new \CRM_Core_Exception('AppSettings already initialised.');
    }

    self::$singleton = new self();

    // app root path is provided from the boot file
    self::set('CIVICRM_PATH_APP_ROOT', $appRootPath);

    // a hook to allow tweaking the available keys + defaults based
    // on dynamic environment (e.g. UF functions)
    self::$singleton->tweakDefaults();

    return self::singleton();
  }

  public function load(array $settingsPaths = []): AppSettings {

    // gather settings set / enforced in settings files
    $this->loadSettingsFiles($settingsPaths);

    // this checks for any values that have been defined
    // explicitly using old constant names
    $this->loadDeprecatedConstants();

    // resolve database connection strings
    // from component parts
    $this->resolveDsns();

    // resolve path tokens and derive urls
    $this->resolvePathsAndUrls();

    // other depencency chains
    $this->resolveOtherValueChains();

    // some kind of verification here? though only essentials because this will happen every page load
    // we could do more periodic checks by inspecting AppSettingsLoader::get()

    return $this;
  }

  /**
   * Steps which effect the global state.
   * They are separated from load in order that people
   * can "opt out" if necessary
   */
  public function export(array $params = []): AppSettings {
    // now get effective values and define them as constants so they are final
    $this->exportFinalValues();

    // export old settings names as well
    $this->exportDeprecatedConstants();

    // put path settings into global $civicrm_paths , $civicrm_root
    // TODO: could values be consumed directly from here?
    $this->exportPaths();

    // put settings overrides into global $civicrm_setting
    // TODO could values be consumed directly from here?
    $this->exportDomainSettingsOverrides();

    // additional things that have to be set
    $this->ensureMemoryLimitAtLeast(self::get('CIVICRM_MINIMUM_PHP_MEMORY'));
    $this->updatePhpPathIncludes(self::get('CIVICRM_MINIMUM_PHP_MEMORY'));

    return $this;
  }

  /**
   * Some dynamic tweaks to defaults based on runtime
   */
  protected function tweakDefaults(): void {
    // exclude paths is never set on Windows for some reason
    // TODO: could we just ignore it wherever it is consumed?
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      // removing it from valid keys means an error will be thrown if someone tries to enforced
      // and will be ignored when finalising if set (so never read)
      $this->validSettingNames = array_diff($this->validSettingNames, ['CIVICRM_EXCLUDE_DIRS_PATTERN']);
    }

    // defaults for CIVICRM_CLEANURL are based on UF
    // TODO could this be moved to System classes?
    if (function_exists('variable_get') && variable_get('clean_url', '0') != '0') {
      self::set('CIVICRM_CLEANURL', 1);
    }
    elseif (function_exists('config_get') && config_get('system.core', 'clean_url') != 0) {
      self::set('CIVICRM_CLEANURL', 1);
    }
    elseif (function_exists('get_option') && get_option('permalink_structure') != '') {
      self::set('CIVICRM_CLEANURL', 1);
    }
  }

  protected function loadSettingsFiles(array $settingsFiles) {
    foreach ($settingsFiles as $file) {
      require_once $file;
    }
  }

  protected function loadDeprecatedConstants() {
    foreach (self::DEPRECATED_ALIASES as $oldName => $newName) {
      if (defined($oldName)) {
        define($newName, constant($oldName));
      }
    }
  }

  protected function resolveDsns() {
    foreach (['CIVICRM', 'CIVICRM_UF', 'CIVICRM_LOGGING'] as $database) {
      DsnLoader::resolveDsn($database);
    }
    foreach (['CIVICRM_UF', 'CIVICRM_LOGGING'] as $database) {
      // UF and logging databases default to CIVICRM database
      if (!self::get($database . '_DSN')) {
        self::set($database . '_DSN', self::get('CIVICRM_DSN'));
      }
    }
  }

  protected function resolveOtherValueChains() {
    /**
    * Standalone urls are always clean :)
    */
    if (self::get('CIVICRM_UF') === 'Standalone') {
      self::set('CIVICRM_CLEANURL', 1);
    }

    // adapt to UnitTest environment might go here?
    // if CIVICRM_UF === 'UnitTests'
    // self::enforce(x y z):
  }

  protected function exportFinalValues() {
    foreach ($this->validSettingNames as $key) {
      $finalValue = self::get($key);
      if (!is_null($finalValue) && !defined($key)) {
        define($key, $finalValue);
      }
    }
  }

  protected function exportDeprecatedConstants() {
    foreach (self::DEPRECATED_ALIASES as $oldName => $newName) {
      if (!defined($oldName)) {
        define($oldName, constant($newName));
      }
    }
  }

  protected function exportPaths() {
    global $civicrm_root, $civicrm_paths;
    $civicrm_root = self::getPath('core');
    $civicrm_paths = $this->pathLoader->getCorePaths();

    global $civicrm_setting;

    foreach ($this->pathLoader->getDomainLevelSettings() as $settingKey => $value) {
      if (!is_null($value)) {
        $civicrm_setting['domain'][$settingKey] = $value;
      }
    }
  }

  protected function exportDomainSettingsOverrides() {
    global $civicrm_setting;

    foreach (self::DOMAIN_SETTINGS_OVERRIDES as $appSettingName => $domainSettingKey) {
      $value = self::get($appSettingName);

      if (!is_null($value)) {
        $civicrm_setting['domain'][$domainSettingKey] = $value;
      }
    }
  }

  public function updatePhpPathIncludes() {
    $updatedIncludePath = implode(PATH_SEPARATOR, [
      // '.' // @todo why was this included from civicrm.settings.php? what would it refer to?
      self::getPath('app_root'),
      self::getPath('packages'),
      get_include_path(),
    ]);

    if (set_include_path($updatedIncludePath) === FALSE) {
      throw new \CRM_Core_Exception("Could not set the include path<p>");
    }
  }

  protected function ensureMemoryLimitAtLeast(string $minLimit) {
    // make sure the memory_limit is at least 128 MB
    $memLimitString = trim(ini_get('memory_limit'));
    $memLimitUnit   = strtolower(substr($memLimitString, -1));
    $memLimit       = (int) $memLimitString;
    switch ($memLimitUnit) {
      case 'g': $memLimit *= 1024;
      case 'm': $memLimit *= 1024;
      case 'k': $memLimit *= 1024;
    }
    if ($memLimit >= 0 and $memLimit < 134217728) {
      ini_set('memory_limit', $minLimit);
    }
  }

  /**
   * Pass settings values into the PathLoader, and then run resolution
   * to replace tokens and derive urls from paths where required
   */
  protected function resolvePathsAndUrls(): void {
    // get settings path loader knows about
    $pathSettings = $this->pathLoader->getPathAndUrlSettings();

    // get up to date values
    foreach ($pathSettings as $settingName => $value) {
      $pathSettings[$settingName] = self::get($settingName);
    }
    $this->pathLoader->setPathsAndUrlsFromSettings($pathSettings);

    $this->pathLoader->resolvePathsAndUrls();

    $updatedSettings = $this->pathLoader->getPathAndUrlSettings();

    foreach ($updatedSettings as $settingName => $value) {
      // we enforce the resolved settings to ensure the detokenised values take preference over
      // tokenised values provided in env vars
      // however this will fail if tokenised values are enforced as the constants
      // will already be set! TODO: buffer defining constants for enforced values
      try {
        self::enforce($settingName, $value);
      }
      catch (\CRM_Core_Exception $e) {
        // ignore for now
      }
    }
  }

  /**
   * Get the value of path setting corresponding to path key
   *
   * e.g. pathKey = core => path setting = CIVICRM_PATH_CORE
   *
   * Note: these paths are not final until resolvePathsAndUrls is called
   * - they may contain tokens etc prior to this
   *
   * @return ?string
   */
  public static function getPath(string $pathKey): ?string {
    return self::get('CIVICRM_PATH_' . strtoupper($pathKey));
  }

  /**
   * Get the value of url setting corresponding to path key
   *
   * e.g. pathKey = core => url setting = CIVICRM_URL_CORE
   *
   * Note: these urls are not final until resolvePathsAndUrls is called
   * They may be null until they are derived from the corresponding path
   * setting
   *
   * @return ?string
   */
  public static function getUrl(string $pathKey): ?string {
    return self::get('CIVICRM_URL_' . strtoupper($pathKey));
  }

}
