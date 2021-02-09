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
 * This class provides the functionality to delete a group of
 * Activities. This class provides functionality for the actual
 * deletion.
 */
class CRM_Activity_Form_Task_Delete extends CRM_Activity_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific Activity?
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Delete Activities'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $deleted = $failed = 0;
    foreach ($this->_activityHolderIds as $activityId['id']) {
      $moveToTrash = CRM_Case_BAO_Case::isCaseActivity($activityId['id']);
      if (CRM_Activity_BAO_Activity::deleteActivity($activityId, $moveToTrash)) {
        $deleted++;
      }
      else {
        $failed++;
      }
    }

    if ($deleted) {
      $msg = ts('%count activity deleted.', ['plural' => '%count activities deleted.', 'count' => $deleted]);
      CRM_Core_Session::setStatus($msg, ts('Removed'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be deleted.', ['plural' => '%count could not be deleted.', 'count' => $failed]), ts('Error'), 'error');
    }
  }

}
