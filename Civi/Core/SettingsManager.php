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

namespace Civi\Core;

/**
 * Class SettingsManager
 * @package Civi\Core
 *
 * The SettingsManager is responsible for tracking settings across various
 * domains and users.
 *
 * Generally, for any given setting, there are four levels where values
 * can be declared:
 *
 *   - Mandatory values (set using an environment variable corresponding to the setting).
 *   - Mandatory values (set using global variable $civicrm_setting).
 *   - Explicit values (which are chosen by the user and stored in the DB).
 *   - Default values (which come from the settings metadata).
 *
 * During bootstrap the SystemManager runs a limited version to
 * get values we need for bootstap - before we have full metadata from
 * the extension system, and setting values from the database.
 *
 * Near the end of bootstrap, one calls SettingsManager::bootComplete() to
 * reload the full version.
 *
 * Note: In a typical usage, there will only be one active domain and one
 * active contact (each having its own bag) within a given request. However,
 * in some edge-cases, you may need to work with multiple domains/contacts
 * at the same time.
 *
 * Note: The global $civicrm_setting is meant to provide sysadmins with a way
 * to override settings in `civicrm.settings.php`, but it has traditionally been
 * possible for extensions to manipulate $civicrm_setting in a hook. If you do
 * this, please call `useMandatory()` to tell SettingsManager to re-scan
 * $civicrm_setting.
 *
 * @see SettingsManagerTest
 */
class SettingsManager {

  protected const BOOT_PHASE_EARLY = 0;
  protected const BOOT_PHASE_MID = 1;
  protected const BOOT_PHASE_COMPLETE = 2;

  /**
   * @var \CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * @var array
   *   Array (int $id => SettingsBag $bag).
   */
  protected $bagsByDomain = [];


  /**
   * @var array
   *   Array (int $id => SettingsBag $bag).
   */
  protected $bagsByContact = [];

  /**
   * @var array|null
   *   Array(string $entity => array(string $settingName => mixed $value)).
   *   Ex: $mandatory['domain']['uploadDir'].
   *   NULL means "autoload from $civicrm_setting".
   */
  protected $mandatory = NULL;

  /**
   * The SettingsManager boots through 3 phases:
   *
   * 0. during early boot, we load metadata for a limited subset of settings
   *    in core *.boot.setting.php files. Setting values come only from
   *    environment variables, $civicrm_setting global, or default
   *
   * 1. once the database is loaded, we can load database values
   *
   * 2. once the extension system is booted, we have full and final settings
   *    metadata (including from extensions)
   *
   * @var int
   */
  protected $bootPhase = self::BOOT_PHASE_EARLY;

  /**
   * @param \CRM_Utils_Cache_Interface $cache
   *   A semi-durable location to store metadata.
   */
  public function __construct($cache) {
    $this->cache = $cache;
  }

  /**
   * Signal that the SettingsManager can now load
   * values from the DB
   *
   * @return SettingsManager
   */
  public function dbAvailable() {
    // we only need to move on the boot phase if we are
    // currently in BOOT_PHASE_EARLY
    if ($this->bootPhase === self::BOOT_PHASE_EARLY) {
      $this->bootPhase = self::BOOT_PHASE_MID;

      // if DB is newly available, reload DB values
      // for all bags
      $this->reloadValues();
    }
    return $this;
  }

  /**
   * Remove pre-boot restrictions and reload defaults/mandatory
   *
   * @return SettingsManager
   */
  public function bootComplete() {
    if ($this->bootPhase !== self::BOOT_PHASE_COMPLETE) {
      $this->bootPhase = self::BOOT_PHASE_COMPLETE;

      // on this transition, we need to reload all
      // the value layers as the metadata might have
      // changed
      $this->reloadValues()->reloadDefaults()->useMandatory();
    }
    return $this;
  }

  /**
   * Maintained as public alias of bootComplete for compatibility
   *
   * @deprecated
   * @return SettingsManager
   */
  public function useDefaults() {
    return $this->bootComplete();
  }

  /**
   * (Re)load database values for all existing settings bags
   *
   * @return SettingsManager
   */
  protected function reloadValues() {
    foreach ($this->bagsByDomain as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadValues();
    }

    foreach ($this->bagsByContact as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadValues();
    }

    return $this;
  }

  /**
   * (Re)load default values for all existing settings bags
   *
   * @return SettingsManager
   */
  protected function reloadDefaults() {
    foreach ($this->bagsByDomain as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadDefaults($this->getDefaults('domain'));
    }

    foreach ($this->bagsByContact as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadDefaults($this->getDefaults('contact'));
    }

    return $this;
  }

  /**
   * Ensure that mandatory values are included with
   * all current and future bags.
   *
   * If you call useMandatory multiple times, it will
   * re-scan the global $civicrm_setting and environment variables
   *
   * @return SettingsManager
   */
  public function useMandatory() {
    $this->mandatory = NULL;

    foreach ($this->bagsByDomain as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadMandatory($this->getMandatory('domain'));
    }

    foreach ($this->bagsByContact as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadMandatory($this->getMandatory('contact'));
    }

    return $this;
  }

