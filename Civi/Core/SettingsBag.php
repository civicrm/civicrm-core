<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Class SettingsBag
 * @package Civi\Core
 *
 * Read and write settings for a given domain (or contact).
 *
 * If the target entity does not already have a value for the setting, then
 * the defaults will be used. If mandatory values are provided, they will
 * override any defaults or custom settings.
 *
 * It's expected that the SettingsBag will have O(50-250) settings -- and that
 * we'll load the full bag on many page requests. Consequently, we don't
 * want the full metadata (help text and version history and HTML widgets)
 * for all 250 settings, but we do need the default values.
 *
 * This class is not usually instantiated directly. Instead, use SettingsManager
 * or Civi::settings().
 *
 * @see \Civi::settings()
 * @see SettingsManagerTest
 */
class SettingsBag {

  protected $domainId;

  protected $contactId;

  /**
   * @var array
   *   Array(string $settingName => mixed $value).
   */
  protected $defaults;

  /**
   * @var array
   *   Array(string $settingName => mixed $value).
   */
  protected $mandatory;

  /**
   * The result of combining default values, mandatory
   * values, and user values.
   *
   * @var array|NULL
   *   Array(string $settingName => mixed $value).
   */
  protected $combined;

  /**
   * @var array
   */
  protected $values;

  /**
   * @param int $domainId
   *   The domain for which we want settings.
   * @param int|NULL $contactId
   *   The contact for which we want settings. Use NULL for domain settings.
   */
  public function __construct($domainId, $contactId) {
    $this->domainId = $domainId;
    $this->contactId = $contactId;
    $this->values = array();
    $this->combined = NULL;
  }

  /**
   * Set/replace the default values.
   *
   * @param array $defaults
   *   Array(string $settingName => mixed $value).
   * @return SettingsBag
   */
  public function loadDefaults($defaults) {
    $this->defaults = $defaults;
    $this->combined = NULL;
    return $this;
  }

  /**
   * Set/replace the mandatory values.
   *
   * @param array $mandatory
   *   Array(string $settingName => mixed $value).
   * @return SettingsBag
   */
  public function loadMandatory($mandatory) {
    $this->mandatory = $mandatory;
    $this->combined = NULL;
    return $this;
  }

  /**
   * Load all explicit settings that apply to this domain or contact.
   *
   * @return SettingsBag
   */
  public function loadValues() {
    // Note: Don't use DAO child classes. They require fields() which require
    // translations -- which are keyed off settings!

    $this->values = array();
    $this->combined = NULL;

    // Ordinarily, we just load values from `civicrm_setting`. But upgrades require care.
    // In v4.0 and earlier, all values were stored in `civicrm_domain.config_backend`.
    // In v4.1-v4.6, values were split between `civicrm_domain` and `civicrm_setting`.
    // In v4.7+, all values are stored in `civicrm_setting`.
    // Whenever a value is available in civicrm_setting, it will take precedence.

    $isUpgradeMode = \CRM_Core_Config::isUpgradeMode();

    if ($isUpgradeMode && empty($this->contactId) && \CRM_Core_DAO::checkFieldExists('civicrm_domain', 'config_backend', FALSE)) {
      $config_backend = \CRM_Core_DAO::singleValueQuery('SELECT config_backend FROM civicrm_domain WHERE id = %1',
        array(1 => array($this->domainId, 'Positive')));
      $oldSettings = \CRM_Upgrade_Incremental_php_FourSeven::convertBackendToSettings($this->domainId, $config_backend);
      \CRM_Utils_Array::extend($this->values, $oldSettings);
    }

    // Normal case. Aside: Short-circuit prevents unnecessary query.
    if (!$isUpgradeMode || \CRM_Core_DAO::checkTableExists('civicrm_setting')) {
      $dao = \CRM_Core_DAO::executeQuery($this->createQuery()->toSQL());
      while ($dao->fetch()) {
        $this->values[$dao->name] = ($dao->value !== NULL) ? unserialize($dao->value) : NULL;
      }
    }

    return $this;
  }

