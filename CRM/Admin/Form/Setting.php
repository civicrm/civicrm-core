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
 * This class generates form components generic to CiviCRM settings.
 */
class CRM_Admin_Form_Setting extends CRM_Core_Form {

  use CRM_Admin_Form_SettingTrait;

  protected $_settings = [];

  protected $includesReadOnlyFields;

  /**
   * Set default values for the form.
   *
   * Default values are retrieved from the database.
   */
  public function setDefaultValues() {
    if (!$this->_defaults) {
      $this->_defaults = [];
      $formArray = ['Component', 'Localization'];
      $formMode = FALSE;
      if (in_array($this->_name, $formArray)) {
        $formMode = TRUE;
      }

      $this->setDefaultsForMetadataDefinedFields();

      // @todo these should be retrievable from the above function.
      $this->_defaults['enableSSL'] = Civi::settings()->get('enableSSL');
      $this->_defaults['verifySSL'] = Civi::settings()->get('verifySSL');
      $this->_defaults['environment'] = CRM_Core_Config::environment();
      $this->_defaults['enableComponents'] = Civi::settings()->get('enable_components');
    }

    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
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

    $this->addFieldsDefinedInSettingsMetadata();

    if ($this->includesReadOnlyFields) {
      CRM_Core_Session::setStatus(ts("Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php."), '', 'info', ['expires' => 0]);
    }
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    self::commonProcess($params);
  }

  /**
   * Common Process.
   *
   * @todo Document what I do.
   *
   * @param array $params
   * @throws \CRM_Core_Exception
   */
  public function commonProcess(&$params) {

    // save components to be enabled
    if (array_key_exists('enableComponents', $params)) {
      civicrm_api3('setting', 'create', [
        'enable_components' => $params['enableComponents'],
      ]);
      unset($params['enableComponents']);
    }

    foreach (['verifySSL', 'enableSSL'] as $name) {
      if (isset($params[$name])) {
        Civi::settings()->set($name, $params[$name]);
        unset($params[$name]);
      }
    }
    try {
      $settings = $this->getSettingsToSetByMetadata($params);
      $this->saveMetadataDefinedSettings($params);
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Save Failed'), 'error');
    }

    $this->filterParamsSetByMetadata($params);

    $params = CRM_Core_BAO_ConfigSetting::filterSkipVars($params);
    if (!empty($params)) {
      throw new CRM_Core_Exception('Unrecognized setting. This may be a config field which has not been properly migrated to a setting. (' . implode(', ', array_keys($params)) . ')');
    }

    CRM_Core_Config::clearDBCache();
    // This doesn't make a lot of sense to me, but it maintains pre-existing behavior.
    Civi::cache('session')->clear();
    CRM_Utils_System::flushCache();
    CRM_Core_Resources::singleton()->resetCacheCode();

    CRM_Core_Session::setStatus(" ", ts('Changes Saved'), "success");
  }

  public function rebuildMenu() {
    // ensure config is set with new values
    $config = CRM_Core_Config::singleton(TRUE, TRUE);

    // rebuild menu items
    CRM_Core_Menu::store();

    // also delete the IDS file so we can write a new correct one on next load
    $configFile = $config->uploadDir . 'Config.IDS.ini';
    @unlink($configFile);
  }

}
