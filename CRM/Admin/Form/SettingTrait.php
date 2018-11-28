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

  /**
   * Add fields in the metadata to the template.
   */
  protected function addFieldsDefinedInSettingsMetadata() {
    $settingMetaData = $this->getSettingsMetaData();
    $descriptions = [];
    foreach ($settingMetaData as $setting => $props) {
      if (isset($props['quick_form_type'])) {
        if (isset($props['pseudoconstant'])) {
          $options = civicrm_api3('Setting', 'getoptions', [
            'field' => $setting,
          ]);
        }
        else {
          $options = NULL;
        }
        //Load input as readonly whose values are overridden in civicrm.settings.php.
        if (Civi::settings()->getMandatory($setting)) {
          $props['html_attributes']['readonly'] = TRUE;
          $this->includesReadOnlyFields = TRUE;
        }

        $add = 'add' . $props['quick_form_type'];
        if ($add == 'addElement') {
          $this->$add(
            $props['html_type'],
            $setting,
            ts($props['title']),
            ($options !== NULL) ? $options['values'] : CRM_Utils_Array::value('html_attributes', $props, []),
            ($options !== NULL) ? CRM_Utils_Array::value('html_attributes', $props, []) : NULL
          );
        }
        elseif ($add == 'addSelect') {
          $this->addElement('select', $setting, ts($props['title']), $options['values'], CRM_Utils_Array::value('html_attributes', $props));
        }
        elseif ($add == 'addCheckBox') {
          $this->addCheckBox($setting, ts($props['title']), $options['values'], NULL, CRM_Utils_Array::value('html_attributes', $props), NULL, NULL, ['&nbsp;&nbsp;']);
        }
        elseif ($add == 'addChainSelect') {
          $this->addChainSelect($setting, [
            'label' => ts($props['title']),
          ]);
        }
        elseif ($add == 'addMonthDay') {
          $this->add('date', $setting, ts($props['title']), CRM_Core_SelectValues::date(NULL, 'M d'));
        }
        else {
          $this->$add($setting, ts($props['title']));
        }
        // Migrate to using an array as easier in smart...
        $descriptions[$setting] = ts($props['description']);
        $this->assign("{$setting}_description", ts($props['description']));
        if ($setting == 'max_attachments') {
          //temp hack @todo fix to get from metadata
          $this->addRule('max_attachments', ts('Value should be a positive number'), 'positiveInteger');
        }
        if ($setting == 'maxFileSize') {
          //temp hack
          $this->addRule('maxFileSize', ts('Value should be a positive number'), 'positiveInteger');
        }

      }
    }
    // setting_description should be deprecated - see Mail.tpl for metadata based tpl.
    $this->assign('setting_descriptions', $descriptions);
    $this->assign('settings_fields', $settingMetaData);
  }

}
