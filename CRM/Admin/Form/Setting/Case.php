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
 * This class generates form components for CiviCase.
 */
class CRM_Admin_Form_Setting_Case extends CRM_Admin_Form_Setting {

  /**
   * Subset of settings on the page as defined using the legacy method.
   *
   * @var array
   *
   * @deprecated - do not add new settings here - the page to display
   * settings on should be defined in the setting metadata.
   */
  protected $_settings = [
    // @todo remove these, define any not yet defined in the setting metadata.
    'civicaseRedactActivityEmail' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'civicaseAllowMultipleClients' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'civicaseNaturalActivityTypeSort' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'civicaseShowCaseActivities' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - CiviCase'));
    parent::buildQuickForm();
  }

}