  /**
   * Get Settings by domain.
   *
   * @param int|null $domainId
   *
   * @return SettingsBag
   */
  public function getBagByDomain($domainId) {
    if ($domainId === NULL) {
      $domainId = \CRM_Core_Config::domainID();
    }

    if (!isset($this->bagsByDomain[$domainId])) {
      $this->bagsByDomain[$domainId] = new SettingsBag($domainId, NULL);
      if ($this->bootPhase !== self::BOOT_PHASE_EARLY) {
        $this->bagsByDomain[$domainId]->loadValues();
      }
      $this->bagsByDomain[$domainId]
        ->loadMandatory($this->getMandatory('domain'))
        ->loadDefaults($this->getDefaults('domain'));
    }
    return $this->bagsByDomain[$domainId];
  }

  /**
   * Get Settings by contact.
   *
   * @param int|null $domainId
   *   For the default domain, leave $domainID as NULL.
   * @param int|null $contactId
   *   For the default/active user's contact, leave $domainID as NULL.
   *
   * @return SettingsBag
   * @throws \CRM_Core_Exception
   *   If there is no contact, then there's no SettingsBag, and we'll throw
   *   an exception.
   */
  public function getBagByContact($domainId, $contactId) {
    if ($domainId === NULL) {
      $domainId = \CRM_Core_Config::domainID();
    }
    if ($contactId === NULL) {
      $contactId = \CRM_Core_Session::getLoggedInContactID();
      if (!$contactId) {
        throw new \CRM_Core_Exception("Cannot access settings subsystem - user or domain is unavailable");
      }
    }

    $key = "$domainId:$contactId";
    if (!isset($this->bagsByContact[$key])) {
      $this->bagsByContact[$key] = new SettingsBag($domainId, $contactId);
      if ($this->bootPhase !== self::BOOT_PHASE_EARLY) {
        $this->bagsByContact[$key]->loadValues();
      }
      $this->bagsByContact[$key]
        ->loadDefaults($this->getDefaults('contact'))
        ->loadMandatory($this->getMandatory('contact'));
    }
    return $this->bagsByContact[$key];
  }

  /**
   * Determine the default settings.
   *
   * @param string $entity
   *   Ex: 'domain' or 'contact'.
   * @return array
   *   Array(string $settingName => mixed $value).
   */
  protected function getDefaults($entity) {
    $cacheKey = 'phase' . $this->bootPhase . '_defaults_' . $entity;
    $defaults = $this->cache->get($cacheKey);

    if (!is_array($defaults)) {
      $defaults = [];

      $specs = SettingsMetadata::getMetadata([
        'is_contact' => ($entity === 'contact' ? 1 : 0),
      ], NULL, FALSE, $this->bootPhase !== self::BOOT_PHASE_COMPLETE);

      foreach ($specs as $key => $spec) {
        $defaults[$key] = $spec['default'] ?? NULL;
      }

      $this->cache->set($cacheKey, $defaults);
    }
    return $defaults;
  }

  /**
   * Get a list of mandatory/overriden settings from $civicrm_setting global
   * or environment.
   *
   * @param string $entity
   *   Ex: 'domain' or 'contact'.
   * @return array
   *   Array(string $settingName => mixed $value).
   */
  protected function getMandatory($entity) {
    if ($this->mandatory === NULL) {
      $this->mandatory = self::parseMandatorySettingsGlobalVar($GLOBALS['civicrm_setting'] ?? NULL);

      // merge in settings from env - these take precedence over values from global
      // TODO: should we warn if env var is overriding $civicrm_setting setting?
      foreach (['domain', 'contact'] as $entityKey) {
        $this->mandatory[$entityKey] = array_merge(
            $this->mandatory[$entityKey] ?? [],
            $this->getEnvSettingValues($entity)
        );
      }
    }
    return $this->mandatory[$entity];
  }

  /**
   * Get any setting values set using environment variables
   *
   * @param string $entity
   *   Ex: 'domain' or 'contact'.
   *
   * @return array
   *   Array(string $settingName or $settingFqn => mixed $value).
   */
  protected function getEnvSettingValues($entity) {
    $settings = [];

    $specs = SettingsMetadata::getMetadata([
      'is_contact' => ($entity === 'contact' ? 1 : 0),
      'is_env_loadable' => TRUE,
    ], NULL, FALSE, $this->bootPhase !== self::BOOT_PHASE_COMPLETE);

    foreach ($specs as $key => $spec) {
      $fqn = $spec['global_name'] ?? NULL;
      if ($fqn) {
        $envValue = getenv($fqn);
        if ($envValue !== FALSE) {
          $settings[$key] = $envValue;
        }
      }
    }

    return $settings;
  }

