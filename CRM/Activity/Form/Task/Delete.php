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
 * This class provides the functionality to delete a group of Activities.
 */
class CRM_Activity_Form_Task_Delete extends CRM_Activity_Form_Task {

  use CRM_Core_Form_Task_DeleteTrait;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  protected function getIDs(): array {
    return $this->_activityHolderIds ?? [];
  }

  protected function deleteRecord($id): bool {
    $activityId = is_array($id) ? ($id['id'] ?? reset($id)) : $id;
    $params = ['id' => $activityId];
    $moveToTrash = CRM_Case_BAO_Case::isCaseActivity($activityId);
    return (bool) CRM_Activity_BAO_Activity::deleteActivity($params, $moveToTrash);
  }

}
