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
    $this->addSettingsToFormFromMetadata();
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
    $settings = $this->getSettingsMetaData();

    // Sections can be defined in child class & modified with hook_civicrm_preProcess
    $sections = $this->sections;
    // "catch-all" generic section for uncategorized settings
    $sections['']['weight'] = PHP_INT_MIN;
    // Sort settings into sections & add to form
    foreach ($settings as $settingName => &$setting) {
      $added = $this->addSettingFieldToForm($settingName, $setting);
      if ($added) {
        $placement = $setting['settings_pages'][$filter];
        $section = $placement['section'] ?? '';
        $sections[$section]['fields'][$settingName] = $setting;
      }
    }
    $sections = array_filter($sections, fn($section) => !empty($section['fields']));
    uasort($sections, ['CRM_Utils_Sort', 'cmpFunc']);

    if ($this->hasReadOnlyFields()) {
      $this->freeze($this->readOnlyFields);
      CRM_Core_Session::setStatus(ts("Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php."), '', 'info', ['expires' => 0]);
    }

    $this->assign('settingPageName', $filter);
    $this->assign('settingSections', $sections);

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

}