  /**
   * Parse mandatory settings from global env var.
   *
   * In previous versions, settings were broken down into verbose+dynamic group names, e.g.
   *
   *   $civicrm_settings['Foo Bar Preferences']['foo'] = 'bar';
   *
   * We now simplify to two simple groups, 'domain' and 'contact'.
   *
   *    $civicrm_setting['domain']['foo'] = 'bar';
   *
   * 'Personal Preferences' is still aliased for compatibility (is this still needed in June 2024?).
   *
   * @param array $civicrm_setting
   *   Ex: $civicrm_setting['Group Name']['field'] = 'value'.
   *   Group names are an historical quirk; ignore them.
   * @return array
   */
  public static function parseMandatorySettingsGlobalVar($civicrm_setting) {
    $result = [
      'domain' => [],
      'contact' => [],
    ];

    $rewriteGroups = [
      'Personal Preferences' => 'contact',
      'domain' => 'domain',
      'contact' => 'contact',
    ];

    if (is_array($civicrm_setting)) {
      foreach ($civicrm_setting as $oldGroup => $values) {
        $newGroup = $rewriteGroups[$oldGroup] ?? 'domain';
        $result[$newGroup] = array_merge($result[$newGroup], $values);
      }
    }

    return $result;
  }

  /**
   * Flush all in-memory and persistent caches related to settings.
   *
   * @return SettingsManager
   */
  public function flush() {
    $this->mandatory = NULL;

    $this->cache->flush();
    // SettingsMetadata; not guaranteed to use same cache.
    \Civi::cache('settings')->flush();

    foreach ($this->bagsByDomain as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadValues();
      $bag->loadDefaults($this->getDefaults('domain'));
      $bag->loadMandatory($this->getMandatory('domain'));
    }

    foreach ($this->bagsByContact as $bag) {
      /** @var SettingsBag $bag */
      $bag->loadValues();
      $bag->loadDefaults($this->getDefaults('contact'));
      $bag->loadMandatory($this->getMandatory('contact'));
    }

    return $this;
  }

  /**
   * Load boot settings from environment and/or settings file
   *
   * @param string $settingsPath
   *    Path to the civicrm.settings.php file
   */
  public static function bootSettings($settingsPath) {
    // during bootstrap we can't use a more persistent cache
    $bootSettingsCache = \CRM_Utils_Cache::create([
      'name' => 'bootSettings',
      'type' => ['ArrayCache'],
    ]);

    $bootSettingsManager = new self($bootSettingsCache);

    // check for constants we need to define
    $bootConstants = SettingsMetadata::getMetadata(['is_contact' => 0, 'is_constant' => TRUE], NULL, FALSE, TRUE);

    // if a constant value has been set using an env var, we need
    // to jump in and define it now so the env var value takes precedence
    // over any "define" calls in the civicrm.settings.php
    // (hopefully these use if (!defined(X))))
    $envSettingsValues = $bootSettingsManager->getEnvSettingValues('domain');

    foreach ($bootConstants as $key => $meta) {
      $fqn = $meta['global_name'] ?? NULL;
      $value = $envSettingsValues[$key] ?? NULL;
      if ($fqn && !defined($fqn) && !is_null($value)) {
        define($fqn, $value);
      }
    }

    // we need to make an exceptional check for if we have a value for DSN that
    // is composed from consituent parts *before* we load the settings
    // file in order to:
    // a) ensure the env values take precedence over define('CIVICRM_DSN'...) in the settings file
    // b) provide the right source value for CIVICRM_LOGGING_DSN (set from CIVICRM_DSN in the settings file template)
    if (!defined('CIVICRM_DSN')) {
      $composedDsn = $bootSettingsManager->getBagByDomain(NULL)->get('civicrm_db_dsn');
      if ($composedDsn) {
        define('CIVICRM_DSN', $composedDsn);
      }
    }

    if (file_exists($settingsPath)) {
      if (!defined('CIVICRM_SETTINGS_PATH')) {
        define('CIVICRM_SETTINGS_PATH', $settingsPath);
      }
      require_once $settingsPath;
    }

    // get all effective values from the settings bag (resolving defaults etc)
    $effectiveValues = $bootSettingsManager->getBagByDomain(NULL)->all();

    foreach ($bootConstants as $key => $meta) {
      $fqn = $meta['global_name'] ?? NULL;
      $value = $effectiveValues[$key] ?? NULL;
      if ($fqn && !defined($fqn) && !is_null($value)) {
        define($fqn, $value);
      }
      // TODO: should we complain here if there are inconsistent defines
      // from elsewhere?
    }

    // if in doubt, the root of civicrm-core is 3 steps
    // up from this file
    global $civicrm_root;
    if (!$civicrm_root) {
      $civicrm_root = dirname(__FILE__, 3) . DIRECTORY_SEPARATOR;
    }
  }

}
