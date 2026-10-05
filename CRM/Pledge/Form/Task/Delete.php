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
 * This class provides the functionality to delete a group of pledges.
 */
class CRM_Pledge_Form_Task_Delete extends CRM_Pledge_Form_Task {

  use CRM_Core_Form_Task_DeleteTrait;

  protected function getPermissionModule(): string {
    return 'CiviPledge';
  }

  protected function getIDs(): array {
    return $this->_pledgeIds ?? [];
  }

  protected function deleteRecord($id): bool {
    return (bool) CRM_Pledge_BAO_Pledge::deletePledge($id);
  }

}
