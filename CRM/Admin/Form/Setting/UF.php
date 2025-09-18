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
 * This class generates form components for Site Url.
 */
class CRM_Admin_Form_Setting_UF extends CRM_Admin_Form_Setting {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $this->setTitle(
      ts('Settings - %1 Integration', [1 => $config->userFramework])
    );

    parent::buildQuickForm();

    // Conditionally remove settings that don't apply to the current UF.
    $settingMetaData = $this->getSettingsMetaData();
    if (!$config->userSystem->hasUsersTable()) {
      unset($settingMetaData['userFrameworkUsersTableName']);
    }
    if (!$config->userSystem->canSetBasePage()) {
      unset($settingMetaData['wpBasePage']);
    }
    $this->assign('settings_fields', $settingMetaData);

    $viewsIntegration = $config->userSystem->viewsIntegration();
    $this->assign('viewsIntegration', $viewsIntegration);
  }

}
