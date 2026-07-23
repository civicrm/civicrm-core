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
 * This class provides the functionality to delete a group of members.
 */
class CRM_Member_Form_Task_Delete extends CRM_Member_Form_Task {

  use CRM_Core_Form_Task_DeleteTrait;

  protected function getPermissionModule(): string {
    return 'CiviMember';
  }

  protected function getIDs(): array {
    return $this->_memberIds ?? [];
  }

  protected function deleteRecord($id): bool {
    return (bool) CRM_Member_BAO_Membership::del($id);
  }

}
