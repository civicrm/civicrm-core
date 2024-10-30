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
 * This class generates form components
 * for previewing Civicrm Profile Group
 *
 */
class CRM_UF_Form_Inline_PreviewById extends CRM_UF_Form_AbstractPreview {

  /**
   * Pre processing work done here.
   *
   * gets session variables for group or field id
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // Inline forms don't get menu-level permission checks
    if (!CRM_Core_Permission::check('access CiviCRM')) {
      CRM_Core_Error::statusBounce(ts('Permission Denied'));
    }
    $gid = CRM_Utils_Request::retrieve('id', 'Positive');
    $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, NULL, NULL, NULL, FALSE, NULL, FALSE, NULL, CRM_Core_Permission::CREATE, 'field_name', NULL, TRUE);
    $this->setProfile($fields);
  }

}
