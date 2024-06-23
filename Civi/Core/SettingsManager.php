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
 *   - Mandatory values (from an environment variable).
 *   - Mandatory values (set using global variable $civicrm_setting).
 *   - Explicit values (which are chosen by the user and stored in the DB).
 *   - Default values (which come from the settings metadata).
 *
 * Note: During the early stages of bootstrap, we run a limited "bootOnly" version
 * of the SettingsManager, to get values which are critical for boot.
 *
 * Loading the defaults requires loading metadata from various sources. However,
 * near the end of bootstrap, one calls SettingsManager::useDefaults() to fetch
 * and merge all the other defaults.
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
   * Whether we are ready to use all defaults (ie. system is booted)
   *
   * @var bool
   */
  protected $useAllDefaults = FALSE;

  /**
   * @param \CRM_Utils_Cache_Interface $cache
   *   A semi-durable location to store metadata.
   */
  public function __construct($cache) {
    $this->cache = $cache;
  }

  /**
   * Ensure that all defaults values are included with
   * all current and future bags.
   *
   * @return SettingsManager
   */
  public function useDefaults() {
    if (!$this->useAllDefaults) {
      $this->useAllDefaults = TRUE;

      if (!empty($this->bagsByDomain)) {
        foreach ($this->bagsByDomain as $bag) {
          /** @var SettingsBag $bag */
          $bag->loadDefaults($this->getDefaults('domain'));
        }
      }

      if (!empty($this->bagsByContact)) {
        foreach ($this->bagsByContact as $bag) {
          /** @var SettingsBag $bag */
          $bag->loadDefaults($this->getDefaults('contact'));
        }
      }
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
      if (\CRM_Core_Config::singleton()->dsn) {
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
      if (\CRM_Core_Config::singleton()->dsn) {
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
    // caching isnt available during boot
    $cacheKey = $this->useAllDefaults ? 'defaults_' . $entity : NULL;
    $defaults = $cacheKey ? $this->cache->get($cacheKey) : NULL;

    if (!is_array($defaults)) {
      $defaults = [];

      $specs = SettingsMetadata::getMetadata([
        'is_contact' => ($entity === 'contact' ? 1 : 0),
      ], NULL, FALSE, !$this->useAllDefaults);

      foreach ($specs as $key => $spec) {
        $defaults[$key] = $spec['default'] ?? NULL;
      }

      if ($cacheKey) {
        $this->cache->set($cacheKey, $defaults);
      }
    }
    return $defaults;
  }

  /**
   * Get a list of mandatory/overriden settings from $civicrm_setting global
   * or environemnt.
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
      foreach (['domain', 'contact'] as $entityKey) {
        $this->mandatory[$entityKey] = array_merge(
            $this->mandatory[$entityKey],
            self::getEnvSettingValues($entity, !$this->useAllDefaults, FALSE)
        );
      }
    }
    return $this->mandatory[$entity];
  }

  /**
   * Get a settings set using environment variables
   *
   * @param string $entity
   *   Ex: 'domain' or 'contact'.
   * @param bool $bootOnly - whether to only load boot critical settings
   * @param bool $fqnKeys - whether the return array keys are setting names or setting fqns
   *
   * @return array
   *   Array(string $settingName or $settingFqn => mixed $value).
   */
  protected static function getEnvSettingValues($entity, $bootOnly, $fqnKeys = FALSE) {
    $settings = [];

    $specs = SettingsMetadata::getMetadata([
      'is_contact' => ($entity === 'contact' ? 1 : 0),
      'load_from_env' => TRUE,
    ], NULL, FALSE, $bootOnly);

    foreach ($specs as $key => $spec) {
      $fqn = $spec['fqn'] ?? NULL;
      if ($fqn) {
        $envValue = getenv($fqn);
        if ($envValue) {
          $settings[$fqnKeys ? $fqn : $key] = $envValue;
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
   *    $civicrm_settings['domain']['foo'] = 'bar';
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



}
