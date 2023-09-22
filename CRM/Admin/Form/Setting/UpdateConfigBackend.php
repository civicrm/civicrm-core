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
 * This class generates form components for Error Handling and Debugging
 */
class CRM_Admin_Form_Setting_UpdateConfigBackend extends CRM_Admin_Form_Setting {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - Cleanup Caches and Update Paths'));

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Cleanup Caches'),
        'subName' => 'cleanup',
        'icon' => 'fa-undo',

      ],
      [
        'type' => 'next',
        'name' => ts('Reset Paths'),
        'subName' => 'resetpaths',
        'icon' => 'fa-terminal',
      ],
    ]);
  }

  public function postProcess() {
    if (isset($_REQUEST['_qf_UpdateConfigBackend_next_cleanup'])) {
      \Civi\Api4\System::flush(FALSE)->execute();
      CRM_Core_Session::setStatus(ts('Cache has been cleared and menu has been rebuilt successfully.'), ts("Success"), "success");
    }
    elseif (isset($_REQUEST['_qf_UpdateConfigBackend_next_resetpaths'])) {
      $msg = CRM_Core_BAO_ConfigSetting::doSiteMove();
      CRM_Core_Session::setStatus($msg, ts("Success"), "success");
    }

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/setting/updateConfigBackend', 'reset=1'));
  }

}
