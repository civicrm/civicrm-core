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

  protected $_uf = NULL;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $this->_uf = $config->userFramework;
    $this->_settings['syncCMSEmail'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;

    $this->assign('wpBasePageEnabled', FALSE);
    $this->assign('userFrameworkUsersTableNameEnabled', FALSE);
    $this->assign('viewsIntegration', FALSE);

    $this->setTitle(
      ts('Settings - %1 Integration', [1 => $this->_uf])
    );

    if ($config->userSystem->canSetBasePage()) {
      $this->_settings['wpBasePage'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;
      $this->assign('wpBasePageEnabled', TRUE);
    }

    if ($config->userSystem->hasUsersTable()) {
      $this->_settings['userFrameworkUsersTableName'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;
      $this->assign('userFrameworkUsersTableNameEnabled', TRUE);
    }

    $viewsIntegration = $config->userSystem->viewsIntegration();
    if ($viewsIntegration) {
      $this->assign('viewsIntegration', $viewsIntegration);
    }

    parent::buildQuickForm();
  }

}
