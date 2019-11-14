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
 * This class provides the functionality to restore a group of participations.
 */
class CRM_Case_Form_Task_Restore extends CRM_Case_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific case?
   *
   * @var bool
   */
  protected $_single = FALSE;

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
    $this->addDefaultButtons(ts('Restore Cases'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $restoredCases = $failed = 0;
    foreach ($this->_entityIds as $caseId) {
      if (CRM_Case_BAO_Case::restoreCase($caseId)) {
        $restoredCases++;
      }
      else {
        $failed++;
      }
    }

    if ($restoredCases) {
      $msg = ts('%count case restored from trash.', [
        'plural' => '%count cases restored from trash.',
        'count' => $restoredCases,
      ]);
      CRM_Core_Session::setStatus($msg, ts('Restored'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be restored.', ['plural' => '%count could not be restored.', 'count' => $failed]), ts('Error'), 'error');
    }
  }

}
