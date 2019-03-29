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

namespace Civi\Core;

/**
 * Class SettingsManager
 * @package Civi\Core
 *
 * The SettingsManager is responsible for tracking settings across various
 * domains and users.
 *
 * Generally, for any given setting, there are three levels where values
 * can be declared:
 *
 *   - Mandatory values (which come from a global $civicrm_setting).
 *   - Explicit values (which are chosen by the user and stored in the DB).
 *   - Default values (which come from the settings metadata).
 *
 * Note: During the early stages of bootstrap, default values are not be available.
 * Loading the defaults requires loading metadata from various sources. However,
 * near the end of bootstrap, one calls SettingsManager::useDefaults() to fetch
 * and merge the defaults.
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
   * @var
   *   Array (int $id => SettingsBag $bag).
   */
  protected $bagsByDomain = [], $bagsByContact = [];

  /**
   * @var array|NULL
   *   Array(string $entity => array(string $settingName => mixed $value)).
   *   Ex: $mandatory['domain']['uploadDir'].
   *   NULL means "autoload from $civicrm_setting".
   */
  protected $mandatory = NULL;

  /**
   * Whether to use defaults.
   *
   * @var bool
   */
  protected $useDefaults = FALSE;

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
    if (!$this->useDefaults) {
      $this->useDefaults = TRUE;

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
   * re-scan the global $civicrm_setting.
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
   * @param int|NULL $domainId
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
   * @param int|NULL $domainId
   *   For the default domain, leave $domainID as NULL.
   * @param int|NULL $contactId
   *   For the default/active user's contact, leave $domainID as NULL.
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
    if (!$this->useDefaults) {
      return self::getSystemDefaults($entity);
    }

    $cacheKey = 'defaults_' . $entity;
    $defaults = $this->cache->get($cacheKey);
    if (!is_array($defaults)) {
      $specs = SettingsMetadata::getMetadata([
        'is_contact' => ($entity === 'contact' ? 1 : 0),
      ]);
      $defaults = [];
      foreach ($specs as $key => $spec) {
        $defaults[$key] = \CRM_Utils_Array::value('default', $spec);
      }
      \CRM_Utils_Array::extend($defaults, self::getSystemDefaults($entity));
      $this->cache->set($cacheKey, $defaults);
    }
    return $defaults;
  }

  /**
   * Get a list of mandatory/overriden settings.
   *
   * @param string $entity
   *   Ex: 'domain' or 'contact'.
   * @return array
   *   Array(string $settingName => mixed $value).
   */
  protected function getMandatory($entity) {
    if ($this->mandatory === NULL) {
      $this->mandatory = self::parseMandatorySettings(\CRM_Utils_Array::value('civicrm_setting', $GLOBALS));
    }
    return $this->mandatory[$entity];
  }

  /**
   * Parse mandatory settings.
   *
   * In previous versions, settings were broken down into verbose+dynamic group names, e.g.
   *
   *   $civicrm_settings['Foo Bar Preferences']['foo'] = 'bar';
   *
   * We now simplify to two simple groups, 'domain' and 'contact'.
   *
   *    $civicrm_settings['domain']['foo'] = 'bar';
   *
   * However, the old groups are grand-fathered in as aliases.
   *
   * @param array $civicrm_setting
   *   Ex: $civicrm_setting['Group Name']['field'] = 'value'.
   *   Group names are an historical quirk; ignore them.
   * @return array
   */
  public static function parseMandatorySettings($civicrm_setting) {
    $result = [
      'domain' => [],
      'contact' => [],
    ];

    $rewriteGroups = [
      //\CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::CAMPAIGN_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::MEMBER_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::PERSONAL_PREFERENCES_NAME => 'contact',
      'Personal Preferences' => 'contact',
      //\CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME => 'domain',
      //\CRM_Core_BAO_Setting::URL_PREFERENCES_NAME => 'domain',
      'domain' => 'domain',
      'contact' => 'contact',
    ];

    if (is_array($civicrm_setting)) {
      foreach ($civicrm_setting as $oldGroup => $values) {
        $newGroup = isset($rewriteGroups[$oldGroup]) ? $rewriteGroups[$oldGroup] : 'domain';
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
    \Civi::cache('settings')->flush(); // SettingsMetadata; not guaranteed to use same cache.

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
   * Get a list of critical system defaults.
   *
   * The setting system can be modified by extensions, which means that it's not fully available
   * during bootstrap -- in particular, defaults cannot be loaded. For a very small number of settings,
   * we must define defaults before the system bootstraps.
   *
   * @param string $entity
   *
   * @return array
   */
  private static function getSystemDefaults($entity) {
    $defaults = [];
    switch ($entity) {
      case 'domain':
        $defaults = [
          'installed' => FALSE,
          'enable_components' => ['CiviEvent', 'CiviContribute', 'CiviMember', 'CiviMail', 'CiviReport', 'CiviPledge'],
          'customFileUploadDir' => '[civicrm.files]/custom/',
          'imageUploadDir' => '[civicrm.files]/persist/contribute/',
          'uploadDir' => '[civicrm.files]/upload/',
          'imageUploadURL' => '[civicrm.files]/persist/contribute/',
          'extensionsDir' => '[civicrm.files]/ext/',
          'extensionsURL' => '[civicrm.files]/ext/',
          'resourceBase' => '[civicrm.root]/',
          'userFrameworkResourceURL' => '[civicrm.root]/',
        ];
        break;

    }
    return $defaults;
  }

}
