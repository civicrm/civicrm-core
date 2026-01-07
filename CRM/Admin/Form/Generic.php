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

/**
 * Generic metadata based settings form.
 *
 * The form filter will determine the settings displayed.
 */
class CRM_Admin_Form_Generic extends CRM_Core_Form {
  use CRM_Admin_Form_SettingTrait;

  protected $_settings = [];

  /**
   * List of sections, keyed by name.
   * Sections can be added using hook_civicrm_preProcess
   *
   * @var array[]
   *   {title: string, icon: string, weight: int, description: string, docUrl: array}
   */
  public $sections = [];

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  public function preProcess() {
    $filter = $this->getSettingPageFilter();
    // Start with fully-resolved metadata for all settings; this allows callbacks to add/remove themselves from pages
    $allSettings = \Civi\Core\SettingsMetadata::getMetadata(NULL, NULL, TRUE, FALSE, TRUE);
    $this->_settings = array_filter($allSettings, function ($setting) use ($filter) {
      return !empty($setting['settings_pages'][$filter]);
    });
  }

  /**
   * Set default values for the form.
   *
   * Default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $this->setDefaultsForMetadataDefinedFields();
    return $this->_defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $filter = $this->getSettingPageFilter();

    // Sections can be defined in child class preprocess function, and/or modified with hook_civicrm_preProcess
    $sections = $this->sections;
    // "catch-all" default section for uncategorized settings
    $sections['default']['weight'] = PHP_INT_MIN;

    // Sort settings into sections & add to form
    foreach ($this->_settings as $settingName => &$setting) {
      // If element was already added by a form class override, don't add it again
      $added = $this->elementExists($settingName) || $this->addSettingFieldToForm($settingName, $setting);
      if ($added) {
        $placement = $setting['settings_pages'][$filter];
        $sectionName = $placement['section'] ?? 'default';
        $setting['weight'] = $placement['weight'] ?? 0;
        $sections[$sectionName]['fields'][$settingName] = $setting;
      }
    }
    // Remove sections with no fields
    $sections = array_filter($sections, fn($section) => !empty($section['fields']));
    // Sort fields by weight
    foreach ($sections as &$section) {
      uasort($section['fields'], ['CRM_Utils_Sort', 'cmpFunc']);
    }
    // Sort sections by weight
    uasort($sections, ['CRM_Utils_Sort', 'cmpFunc']);

    $this->assign('readOnlyFields', $this->readOnlyFields);
    $this->assign('settingPageName', $filter);
    $this->assign('settingSections', $sections);

    $this->addFormRule([self::class, 'genericSettingsFormRule'], $this->_settings);

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    try {
      $this->saveMetadataDefinedSettings($params);
      CRM_Core_Session::setStatus(ts('Settings Saved.'), ts('Saved'), 'success');
      CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Save Failed'), 'error');
    }
  }

  /**
   * Setting validation form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   * @param array $settings
   *   Settings metadata
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function genericSettingsFormRule($fields, $files, $settings) {
    $errors = [];

    foreach ($settings as $settingName => $settingMeta) {
      if (!empty($settingMeta['validate_callback']) && isset($fields[$settingName])) {
        $errorText = NULL;
        $callback = Civi\Core\Resolver::singleton()->get($settingMeta['validate_callback']);
        // These validate_callbacks are inconsistent. Some return FALSE, others throw an Exception.
        try {
          $value = self::formatSettingValue($settingMeta, $fields[$settingName]);
          $valid = call_user_func_array($callback, [$value, $settingMeta]);
        }
        catch (CRM_Core_Exception $e) {
          $valid = FALSE;
          $errorText = $e->getMessage();
        }
        if (!$valid) {
          $errors[$settingName] = $errorText ?: ts('Invalid value for %1', [1 => $settingMeta['title']]);
        }
      }
      // Add validation for number fields. Don't worry too much about the message text as it shouldn't ever be seen; number inputs are constrained clientside.
      if ($settingMeta['type'] === 'Integer' && is_string($fields[$settingName] ?? NULL) && strlen($fields[$settingName])) {
        if (!CRM_Utils_Rule::integer($fields[$settingName])) {
          $errors[$settingName] = ts('Invalid value for %1', [1 => $settingMeta['title']]);
        }
        if (isset($settingMeta['html_attributes']['min']) && $fields[$settingName] < $settingMeta['html_attributes']['min']) {
          $errors[$settingName] = ts('Invalid value for %1', [1 => $settingMeta['title']]);
        }
        if (isset($settingMeta['html_attributes']['max']) && $fields[$settingName] > $settingMeta['html_attributes']['max']) {
          $errors[$settingName] = ts('Invalid value for %1', [1 => $settingMeta['title']]);
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

}
