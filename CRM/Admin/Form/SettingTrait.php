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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
use Civi\Api4\Setting;

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
   * Fields defined as read only.
   *
   * @var array
   */
  private $mandatoryValues = [];

  /**
   * @return array
   * @see CRM_Core_Form::getMandatoryValues()
   */
  public function getMandatoryValues(): array {
    return $this->mandatoryValues;
  }

  /**
   * Get the metadata relating to the settings on the form, ordered by the keys in $this->_settings.
   *
   * @return array
   */
  protected function getSettingsMetaData(): array {
    if (empty($this->settingsMetadata)) {
      $this->settingsMetadata = \Civi\Core\SettingsMetadata::getMetadata(['name' => array_keys($this->_settings)], NULL, TRUE, FALSE, TRUE);
      // This array_merge re-orders to the key order of $this->_settings.
      $this->settingsMetadata = array_merge($this->_settings, $this->settingsMetadata);
    }
    uasort($this->settingsMetadata, function ($a, $b) {
      return $this->isWeightHigher($a, $b);
    });
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
      $quickFormType = $this->getQuickFormType($this->getSettingMetadata($key));
      if ($quickFormType === 'CheckBoxes') {
        $setValues[$key] = [];
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
  protected function getSettingMetadata($setting): array {
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
    return $this->getSettingsMetaData()[$setting][$item] ?? NULL;
  }

  /**
   * This is public so we can retrieve the filter name via hooks etc. and apply conditional logic (eg. loading javascript conditionals).
   *
   * @return string
   */
  public function getSettingPageFilter() {
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
   * @deprecated
   * @return array
   */
  protected function getSettingsOrderedByWeight() {
    $settingMetaData = $this->getSettingsMetaData();
    // Probably unnessary to do this again.
    $settingMetaData = $this->filterMetadataByWeight($settingMetaData);

    return $settingMetaData;
  }

  /**
   * Old function, not used in CRM_Admin_Form_Generic.
   *
   * @deprecated
   * @throws \CRM_Core_Exception
   */
  protected function addFieldsDefinedInSettingsMetadata() {
    $this->addSettingsToFormFromMetadata();
    $settingMetaData = $this->getSettingsMetaData();
    $descriptions = [];
    foreach ($settingMetaData as $settingName => &$props) {
      $added = $this->addSettingFieldToForm($settingName, $props);
      if ($added) {
        // Migrate to using an array as easier in smart...
        $description = $props['description'] ?? NULL;
        $descriptions[$settingName] = $description;
        $this->assign("{$settingName}_description", $description);
      }
    }
    // setting_description should be deprecated - see Mail.tpl for metadata based tpl.
    $this->assign('setting_descriptions', $descriptions);
    $this->assign('settings_fields', $settingMetaData);
    $this->assign('fields', $this->getSettingsOrderedByWeight());

    $mandatory = $this->getMandatoryValues();
    if ($mandatory) {
      CRM_Core_Session::setStatus(ts("Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php."), '', 'info', ['expires' => 0]);
    }
  }

  protected function addSettingFieldToForm(string $settingName, array &$props) {
    $quickFormType = $this->getQuickFormType($props);
    if (isset($quickFormType)) {
      $options = $props['options'] ?? NULL;
      if ($options) {
        if ($quickFormType === 'Select' && isset($props['is_required']) && $props['is_required'] === FALSE && !isset($options[''])) {
          // If the spec specifies the field is not required add a null option.
          // Why not if empty($props['is_required']) - basically this has been added to the spec & might not be set to TRUE
          // when it is true.
          $options = ['' => ts('None')] + $options;
        }
      }
      if ($props['type'] === 'Boolean') {
        $options = [$props['title'] => $props['name']];
      }

      // Disable input when values are overridden in civicrm.settings.php.
      $mandatory = Civi::settings()->getMandatory($settingName);
      if ($mandatory !== NULL) {
        $this->mandatoryValues[$settingName] = $mandatory;
      }

      $add = 'add' . $quickFormType;
      if ($add === 'addElement') {
        $this->$add(
          $props['html_type'],
          $settingName,
          $props['title'],
          ($options !== NULL) ? $options : $props['html_attributes'] ?? [],
          ($options !== NULL) ? $props['html_attributes'] ?? [] : NULL
        );
      }
      elseif ($add === 'addSelect') {
        $this->addElement('select', $settingName, $props['title'], $options, $props['html_attributes'] ?? NULL);
      }
      elseif ($add === 'addCheckBox') {
        $this->addCheckBox($settingName, '', $options, NULL, $props['html_attributes'] ?? NULL, NULL, NULL, ['&nbsp;&nbsp;']);
      }
      elseif ($add === 'addCheckBoxes') {
        $newOptions = array_flip($options);
        $classes = 'crm-checkbox-list';
        if (!empty($props['sortable'])) {
          $classes .= ' crm-sortable-list';
          $newOptions = array_flip(self::reorderSortableOptions($settingName, $options));
        }
        $props['wrapper_element'] = ['<ul class="' . $classes . '"><li>', '</li></ul>'];
        $this->addCheckBox($settingName,
          $props['title'],
          $newOptions,
          NULL, NULL, NULL, NULL,
          '</li><li>'
        );
      }
      elseif ($add === 'addChainSelect') {
        $this->addChainSelect($settingName, ['label' => $props['title']] + $props['chain_select_settings']);
      }
      elseif ($add === 'addMonthDay') {
        $this->add('date', $settingName, $props['title'], CRM_Core_SelectValues::date(NULL, 'M d'));
      }
      elseif ($add === 'addEntityRef') {
        $this->$add($settingName, $props['title'], $props['entity_reference_options']);
      }
      elseif ($add === 'addToggle' && $props['type'] === 'Boolean') {
        $attributes = $props['html_attributes'] ?? [];
        $attributes += [
          'on' => ts('Enabled'),
          'off' => ts('Disabled'),
        ];
        $this->addToggle($settingName, $props['title'], $attributes);
      }
      elseif ($add === 'add') {
        $this->add($props['html_type'], $settingName, $props['title'], $options ?? $props['html_attributes'] ?? NULL, !empty($props['is_required']), $options ? ($props['html_attributes'] ?? NULL) : ($props['html_extra'] ?? NULL));
      }
      else {
        $this->$add($settingName, $props['title'], $options);
      }
    }
    return isset($quickFormType);
  }

  /**
   * Get the quickform type for the given html type.
   *
   * @param array $spec
   *
   * @return string
   */
  protected static function getQuickFormType($spec) {
    if (isset($spec['quick_form_type']) &&
    !($spec['quick_form_type'] === 'Element' && !empty($spec['html_type']))) {
      // This is kinda transitional
      $mapTypes = [
        'CheckBox' => 'Toggle',
        'YesNo' => 'Toggle',
      ];
      return $mapTypes[$spec['quick_form_type']] ?? $spec['quick_form_type'];
    }

    // The spec for settings has been updated for consistency - we provide deprecation notices for sites that have
    // not made this change.
    $htmlType = $spec['html_type'];
    if ($htmlType !== strtolower($htmlType)) {
      // Avoiding 'ts' for obscure strings.
      CRM_Core_Error::deprecatedFunctionWarning('Settings fields html_type should be lower case - see https://docs.civicrm.org/dev/en/latest/framework/setting/ - this needs to be fixed for ' . $spec['name']);
      $htmlType = strtolower($spec['html_type']);
    }
    $mapping = [
      'checkboxes' => 'CheckBoxes',
      'checkbox' => 'Toggle',
      'radio' => 'Radio',
      'select' => 'Select',
      'textarea' => 'Element',
      'text' => 'Element',
      'entity_reference' => 'EntityRef',
      'advmultiselect' => 'Element',
      'chainselect' => 'ChainSelect',
      'yesno' => 'Toggle',
      'toggle' => 'Toggle',
    ];
    $mapping += array_fill_keys(CRM_Core_Form::$html5Types, '');
    return $mapping[$htmlType] ?? '';
  }

  /**
   * Get the defaults for all fields defined in the metadata.
   *
   * All others are pending conversion.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setDefaultsForMetadataDefinedFields() {
    CRM_Core_BAO_ConfigSetting::retrieve($this->_defaults);
    foreach (array_keys($this->_settings) as $setting) {
      $this->_defaults[$setting] = civicrm_api3('setting', 'getvalue', ['name' => $setting]);
      $spec = $this->getSettingsMetaData()[$setting];
      if (!empty($spec['serialize']) && !is_array($this->_defaults[$setting])) {
        $this->_defaults[$setting] = CRM_Core_DAO::unSerializeField((string) $this->_defaults[$setting], $spec['serialize']);
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
   * Save any fields which have been defined via metadata.
   *
   * (Other fields are hack-handled... sadly.
   *
   * @param array $params
   *   Form input.
   *
   * @throws \CRM_Core_Exception
   */
  protected function saveMetadataDefinedSettings($params) {
    $settings = $this->getSettingsToSetByMetadata($params);
    foreach ($settings as $setting => $settingValue) {
      $settingMetaData = $this->getSettingMetadata($setting);
      $settings[$setting] = self::formatSettingValue($settingMetaData, $settingValue);
    }
    Setting::set(FALSE)->setValues($settings)->execute();
  }

  protected static function formatSettingValue(array $settingMetaData, $settingValue) {
    $quickFormType = self::getQuickFormType($settingMetaData);
    if (!empty($settingMetaData['sortable'])) {
      $settingValue = self::getReorderedSettingData($settingMetaData['name'], $settingValue);
    }
    elseif ($quickFormType === 'CheckBoxes') {
      $settingValue = array_keys($settingValue);
    }
    elseif ($quickFormType === 'Toggle') {
      $settingValue = (bool) $settingValue;
    }
    elseif ($settingMetaData['type'] === 'Integer') {
      // QuickForm is pretty slack when it comes to types, cast to an integer.
      if (is_numeric($settingValue)) {
        $settingValue = (int) $settingValue;
      }
      if (!$settingValue && empty($settingMetaData['is_required'])) {
        $settingValue = NULL;
      }
    }
    return $settingValue;
  }

  /**
   * Display options in correct order on the form
   *
   * @param $setting
   * @param $options
   * @return array
   */
  public static function reorderSortableOptions($setting, $options) {
    return array_merge(array_flip(Civi::settings()->get($setting)), $options);
  }

  /**
   * @param string $setting
   * @param array $settingValue
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  private static function getReorderedSettingData(string $setting, array $settingValue): array {
    // Get order from $_POST as $_POST maintains the order the sorted setting
    // options were sent. You can simply assign data from $_POST directly to
    // $settings[] but preference has to be given to data from Quickform.
    $order = array_keys(\CRM_Utils_Request::retrieve($setting, 'String'));
    $settingValueKeys = array_keys($settingValue);
    return array_intersect($order, $settingValueKeys);
  }

  /**
   * Add settings to form if the metadata designates they should be on the page.
   *
   * @deprecated
   * @throws \CRM_Core_Exception
   */
  protected function addSettingsToFormFromMetadata() {
    $filter = $this->getSettingPageFilter();
    $settings = civicrm_api3('Setting', 'getfields', [])['values'];
    foreach ($settings as $key => $setting) {
      if (isset($setting['settings_pages'][$filter])) {
        $this->_settings[$key] = $setting;
      }
    }
  }

  /**
   * @param array $settingMetaData
   *
   * @deprecated
   * @return array
   */
  protected function filterMetadataByWeight(array $settingMetaData): array {
    usort($settingMetaData, function ($a, $b) {
      return $this->isWeightHigher($a, $b);
    });
    return $settingMetaData;
  }

  /**
   * Is the relevant weight of b higher than a.
   *
   * @param array $a
   * @param array $b
   *
   * @return int
   */
  protected function isWeightHigher(array $a, array $b): int {
    $filter = $this->getSettingPageFilter();
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
  }

}
