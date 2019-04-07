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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This trait allows us to consolidate Preferences & Settings forms.
 *
 * It is intended mostly as part of a refactoring process to get rid of having 2.
 */
trait CRM_Admin_Form_SettingTrait {

  /**
   * The setting page filter.
   *
   * @var string
   */
  private $_filter;

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
    $setValues = array_intersect_key($params, $this->_settings);
    // Checkboxes will be unset rather than empty so we need to add them back in.
    // Handle quickform hateability just once, right here right now.
    $unsetValues = array_diff_key($this->_settings, $params);
    foreach ($unsetValues as $key => $unsetValue) {
      if ($this->getQuickFormType($this->getSettingMetadata($key)) === 'CheckBox') {
        $setValues[$key] = [$key => 0];
      }
    }
    return $setValues;
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
   * Get the metadata for a particular field.
   *
   * @param $setting
   * @return mixed
   */
  protected function getSettingMetadata($setting) {
    return $this->getSettingsMetaData()[$setting];
  }

  /**
   * Get the metadata for a particular field for a particular item.
   *
   * e.g get 'serialize' key, if exists, for a field.
   *
   * @param $setting
   * @param $item
   * @return mixed
   */
  protected function getSettingMetadataItem($setting, $item) {
    return CRM_Utils_Array::value($item, $this->getSettingsMetaData()[$setting]);
  }

  /**
   * @return string
   */
  protected function getSettingPageFilter() {
    if (!isset($this->_filter)) {
      // Get the last URL component without modifying the urlPath property.
      $urlPath = array_values($this->urlPath);
      $this->_filter = end($urlPath);
    }
    return $this->_filter;
  }

  /**
   * Returns a re-keyed copy of the settings, ordered by weight.
   *
   * @return array
   */
  protected function getSettingsOrderedByWeight() {
    $settingMetaData = $this->getSettingsMetaData();
    $filter = $this->getSettingPageFilter();

    usort($settingMetaData, function ($a, $b) use ($filter) {
      // Handle cases in which a comparison is impossible. Such will be considered ties.
      if (
        // A comparison can't be made unless both setting weights are declared.
        !isset($a['settings_pages'][$filter]['weight'], $b['settings_pages'][$filter]['weight'])
        // A pair of settings might actually have the same weight.
        || $a['settings_pages'][$filter]['weight'] === $b['settings_pages'][$filter]['weight']
      ) {
        return 0;
      }

      return $a['settings_pages'][$filter]['weight'] > $b['settings_pages'][$filter]['weight'] ? 1 : -1;
    });

    return $settingMetaData;
  }