  /**
   * Add a batch of settings. Save them.
   *
   * @param array $settings
   *   Array(string $settingName => mixed $settingValue).
   * @return SettingsBag
   */
  public function add(array $settings) {
    foreach ($settings as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  /**
   * Get a list of all effective settings.
   *
   * @return array
   *   Array(string $settingName => mixed $settingValue).
   */
  public function all() {
    if ($this->combined === NULL) {
      $this->combined = $this->combine(
        array($this->defaults, $this->values, $this->mandatory)
      );
    }
    return $this->combined;
  }

  /**
   * Determine the effective value.
   *
   * @param string $key
   * @return mixed
   */
  public function get($key) {
    $all = $this->all();
    return isset($all[$key]) ? $all[$key] : NULL;
  }

  /**
   * Determine the default value of a setting.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return mixed|NULL
   */
  public function getDefault($key) {
    return isset($this->defaults[$key]) ? $this->defaults[$key] : NULL;
  }

  /**
   * Determine the explicitly designated value, regardless of
   * any default or mandatory values.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return mixed|NULL
   */
  public function getExplicit($key) {
    return (isset($this->values[$key]) ? $this->values[$key] : NULL);
  }

  /**
   * Determine the mandatory value of a setting.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return mixed|NULL
   */
  public function getMandatory($key) {
    return isset($this->mandatory[$key]) ? $this->mandatory[$key] : NULL;
  }

  /**
   * Determine if the entity has explicitly designated a value.
   *
   * Note that get() may still return other values based on
   * mandatory values or defaults.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return bool
   */
  public function hasExplict($key) {
    // NULL means no designated value.
    return isset($this->values[$key]);
  }

  /**
   * Removes any explicit settings. This restores the default.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return SettingsBag
   */
  public function revert($key) {
    // It might be better to DELETE (to avoid long-term leaks),
    // but setting NULL is simpler for now.
    return $this->set($key, NULL);
  }

  /**
   * Add a single setting. Save it.
   *
   * @param string $key
   *   The simple name of the setting.
   * @param mixed $value
   *   The new, explicit value of the setting.
   * @return SettingsBag
   */
  public function set($key, $value) {
    $this->setDb($key, $value);
    $this->values[$key] = $value;
    $this->combined = NULL;
    return $this;
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  protected function createQuery() {
    $select = \CRM_Utils_SQL_Select::from('civicrm_setting')
      ->select('id, name, value, domain_id, contact_id, is_domain, component_id, created_date, created_id')
      ->where('domain_id = #id', array(
        'id' => $this->domainId,
      ));
    if ($this->contactId === NULL) {
      $select->where('is_domain = 1');
    }
    else {
      $select->where('contact_id = #id', array(
        'id' => $this->contactId,
      ));
      $select->where('is_domain = 0');
    }
    return $select;
  }

  /**
   * Combine a series of arrays, excluding any
   * null values. Later values override earlier
   * values.
   *
   * @param array $arrays
   *   List of arrays to combine.
   * @return array
   */
  protected function combine($arrays) {
    $combined = array();
    foreach ($arrays as $array) {
      foreach ($array as $k => $v) {
        if ($v !== NULL) {
          $combined[$k] = $v;
        }
      }
    }
    return $combined;
  }

  /**
   * Update the DB record for this setting.
   *
   * @param string $name
   *   The simple name of the setting.
   * @param mixed $value
   *   The new value of the setting.
   */
  protected function setDb($name, $value) {
    if (\CRM_Core_BAO_Setting::isUpgradeFromPreFourOneAlpha1()) {
      // civicrm_setting table is not going to be present.
      return;
    }

    $fields = array();
    $fieldsToSet = \CRM_Core_BAO_Setting::validateSettingsInput(array($name => $value), $fields);
    //We haven't traditionally validated inputs to setItem, so this breaks things.
    //foreach ($fieldsToSet as $settingField => &$settingValue) {
    //  self::validateSetting($settingValue, $fields['values'][$settingField]);
    //}

    $metadata = $fields['values'][$name];

    $dao = new \CRM_Core_DAO_Setting();
    $dao->name = $name;
    $dao->domain_id = $this->domainId;
    if ($this->contactId) {
      $dao->contact_id = $this->contactId;
      $dao->is_domain = 0;
    }
    else {
      $dao->is_domain = 1;
    }
    $dao->find(TRUE);

    if (isset($metadata['on_change'])) {
      foreach ($metadata['on_change'] as $callback) {
        call_user_func(
          \Civi\Core\Resolver::singleton()->get($callback),
          unserialize($dao->value),
          $value,
          $metadata,
          $this->domainId
        );
      }
    }

    if (\CRM_Utils_System::isNull($value)) {
      $dao->value = 'null';
    }
    else {
      $dao->value = serialize($value);
    }

    if (!isset(\Civi::$statics[__CLASS__]['upgradeMode'])) {
      \Civi::$statics[__CLASS__]['upgradeMode'] = \CRM_Core_Config::isUpgradeMode();
    }
    if (\Civi::$statics[__CLASS__]['upgradeMode'] && \CRM_Core_DAO::checkFieldExists('civicrm_setting', 'group_name')) {
      $dao->group_name = 'placeholder';
    }

    $dao->created_date = \CRM_Utils_Time::getTime('YmdHis');

    $session = \CRM_Core_Session::singleton();
    if (\CRM_Contact_BAO_Contact_Utils::isContactId($session->get('userID'))) {
      $dao->created_id = $session->get('userID');
    }

    if ($dao->id) {
      $dao->save();
    }
    else {
      // Cannot use $dao->save(); in upgrade mode (eg WP + Civi 4.4=>4.7), the DAO will refuse
      // to save the field `group_name`, which is required in older schema.
      \CRM_Core_DAO::executeQuery(\CRM_Utils_SQL_Insert::dao($dao)->toSQL());
    }
    $dao->free();
  }

}
