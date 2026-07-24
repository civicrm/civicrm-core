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
 * This class provides the functionality to delete a group of case records.
 */
class CRM_Case_Form_Task_Delete extends CRM_Case_Form_Task {

  use CRM_Core_Form_Task_DeleteTrait;

  /**
   * Are we moving case to Trash.
   *
   * @var bool
   */
  public $_moveToTrash = TRUE;

  protected function getPermissionModule(): string {
    return 'CiviCase';
  }

  protected function getSuccessMessage(int $deleted): string {
    if ($this->_moveToTrash) {
      return ts('%count case moved to trash.', ['plural' => '%count cases moved to trash.', 'count' => $deleted]);
    }
    return ts('%count case permanently deleted.', ['plural' => '%count cases permanently deleted.', 'count' => $deleted]);
  }

  protected function getIDs(): array {
    return $this->_entityIds ?? [];
  }

  protected function deleteRecord($id): bool {
    return (bool) CRM_Case_BAO_Case::deleteCase($id, $this->_moveToTrash);
  }

}