  /**
   * Add fields in the metadata to the template.
   */
  protected function addFieldsDefinedInSettingsMetadata() {
    $settingMetaData = $this->getSettingsMetaData();
    $descriptions = [];
    foreach ($settingMetaData as $setting => $props) {
      $quickFormType = $this->getQuickFormType($props);
      if (isset($quickFormType)) {
        $options = CRM_Utils_Array::value('options', $props);
        if (isset($props['pseudoconstant'])) {
          $options = civicrm_api3('Setting', 'getoptions', [
            'field' => $setting,
          ])['values'];
          if ($props['html_type'] === 'Select' && isset($props['is_required']) && $props['is_required'] === FALSE && !isset($options[''])) {
            // If the spec specifies the field is not required add a null option.
            // Why not if empty($props['is_required']) - basically this has been added to the spec & might not be set to TRUE
            // when it is true.
            $options = ['' => ts('None')] + $options;
          }
        }
        if ($props['type'] === 'Boolean') {
          $options = [$props['title'] => $props['name']];
        }

        //Load input as readonly whose values are overridden in civicrm.settings.php.
        if (Civi::settings()->getMandatory($setting)) {
          $props['html_attributes']['readonly'] = TRUE;
          $this->includesReadOnlyFields = TRUE;
        }

        $add = 'add' . $quickFormType;
        if ($add == 'addElement') {
          $this->$add(
            $props['html_type'],
            $setting,
            ts($props['title']),
            ($options !== NULL) ? $options : CRM_Utils_Array::value('html_attributes', $props, []),
            ($options !== NULL) ? CRM_Utils_Array::value('html_attributes', $props, []) : NULL
          );
        }
        elseif ($add == 'addSelect') {
          $this->addElement('select', $setting, ts($props['title']), $options, CRM_Utils_Array::value('html_attributes', $props));
        }
        elseif ($add == 'addCheckBox') {
          $this->addCheckBox($setting, '', $options, NULL, CRM_Utils_Array::value('html_attributes', $props), NULL, NULL, ['&nbsp;&nbsp;']);
        }
        elseif ($add == 'addCheckBoxes') {
          $options = array_flip($options);
          $newOptions = [];
          foreach ($options as $key => $val) {
            $newOptions[$key] = $val;
          }
          $this->addCheckBox($setting,
            $props['title'],
            $newOptions,
            NULL, NULL, NULL, NULL,
            ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>']
          );
        }
        elseif ($add == 'addChainSelect') {
          $this->addChainSelect($setting, [
            'label' => ts($props['title']),
          ]);
        }
        elseif ($add == 'addMonthDay') {
          $this->add('date', $setting, ts($props['title']), CRM_Core_SelectValues::date(NULL, 'M d'));
        }
        elseif ($add === 'addEntityRef') {
          $this->$add($setting, ts($props['title']), $props['entity_reference_options']);
        }
        elseif ($add === 'addYesNo' && ($props['type'] === 'Boolean')) {
          $this->addRadio($setting, ts($props['title']), [1 => 'Yes', 0 => 'No'], NULL, '&nbsp;&nbsp;');
        }
        else {
          $this->$add($setting, ts($props['title']), $options);
        }
        // Migrate to using an array as easier in smart...
        $description = CRM_Utils_Array::value('description', $props);
        $descriptions[$setting] = $description;
        $this->assign("{$setting}_description", $description);
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
    $this->assign('fields', $this->getSettingsOrderedByWeight());
  }

  /**
   * Get the quickform type for the given html type.
   *
   * @param array $spec
   *
   * @return string
   */
  protected function getQuickFormType($spec) {
    if (isset($spec['quick_form_type']) &&
    !($spec['quick_form_type'] === 'Element' && !empty($spec['html_type']))) {
      // This is kinda transitional
      return $spec['quick_form_type'];
    }

    // The spec for settings has been updated for consistency - we provide deprecation notices for sites that have
    // not made this change.
    $htmlType = $spec['html_type'];
    if ($htmlType !== strtolower($htmlType)) {
      CRM_Core_Error::deprecatedFunctionWarning(ts('Settings fields html_type should be lower case - see https://docs.civicrm.org/dev/en/latest/framework/setting/ - this needs to be fixed for ' . $spec['name']));
      $htmlType = strtolower($spec['html_type']);
    }
    $mapping = [
      'checkboxes' => 'CheckBoxes',
      'checkbox' => 'CheckBox',
      'radio' => 'Radio',
      'select' => 'Select',
      'textarea' => 'Element',
      'text' => 'Element',
      'entity_reference' => 'EntityRef',
      'advmultiselect' => 'Element',
    ];
    return $mapping[$htmlType];
  }

  /**
   * Get the defaults for all fields defined in the metadata.
   *
   * All others are pending conversion.
   */
  protected function setDefaultsForMetadataDefinedFields() {
    CRM_Core_BAO_ConfigSetting::retrieve($this->_defaults);
    foreach (array_keys($this->_settings) as $setting) {
      $this->_defaults[$setting] = civicrm_api3('setting', 'getvalue', ['name' => $setting]);
      $spec = $this->getSettingsMetadata()[$setting];
      if (!empty($spec['serialize'])) {
        $this->_defaults[$setting] = CRM_Core_DAO::unSerializeField($this->_defaults[$setting], $spec['serialize']);
      }
      if ($this->getQuickFormType($spec) === 'CheckBoxes') {
        $this->_defaults[$setting] = array_fill_keys($this->_defaults[$setting], 1);
      }
      if ($this->getQuickFormType($spec) === 'CheckBox') {
        $this->_defaults[$setting] = [$setting => $this->_defaults[$setting]];
      }
    }
  }

  /**
   * @param $params
   *
   */
  protected function saveMetadataDefinedSettings($params) {
    $settings = $this->getSettingsToSetByMetadata($params);
    foreach ($settings as $setting => $settingValue) {
      if ($this->getQuickFormType($this->getSettingMetadata($setting)) === 'CheckBoxes') {
        $settings[$setting] = array_keys($settingValue);
      }
      if ($this->getQuickFormType($this->getSettingMetadata($setting)) === 'CheckBox') {
        // This will be an array with one value.
        $settings[$setting] = (int) reset($settings[$setting]);
      }
    }
    civicrm_api3('setting', 'create', $settings);
  }

}
