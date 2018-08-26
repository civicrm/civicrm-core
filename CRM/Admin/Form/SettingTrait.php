<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This trait allows us to consolidate Preferences & Settings forms.
 *
 * It is intended mostly as part of a refactoring process to get rid of having 2.
 */
trait CRM_Admin_Form_SettingTrait {

  /**
   * @var array
   */
  protected $settingsMetadata;

  /**
   * Get default entity.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Setting';
  }

  /**
   * Get the metadata relating to the settings on the form, ordered by the keys in $this->_settings.
   *
   * @return array
   */
  protected function getSettingsMetaData() {
    if (empty($this->settingsMetadata)) {
      $allSettingMetaData = civicrm_api3('setting', 'getfields', []);
      $this->settingsMetadata = array_intersect_key($allSettingMetaData['values'], $this->_settings);
      // This array_merge re-orders to the key order of $this->_settings.
      $this->settingsMetadata = array_merge($this->_settings, $this->settingsMetadata);
    }
    return $this->settingsMetadata;
  }

  /**
   * Get the settings which can be stored based on metadata.
   *
   * @param array $params
   * @return array
   */
  protected function getSettingsToSetByMetadata($params) {
    return array_intersect_key($params, $this->_settings);
  }

  /**
   * @param $params
   */
  protected function filterParamsSetByMetadata(&$params) {
    foreach ($this->getSettingsToSetByMetadata($params) as $setting => $settingGroup) {
      //@todo array_diff this
      unset($params[$setting]);
    }
  }

}
