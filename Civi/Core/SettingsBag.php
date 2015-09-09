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

  protected $filteredValues;

  /**
   * @param int $domainId
   *   The domain for which we want settings.
   * @param int|NULL $contactId
   *   The contact for which we want settings. Use NULL for domain settings.
   * @param array $defaults
   *   Array(string $settingName => mixed $value).
   * @param array $mandatory
   *   Array(string $settingName => mixed $value).
   */
  public function __construct($domainId, $contactId, $defaults, $mandatory) {
    $this->domainId = $domainId;
    $this->contactId = $contactId;
    $this->defaults = $defaults;
    $this->mandatory = $mandatory;
    $this->combined = NULL;
  }

  /**
   * Load all settings that apply to this domain or contact.
   *
   * @return $this
   */
  public function load() {
    $this->values = array();
    $dao = $this->createDao();
    $dao->find();
    while ($dao->fetch()) {
      $this->values[$dao->name] = ($dao->value !== NULL) ? unserialize($dao->value) : NULL;
    }
    $this->combined = NULL;
    return $this;
  }

  /**
   * Add a batch of settings. Save them.
   *
   * @param array $settings
   *   Array(string $settingName => mixed $settingValue).
   * @return $this
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
   * Get the value of a setting, formatted as a path.
   *
   * @param string $key
   * @return string|NULL
   *   Absolute path.
   */
  public function getPath($key) {
    if (!isset($this->filteredValues[$key])) {
      $this->filteredValues[$key] = $this->filterPath($this->get($key));
    }
    return $this->filteredValues[$key];
  }

  /**
   * Get the value of a setting, formatted as a URL.
   *
   * @param string $key
   * @param bool $preferFormat
   *   The preferred format ('absolute', 'relative').
   *   The result data may not meet the preference -- if the setting
   *   refers to an external domain, then the result will be
   *   absolute (regardless of preference).
   * @parma bool|NULL $ssl
   *   NULL to autodetect. TRUE to force to SSL.
   * @return string|NULL
   *   URL.
   */
  public function getUrl($key, $preferFormat, $ssl = NULL) {
    if (!isset($this->filteredValues[$key][$preferFormat][$ssl])) {
      $value = $this->filterUrl($this->get($key), $preferFormat, $ssl);
      $this->filteredValues[$key][$preferFormat][$ssl] = $value;
    }
    return $this->filteredValues[$key][$preferFormat][$ssl];
  }

  /**
   * Determine the explicitly designated value, regardless of
   * any default or mandatory values.
   *
   * @param string $key
   * @return null
   */
  public function getExplicit($key) {
    return (isset($this->values[$key]) ? $this->values[$key] : NULL);
  }

  /**
   * Determine if the entity has explicitly designated a value.
   *
   * Note that get() may still return other values based on
   * mandatory values or defaults.
   *
   * @param string $key
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
   * @return $this
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
   * @param mixed $value
   * @return $this
   */
  public function set($key, $value) {
    $this->setDb($key, $value);
    $this->values[$key] = $value;
    unset($this->filteredValues[$key]);
    $this->combined = NULL;
    return $this;
  }

  /**
   * @param string $key
   * @param string $value
   *   Absolute path.
   * @return $this
   */
  public function setPath($key, $value) {
    $this->set($key, \CRM_Utils_File::relativeDirectory($value));
    return $this;
  }

  /**
   * @param string $key
   * @param string $value
   *   Absolute URL.
   * @return $this
   */
  public function setUrl($key, $value) {
    $this->set($key, \CRM_Utils_System::relativeURL($value));
    return $this;
  }

  /**
   * @return \CRM_Core_DAO_Setting
   */
  protected function createDao() {
    $dao = new \CRM_Core_DAO_Setting();
    $dao->domain_id = $this->domainId;
    if ($this->contactId === NULL) {
      $dao->is_domain = 1;
    }
    else {
      $dao->contact_id = $this->contactId;
      $dao->is_domain = 0;
    }
    return $dao;
  }

  /**
   * Combine a series of arrays, excluding any
   * null values. Later values override earlier
   * values.
   *
   * @param $arrays
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
   * @param $key
   * @param $value
   */
  protected function setDb($name, $value) {
    $fields = array();
    $fieldsToSet = \CRM_Core_BAO_Setting::validateSettingsInput(array($name => $value), $fields);
    //We haven't traditionally validated inputs to setItem, so this breaks things.
    //foreach ($fieldsToSet as $settingField => &$settingValue) {
    //  self::validateSetting($settingValue, $fields['values'][$settingField]);
    //}
    // NOTE: We don't have any notion of createdID
    \CRM_Core_BAO_Setting::_setItem($fields['values'][$name], $value, '', $name, NULL, $this->contactId, NULL, $this->domainId);

    //$dao = $this->createDao();
    //$dao->name = $key;
    //$dao->group_name = '';
    //$dao->find();
    //$serializedValue = ($value === NULL ? 'null' : serialize($value));
    //if ($dao->value !== $serializedValue) {
    //  $dao->created_date = \CRM_Utils_Time::getTime('Ymdhis');
    //  $dao->value = $serializedValue;
    //  $dao->save();
    //}
  }

  /**
   * Filter a URL, the same way that it would be if it were read from settings.
   *
   * @param $value
   * @param $preferFormat
   * @param $ssl
   * @return mixed|string
   */
  public function filterUrl($value, $preferFormat, $ssl = NULL) {
    if ($value) {
      $value = \CRM_Utils_System::absoluteURL($value, TRUE);
    }
    if ($preferFormat === 'relative' && $value) {
      $parsed = parse_url($value);
      if (isset($_SERVER['HTTP_HOST']) && isset($parsed['host']) && $_SERVER['HTTP_HOST'] == $parsed['host']) {
        $value = $parsed['path'];
      }
    }

    if ($value) {
      if ($ssl || ($ssl === NULL && \CRM_Utils_System::isSSL())) {
        $value = str_replace('http://', 'https://', $value);
      }
    }
    return $value;
  }

  /**
   * @param string $value
   * @return bool|string
   */
  public function filterPath($value) {
    if ($value) {
      return \CRM_Utils_File::absoluteDirectory($value);
    }
    else {
      return FALSE;
    }
  }

}
