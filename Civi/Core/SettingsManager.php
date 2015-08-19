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
 * Class SettingsManager
 * @package Civi\Core
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
  protected $bagsByDomain = array(), $bagsByContact = array();

  /**
   * @var array
   */
  protected $mandatory = NULL;

  /**
   * @param \CRM_Utils_Cache_Interface $cache
   * @param NULL|array $mandatory
   */
  public function __construct($cache, $mandatory = NULL) {
    $this->cache = $cache;
    $this->mandatory = $mandatory;
  }

  /**
   * @param int $domainId
   * @return SettingsBag
   */
  public function getBagByDomain($domainId) {
    if ($domainId === NULL) {
      $domainId = \CRM_Core_Config::domainID();
    }

    if (!isset($this->bagsByDomain[$domainId])) {
      $defaults = $this->getDefaults('domain');
      // Filter $mandatory to only include domain-settings.
      $mandatory = \CRM_Utils_Array::subset($this->getMandatory(), array_keys($defaults));
      $this->bagsByDomain[$domainId] = new SettingsBag($domainId, NULL, $defaults, $mandatory);
      $this->bagsByDomain[$domainId]->load();
    }
    return $this->bagsByDomain[$domainId];
  }

  /**
   * @param int $domainId
   * @param int $contactId
   * @return SettingsBag
   */
  public function getBagByContact($domainId, $contactId) {
    if ($domainId === NULL) {
      $domainId = \CRM_Core_Config::domainID();
    }

    $key = "$domainId:$contactId";
    if (!isset($this->bagsByContact[$key])) {
      $defaults = $this->getDefaults('contact');
      // Filter $mandatory to only include domain-settings.
      $mandatory = \CRM_Utils_Array::subset($this->getMandatory(), array_keys($defaults));
      $this->bagsByContact[$key] = new SettingsBag($domainId, $contactId, $defaults, $mandatory);
      $this->bagsByContact[$key]->load();
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
  public function getDefaults($entity) {
    $cacheKey = 'defaults:' . $entity;
    $defaults = $this->cache->get($cacheKey);
    if (!is_array($defaults)) {
      $specs = \CRM_Core_BAO_Setting::getSettingSpecification(NULL, array(
        'is_contact' => ($entity === 'contact' ? 1 : 0),
      ));
      $defaults = array();
      foreach ($specs as $key => $spec) {
        $defaults[$key] = \CRM_Utils_Array::value('default', $spec);
      }
      $this->cache->set($cacheKey, $defaults);
    }
    return $defaults;
  }

  /**
   * Get a list of mandatory/overriden settings.
   *
   * @return array
   *   Array(string $settingName => mixed $value).
   */
  public function getMandatory() {
    if ($this->mandatory === NULL) {
      if (isset($GLOBALS['civicrm_setting'])) {
        $this->mandatory = self::parseMandatorySettings($GLOBALS['civicrm_setting']);
      }
      else {
        $this->mandatory = array();
      }
    }
    return $this->mandatory;
  }

  /**
   * Parse
   *
   * @param array $civicrm_setting
   *   Ex: $civicrm_setting['Group Name']['field'] = 'value'.
   *   Group names are an historical quirk; ignore them.
   * @return array
   */
  public static function parseMandatorySettings($civicrm_setting) {
    $tmp = array();
    if (is_array($civicrm_setting)) {
      foreach ($civicrm_setting as $group => $settings) {
        foreach ($settings as $k => $v) {
          if ($v !== NULL) {
            $tmp[$k] = $v;
          }
        }
      }
      return $tmp;
    }
    return $tmp;
  }

}
