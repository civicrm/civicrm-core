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
 * This class generates form components for Error Handling and Debugging
 */
class CRM_Admin_Form_Setting_UpdateConfigBackend extends CRM_Admin_Form_Setting {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Cleanup Caches and Update Paths'));

    $this->addElement(
      'submit', $this->getButtonName('next', 'cleanup'), 'Cleanup Caches',
      array('class' => 'crm-form-submit', 'id' => 'cleanup-cache')
    );

    $this->addElement(
      'submit', $this->getButtonName('next', 'resetpaths'), 'Reset Paths',
      array('class' => 'crm-form-submit', 'id' => 'resetpaths')
    );

    //parent::buildQuickForm();
  }

  public function postProcess() {
    if (!empty($_POST['_qf_UpdateConfigBackend_next_cleanup'])) {

      $config = CRM_Core_Config::singleton();

      // cleanup templates_c directory
      $config->cleanup(1, FALSE);

      // clear all caches
      CRM_Core_Config::clearDBCache();
      Civi::cache('session')->clear();
      CRM_Utils_System::flushCache();

      parent::rebuildMenu();

      CRM_Core_BAO_WordReplacement::rebuild();

      CRM_Core_Session::setStatus(ts('Cache has been cleared and menu has been rebuilt successfully.'), ts("Success"), "success");
    }

    if (!empty($_POST['_qf_UpdateConfigBackend_next_resetpaths'])) {
      $msg = CRM_Core_BAO_ConfigSetting::doSiteMove();

      CRM_Core_Session::setStatus($msg, ts("Success"), "success");
    }

    return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/setting/updateConfigBackend', 'reset=1'));
  }

}
